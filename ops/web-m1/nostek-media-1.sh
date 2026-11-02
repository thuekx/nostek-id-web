#!/usr/bin/env bash
# nostek-media-1.sh — WEB-M1: media server for nostek.id on its own VPS (host nostek-media)
#
# Stack (all by image digest): Garage (S3-compatible object store, 1 node) · imgproxy (resize to
# WebP/AVIF on the fly, presets only) · Caddy (TLS for media.nostek.id + s3-media.nostek.id).
# Only Caddy publishes ports (80, 443). Garage S3/web/admin/RPC ports stay on the Docker network.
#
# Usage (from the normal ithero shell, never a root shell):
#   sudo bash nostek-media-1.sh PREFLIGHT    --ack WEB-M1 < /dev/null      read-only checks
#   sudo bash nostek-media-1.sh APPLY        --ack WEB-M1 < /dev/null      install + start (auto-rollback)
#   read -rs P; printf '%s\n' "$P" | sudo bash nostek-media-1.sh BACKUP-SETUP --ack WEB-M1 --stdin; unset P
#                                                                          one line: <restic repo> <key id> <key secret> <restic passphrase>
#   sudo bash nostek-media-1.sh BACKUP       --ack WEB-M1 < /dev/null      snapshot + restic + rebuild proof
#   sudo bash nostek-media-1.sh VERIFY       --ack WEB-M1 < /dev/null      end-to-end checks
#   sudo bash nostek-media-1.sh WPKEY        --ack WEB-M1                  show the WordPress S3 key ON THE TERMINAL ONLY
# Every mode ends with one line: RESULT <MODE> PASS|FAIL fail=<n> warn=<n> …
# Secrets: generated on the host or read from --stdin; stored root-only (600) under /etc/nostek-media;
# never in argv, never on stdout/stderr, never in the log.
set -uo pipefail
umask 077

STEP="WEB-M1"
REV="1"
EXPECTED_HOST="nostek-media"
MEDIA_HOST="${NM_MEDIA_HOST:-media.nostek.id}"
S3_HOST="${NM_S3_HOST:-s3-media.nostek.id}"
BUCKET="media.nostek.id"
WP_KEY_NAME="wp-nostek"
IMG_GARAGE="dxflrs/garage:v2.5.0@sha256:677e5b758e4425a325b811c425c026bf35a4bf42f7ddbaa773de3f4fd779a5a5"
IMG_IMGPROXY="darthsim/imgproxy:v4.0.20@sha256:f5db62d528ab08e7b12f14e759fd7d62c09aeabf2e629d0e2a3963da692d0130"
IMG_CADDY="caddy:2.11.7-alpine@sha256:e70b164da81ef698b3ad19af799ea6086ce6d9e48ed4079eb9fe26f6fbf14ee5"
# Test seams (harness only): NM_ROOT prefixes every path, NM_SCHEME=http turns TLS off, NM_HTTP_PORT moves port 80.
ROOT="${NM_ROOT:-}"
SCHEME="${NM_SCHEME:-https}"
HTTP_PORT="${NM_HTTP_PORT:-80}"
HTTPS_PORT="${NM_HTTPS_PORT:-443}"
ETC="$ROOT/etc/nostek-media"
SRV="$ROOT/srv/nostek-media"
OPT="$ROOT/opt/nostek-media"
BAK="$ROOT/var/backups/nostek-media"
LOG="$ROOT/var/log/nostek-media-1.log"
SBIN="$ROOT/usr/local/sbin"
UNITS="$ROOT/etc/systemd/system"
PROJECT="nostek-media"
CANARY_PNG_B64="iVBORw0KGgoAAAANSUhEUgAAAEAAAAAgCAIAAAAt/+nTAAAAN0lEQVR42u3PQQkAAAgEsCtoFQOY3gw+hcEKLDX9WgQEBAQEBAQEBAQEBAQEBAQEBAQEBAQErhZjoKiIMHcjJAAAAABJRU5ErkJggg=="

FAILS=0
WARNS=0
MODE="${1:-}"
shift || true
ACK=""
STDIN=0
TIMER=0
while [ $# -gt 0 ]; do
  case "$1" in
    --ack) ACK="${2:-}"; shift 2 ;;
    --stdin) STDIN=1; shift ;;
    --timer) TIMER=1; shift ;;
    *) echo "FAIL unknown argument: $1"; echo "RESULT ${MODE:-NONE} FAIL fail=1 warn=0 usage"; exit 2 ;;
  esac
done

mkdir -p "$(dirname "$LOG")" 2>/dev/null || true
log() { printf '%s %s\n' "$(TZ=Asia/Jakarta date '+%F %T WIB')" "$*" >> "$LOG" 2>/dev/null || true; }
say() { printf '%s\n' "$*"; log "$*"; }
pass() { say "PASS $*"; }
warn() { WARNS=$((WARNS + 1)); say "WARN $*"; }
fail() { FAILS=$((FAILS + 1)); say "FAIL $*"; }
progress() { say "PROGRESS $*"; }
result() { say "RESULT $MODE $([ "$FAILS" -eq 0 ] && echo PASS || echo FAIL) fail=$FAILS warn=$WARNS $*"; [ "$FAILS" -eq 0 ]; exit $?; }
loud() { # an unmissable line on stdout, stderr and the terminal
  printf '%s\n' "$*"; printf '%s\n' "$*" >&2
  { printf '%s\n' "$*" > /dev/tty; } 2>/dev/null || true
  log "$*"
}

case "$MODE" in PREFLIGHT|APPLY|BACKUP-SETUP|BACKUP|VERIFY|WPKEY) ;; *)
  echo "FAIL mode must be PREFLIGHT, APPLY, BACKUP-SETUP, BACKUP, VERIFY or WPKEY"; MODE="${MODE:-NONE}"; FAILS=1; result "usage" ;;
esac
if [ "$ACK" != "$STEP" ]; then echo "REFUSE: pass --ack $STEP; nothing done"; FAILS=1; result "no-ack"; fi
if [ "$(id -u)" -ne 0 ]; then echo "REFUSE: run with sudo; nothing done"; FAILS=1; result "not-root"; fi
THIS_HOST="$(hostname -s 2>/dev/null || hostname)"
if [ "$THIS_HOST" != "$EXPECTED_HOST" ]; then
  loud "❌ WRONG HOST: this is $THIS_HOST — run $STEP $MODE on $EXPECTED_HOST; nothing done"
  FAILS=1; result "wrong-host"
fi
log "start $STEP rev$REV $MODE"

G() { docker exec "${PROJECT}-garage-1" /garage "$@"; }
compose() { docker compose -p "$PROJECT" -f "$OPT/compose.yaml" "$@"; }
garage_up() { [ "$(docker inspect -f '{{.State.Running}}' "${PROJECT}-garage-1" 2>/dev/null)" = "true" ]; }
wp_secret_pair() { # prints "<id> <secret>" for internal use only (captured, never echoed)
  G key info --show-secret "$WP_KEY_NAME" 2>/dev/null \
    | awk -F': *' '/^Key ID/{i=$2} /^Secret key/{s=$2} END{if(i&&s)print i" "s}'
}
s3_put() { # s3_put <file> <key> <content-type>; credentials go to curl through stdin
  local pair id sec ip
  pair="$(wp_secret_pair)" || return 1
  id="${pair%% *}"; sec="${pair#* }"
  ip="$(docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' "${PROJECT}-garage-1")"
  printf 'user = "%s:%s"\n' "$id" "$sec" | curl -sS -K - --aws-sigv4 "aws:amz:garage:s3" -X PUT \
    --data-binary "@$1" -H "Content-Type: $3" -o /dev/null -w '%{http_code}' "http://$ip:3900/$BUCKET/$2"
}
edge_get() { # edge_get <host> <path> [extra curl args]: through Caddy on this machine
  local host="$1" path="$2"; shift 2
  if [ "$SCHEME" = "https" ]; then
    curl -sS --max-time 20 --resolve "$host:$HTTPS_PORT:127.0.0.1" "$@" "https://$host:$HTTPS_PORT$path"
  else
    curl -sS --max-time 20 -H "Host: $host" "$@" "http://127.0.0.1:$HTTP_PORT$path"
  fi
}

render_files() { # writes compose.yaml, Caddyfile, garage.toml into $1 (staging dir)
  local d="$1" site_media site_s3
  if [ "$SCHEME" = "https" ]; then site_media="$MEDIA_HOST"; site_s3="$S3_HOST"; else site_media="http://$MEDIA_HOST"; site_s3="http://$S3_HOST"; fi
  mkdir -p "$d/garage" "$d/caddy"
  cat > "$d/garage/garage.toml" <<EOF
# WEB-M1 rev$REV — Garage single node (replication 1). Secrets come from files.
metadata_dir = "/var/lib/garage/meta"
data_dir = "/var/lib/garage/data"
metadata_snapshots_dir = "/var/lib/garage/snapshots"
db_engine = "lmdb"
replication_factor = 1
rpc_bind_addr = "0.0.0.0:3901"
rpc_public_addr = "127.0.0.1:3901"
rpc_secret_file = "/etc/garage/rpc_secret"

[s3_api]
s3_region = "garage"
api_bind_addr = "0.0.0.0:3900"
root_domain = ".s3.invalid"

[s3_web]
bind_addr = "0.0.0.0:3902"
root_domain = ".web.invalid"
index = "index.html"

[admin]
api_bind_addr = "0.0.0.0:3903"
admin_token_file = "/etc/garage/admin_token"
EOF
  cat > "$d/caddy/Caddyfile" <<EOF
# WEB-M1 rev$REV
{
	admin off
}
$site_media {
	encode zstd gzip
	header {
		X-Content-Type-Options nosniff
		Access-Control-Allow-Origin *
		-Server
	}
	@write not method GET HEAD OPTIONS
	route {
		respond @write 405
		handle_path /i/* {
			header Cache-Control "public, max-age=31536000, immutable"
			reverse_proxy imgproxy:8080
		}
		handle {
			header Cache-Control "public, max-age=86400"
			reverse_proxy garage:3902
		}
	}
}
$site_s3 {
	request_body {
		max_size 64MB
	}
	reverse_proxy garage:3900
}
# internal origin for imgproxy only (not published)
http://:8081 {
	reverse_proxy garage:3902 {
		header_up Host $MEDIA_HOST
	}
}
EOF
  cat > "$d/compose.yaml" <<EOF
# WEB-M1 rev$REV — rendered by nostek-media-1.sh; do not edit by hand
name: $PROJECT
services:
  garage:
    image: $IMG_GARAGE
    restart: unless-stopped
    environment:
      GARAGE_CONFIG_FILE: /etc/garage/garage.toml
    volumes:
      - $ETC/garage:/etc/garage:ro
      - $SRV/meta:/var/lib/garage/meta
      - $SRV/data:/var/lib/garage/data
      - $SRV/snapshots:/var/lib/garage/snapshots
    logging: {driver: json-file, options: {max-size: "10m", max-file: "3"}}
  imgproxy:
    image: $IMG_IMGPROXY
    restart: unless-stopped
    read_only: true
    tmpfs: [/tmp]
    environment:
      IMGPROXY_BASE_URL: "http://caddy:8081/"
      IMGPROXY_ALLOWED_SOURCES: "http://caddy:8081/"
      IMGPROXY_ONLY_PRESETS: "true"
      IMGPROXY_PRESETS: "w400=resizing_type:fit/width:400,w800=resizing_type:fit/width:800,w1600=resizing_type:fit/width:1600,w2400=resizing_type:fit/width:2400"
      IMGPROXY_AUTO_WEBP: "true"
      IMGPROXY_ENFORCE_WEBP: "true"
      IMGPROXY_MAX_SRC_RESOLUTION: "60"
      IMGPROXY_MAX_SRC_FILE_SIZE: "33554432"
      IMGPROXY_TTL: "31536000"
    depends_on: [garage]
    logging: {driver: json-file, options: {max-size: "10m", max-file: "3"}}
  caddy:
    image: $IMG_CADDY
    restart: unless-stopped
    ports:
      - "$HTTP_PORT:80"
      - "$HTTPS_PORT:443"
      - "$HTTPS_PORT:443/udp"
    volumes:
      - $ETC/caddy/Caddyfile:/etc/caddy/Caddyfile:ro
      - $SRV/caddy/data:/data
      - $SRV/caddy/config:/config
    depends_on: [garage, imgproxy]
    logging: {driver: json-file, options: {max-size: "10m", max-file: "3"}}
EOF
}

# ---------------------------------------------------------------- PREFLIGHT
preflight() {
  progress "1/6 system"
  if grep -q 'VERSION_ID="24.04"' "$ROOT/etc/os-release" 2>/dev/null || grep -q 'VERSION_ID="24.04"' /etc/os-release; then pass "Ubuntu 24.04"; else fail "not Ubuntu 24.04 ($(. /etc/os-release; echo "$PRETTY_NAME"))"; fi
  local tz; tz="$(timedatectl show -p Timezone --value 2>/dev/null || cat /etc/timezone 2>/dev/null)"
  if [ "$tz" = "Asia/Jakarta" ]; then pass "timezone Asia/Jakarta (WIB)"; else warn "timezone is ${tz:-unknown}; APPLY sets Asia/Jakarta (TZ-ALL)"; fi
  local mem; mem="$(awk '/MemTotal/{print int($2/1024)}' /proc/meminfo)"
  if [ "$mem" -ge 3500 ]; then pass "RAM ${mem} MiB"; else fail "RAM ${mem} MiB < 3500 MiB (spec 4 GB)"; fi
  progress "2/6 disk"
  mkdir -p "$ROOT/srv" 2>/dev/null || true
  local free; free="$(df -BG --output=avail "$ROOT/srv" 2>/dev/null | tail -1 | tr -dc 0-9)"
  if [ "${free:-0}" -ge 150 ]; then pass "free disk on /srv ${free} GB"; else fail "free disk on /srv ${free:-0} GB < 150 GB (spec 250 GB)"; fi
  progress "3/6 ports"
  local p
  for p in "$HTTP_PORT" "$HTTPS_PORT"; do
    if ss -ltnH "sport = :$p" 2>/dev/null | grep -q . && ! docker ps --format '{{.Names}}' 2>/dev/null | grep -q "^${PROJECT}-caddy-1$"; then
      fail "port $p already in use by another program"
    else pass "port $p free (or ours)"; fi
  done
  progress "4/6 DNS"
  local h ip myips
  myips="$(hostname -I 2>/dev/null) $(curl -4 -sS --max-time 8 https://api.ipify.org 2>/dev/null)"
  for h in "$MEDIA_HOST" "$S3_HOST"; do
    ip="$(getent ahostsv4 "$h" 2>/dev/null | awk 'NR==1{print $1}')"
    if [ -z "$ip" ]; then warn "DNS $h has no A record yet (needed before VERIFY: certificate)"
    elif printf ' %s ' "$myips" | grep -q " $ip "; then pass "DNS $h -> $ip (this VPS)"
    else warn "DNS $h -> $ip, not this VPS ($myips); fine only if it is a CDN proxy in front of this VPS"; fi
  done
  progress "5/6 docker"
  if command -v docker >/dev/null 2>&1 && docker compose version >/dev/null 2>&1; then pass "docker + compose present ($(docker --version | awk '{print $3}' | tr -d ,))"
  else warn "docker / compose not installed; APPLY installs docker.io + docker-compose-v2 from Ubuntu"; fi
  progress "6/6 existing install"
  if [ -f "$OPT/compose.yaml" ]; then warn "an install exists ($OPT); APPLY re-renders it (old copy kept in $BAK)"; else pass "no previous install"; fi
  result "host=$THIS_HOST"
}

# ---------------------------------------------------------------- APPLY
ROLL_DIR=""
STARTED_NEW=0
rollback() {
  say "ROLLBACK started (APPLY failed at: ${1:-unknown})"
  if [ "$STARTED_NEW" -eq 1 ]; then compose down >/dev/null 2>&1 || true; fi
  local failed; failed="$BAK/FAILED-$(date +%Y%m%d_%H%M%S)"
  mkdir -p "$failed"
  [ -e "$OPT/compose.yaml" ] && mv "$OPT/compose.yaml" "$failed/" 2>/dev/null
  [ -e "$ETC/caddy/Caddyfile" ] && mv "$ETC/caddy/Caddyfile" "$failed/" 2>/dev/null
  [ -e "$ETC/garage/garage.toml" ] && mv "$ETC/garage/garage.toml" "$failed/" 2>/dev/null
  if [ -n "$ROLL_DIR" ] && [ -d "$ROLL_DIR" ]; then
    [ -e "$ROLL_DIR/compose.yaml" ] && cp -a "$ROLL_DIR/compose.yaml" "$OPT/"
    [ -e "$ROLL_DIR/Caddyfile" ] && cp -a "$ROLL_DIR/Caddyfile" "$ETC/caddy/"
    [ -e "$ROLL_DIR/garage.toml" ] && cp -a "$ROLL_DIR/garage.toml" "$ETC/garage/"
    [ -e "$OPT/compose.yaml" ] && compose up -d >/dev/null 2>&1 && say "ROLLBACK previous install started again"
  fi
  say "ROLLBACK done: new files moved aside to $failed (data under $SRV untouched)"
}
step_or_rollback() { # step_or_rollback <label> <command…>
  local label="$1"; shift
  if "$@"; then pass "$label"; else fail "$label"; rollback "$label"; result "at=$label rolled-back=yes"; fi
}
apt_install() {
  export DEBIAN_FRONTEND=noninteractive NEEDRESTART_MODE=l
  local o=(-o Dpkg::Use-Pty=0 -o DPkg::Lock::Timeout=120 -q -y)
  progress "apt update"
  apt-get "${o[@]}" update < /dev/null > /dev/null || return 1
  progress "apt install $*"
  apt-get "${o[@]}" install --no-install-recommends "$@" < /dev/null > /dev/null
}
gen_secret_hex() { od -An -tx1 -N32 /dev/urandom | tr -d ' \n'; }
apply() {
  progress "1/9 timezone"
  if [ "$(timedatectl show -p Timezone --value 2>/dev/null)" != "Asia/Jakarta" ]; then
    timedatectl set-timezone Asia/Jakarta && pass "timezone set to Asia/Jakarta" || warn "could not set timezone"
  else pass "timezone already Asia/Jakarta"; fi
  progress "2/9 packages"
  if ! { command -v docker >/dev/null && docker compose version >/dev/null 2>&1 && command -v restic >/dev/null; }; then
    step_or_rollback "packages docker.io docker-compose-v2 restic curl" apt_install docker.io docker-compose-v2 restic curl ca-certificates
    systemctl enable --now docker >/dev/null 2>&1 || true
  else pass "packages present"; fi
  progress "3/9 directories + secrets"
  mkdir -p "$ETC/garage" "$ETC/caddy" "$SRV/meta" "$SRV/data" "$SRV/snapshots" "$SRV/caddy/data" "$SRV/caddy/config" "$OPT" "$BAK" "$SBIN"
  chmod 700 "$ETC" "$ETC/garage"
  [ -s "$ETC/garage/rpc_secret" ] || { gen_secret_hex > "$ETC/garage/rpc_secret"; chmod 600 "$ETC/garage/rpc_secret"; }
  [ -s "$ETC/garage/admin_token" ] || { head -c 32 /dev/urandom | base64 | tr -d '\n=' > "$ETC/garage/admin_token"; chmod 600 "$ETC/garage/admin_token"; }
  pass "secrets present (root-only files, not shown)"
  progress "4/9 render config"
  ROLL_DIR="$BAK/previous-$(date +%Y%m%d_%H%M%S)"
  if [ -f "$OPT/compose.yaml" ]; then
    mkdir -p "$ROLL_DIR"; cp -a "$OPT/compose.yaml" "$ETC/caddy/Caddyfile" "$ETC/garage/garage.toml" "$ROLL_DIR/" 2>/dev/null
    say "INFO previous config kept in $ROLL_DIR"
  else ROLL_DIR=""; STARTED_NEW=1; fi
  local tmp; tmp="$(mktemp -d)"
  render_files "$tmp"
  install -m 600 "$tmp/garage/garage.toml" "$ETC/garage/garage.toml"
  install -m 644 "$tmp/caddy/Caddyfile" "$ETC/caddy/Caddyfile"
  install -m 600 "$tmp/compose.yaml" "$OPT/compose.yaml"
  rm -rf "$tmp"
  install -m 700 "$0" "$SBIN/nostek-media-1.sh"
  pass "config rendered (compose $(sha256sum "$OPT/compose.yaml" | cut -c1-12), Caddyfile $(sha256sum "$ETC/caddy/Caddyfile" | cut -c1-12))"
  progress "5/9 pull images by digest"
  local img
  for img in "$IMG_GARAGE" "$IMG_IMGPROXY" "$IMG_CADDY"; do
    if docker image inspect "$img" >/dev/null 2>&1; then pass "image ${img%%@*} present at its digest"
    else step_or_rollback "image ${img%%@*} pulled by digest" docker pull -q "$img"; fi
  done
  progress "6/9 start"
  step_or_rollback "containers started" compose up -d --remove-orphans
  local _i; for _i in $(seq 1 30); do G status >/dev/null 2>&1 && break; sleep 1; done
  step_or_rollback "garage answers" G status
  progress "7/9 cluster layout"
  if G status 2>/dev/null | grep -q "NO ROLE ASSIGNED"; then
    local node cap
    node="$(G node id -q 2>/dev/null | cut -d@ -f1)"
    cap="$(df -BG --output=avail "$SRV/data" | tail -1 | tr -dc 0-9)"; cap=$(( cap * 9 / 10 ))
    step_or_rollback "layout assign ${cap}G" G layout assign -z jkt -c "${cap}G" "$node"
    step_or_rollback "layout apply" G layout apply --version "$(( $(G layout show 2>/dev/null | awk '/Current cluster layout version/{print $NF}') + 1 ))"
  else pass "layout already assigned"; fi
  progress "8/9 bucket + key"
  G bucket info "$BUCKET" >/dev/null 2>&1 || step_or_rollback "bucket $BUCKET created" G bucket create "$BUCKET"
  G key info "$WP_KEY_NAME" >/dev/null 2>&1 || { G key create "$WP_KEY_NAME" > /dev/null 2>&1 && pass "key $WP_KEY_NAME created (secret not shown)"; }
  step_or_rollback "key $WP_KEY_NAME may read/write $BUCKET" G bucket allow --read --write "$BUCKET" --key "$WP_KEY_NAME"
  step_or_rollback "public website on $BUCKET" G bucket website --allow "$BUCKET"
  progress "9/9 canary objects"
  local c; c="$(mktemp)"
  if [ ! -s "$ETC/canary.sha256" ]; then printf 'nostek-media canary %s\n' "$(gen_secret_hex | cut -c1-16)" > "$c"; else cat "$ETC/canary.txt" > "$c" 2>/dev/null || printf 'nostek-media canary\n' > "$c"; fi
  cp "$c" "$ETC/canary.txt"; sha256sum "$c" | cut -d' ' -f1 > "$ETC/canary.sha256"
  [ "$(s3_put "$c" "_nostek/canary.txt" text/plain)" = "200" ] && pass "canary.txt uploaded through S3" || { fail "canary.txt upload"; rollback "canary"; result "at=canary rolled-back=yes"; }
  printf '%s' "$CANARY_PNG_B64" | base64 -d > "$c"
  [ "$(s3_put "$c" "_nostek/canary.png" image/png)" = "200" ] && pass "canary.png uploaded through S3" || { fail "canary.png upload"; rollback "canary"; result "at=canary rolled-back=yes"; }
  rm -f "$c"
  say "NEXT: BACKUP-SETUP, then BACKUP (first snapshot + rebuild proof), then VERIFY. Do not point WordPress here before VERIFY PASS (NEW-VM-BACKUP)."
  result "garage=v2.5.0 imgproxy=v4.0.20 caddy=2.11.7 bucket=$BUCKET"
}

# ---------------------------------------------------------------- BACKUP-SETUP
backup_setup() {
  if [ "$STDIN" -ne 1 ]; then fail "BACKUP-SETUP reads one line from --stdin (see usage)"; result "no-stdin"; fi
  local line repo kid ksec pass_ x
  IFS= read -r line || true
  read -r repo kid ksec pass_ x <<< "$line"
  if [ -z "${repo:-}" ] || [ -z "${kid:-}" ] || [ -z "${ksec:-}" ] || [ -z "${pass_:-}" ] || [ -n "${x:-}" ]; then
    fail "expected 4 values separated by spaces: <restic repo> <key id> <key secret> <restic passphrase>"; result "bad-input"
  fi
  case "$repo" in s3:*|b2:*|sftp:*|rest:*) ;; /*) [ -n "$ROOT" ] || { fail "a local path is not an off-site backup"; result "bad-repo"; } ;; *) fail "repo must start with s3:, b2:, sftp: or rest:"; result "bad-repo" ;; esac
  if [ "${#pass_}" -lt 16 ]; then fail "restic passphrase shorter than 16 characters"; result "weak-passphrase"; fi
  mkdir -p "$ETC"
  { printf 'RESTIC_REPOSITORY=%s\n' "$repo"
    case "$repo" in b2:*) printf 'B2_ACCOUNT_ID=%s\nB2_ACCOUNT_KEY=%s\n' "$kid" "$ksec" ;;
      *) printf 'AWS_ACCESS_KEY_ID=%s\nAWS_SECRET_ACCESS_KEY=%s\n' "$kid" "$ksec" ;; esac
  } > "$ETC/backup.env"
  printf '%s' "$pass_" > "$ETC/restic.pass"
  chmod 600 "$ETC/backup.env" "$ETC/restic.pass"
  unset line kid ksec pass_
  pass "backup target stored root-only (repo $(printf '%s' "$repo" | sed -E 's#(//[^/]+).*#\1/…#'))"
  install -m 700 "$0" "$SBIN/nostek-media-1.sh"
  cat > "$UNITS/nostek-media-backup.service" <<EOF
[Unit]
Description=WEB-M1 nostek-media nightly backup (restic)
After=docker.service
[Service]
Type=oneshot
ExecStart=/bin/bash $SBIN/nostek-media-1.sh BACKUP --ack $STEP --timer
StandardInput=null
EOF
  cat > "$UNITS/nostek-media-backup.timer" <<EOF
[Unit]
Description=WEB-M1 nostek-media backup at 03:30 WIB
[Timer]
OnCalendar=*-*-* 03:30:00 Asia/Jakarta
Persistent=true
RandomizedDelaySec=300
[Install]
WantedBy=timers.target
EOF
  systemctl daemon-reload >/dev/null 2>&1 && systemctl enable --now nostek-media-backup.timer >/dev/null 2>&1 \
    && pass "timer nostek-media-backup.timer enabled (03:30 WIB)" || fail "timer could not be enabled"
  result "next=BACKUP"
}

# ---------------------------------------------------------------- BACKUP
restic_env() { set -a; . "$ETC/backup.env"; set +a; export RESTIC_PASSWORD_FILE="$ETC/restic.pass"; }
backup() {
  [ -s "$ETC/backup.env" ] || { fail "no backup target: run BACKUP-SETUP first"; result "no-target"; }
  garage_up || { fail "garage is not running"; result "garage-down"; }
  restic_env
  progress "1/5 metadata snapshot"
  G meta snapshot >/dev/null 2>&1 && pass "garage metadata snapshot" || { fail "garage meta snapshot"; result "at=snapshot"; }
  local snaps latest
  snaps="$(ls -1d "$SRV/snapshots"/*/ 2>/dev/null | sort)"
  latest="$(printf '%s\n' "$snaps" | tail -1)"; latest="${latest%/}"
  printf '%s\n' "$snaps" | head -n -2 | while read -r s; do [ -n "$s" ] && mkdir -p "$BAK/old-snapshots" && mv "$s" "$BAK/old-snapshots/" ; done
  find "$BAK/old-snapshots" -mindepth 1 -maxdepth 1 -mtime +3 -exec rm -rf {} + 2>/dev/null || true  # old copies only
  progress "2/5 restic repository"
  if ! restic cat config >/dev/null 2>&1; then
    restic init >/dev/null 2>&1 && pass "restic repository initialised" || { fail "restic init (check repo / keys)"; result "at=init"; }
  else pass "restic repository reachable"; fi
  progress "3/5 restic backup"
  local out
  out="$(restic backup --json --tag web-m1 --host "$EXPECTED_HOST" \
      --exclude "$SRV/meta/db.lmdb" "$ETC" "$SRV/meta" "$latest" "$SRV/data" "$OPT" 2>&1 \
    | awk '/"message_type":"status"/ { if (match($0, /"percent_done":[0-9.]+/)) { p=int(substr($0,RSTART+15,RLENGTH-15)*100); if (p>=n) { printf "PROGRESS backup %d%%\n", p > "/dev/stderr"; n=p+10 } } }
           /"message_type":"summary"/ { print }' )"
  local sid; sid="$(printf '%s' "$out" | grep -o '"snapshot_id":"[0-9a-f]*"' | cut -d'"' -f4)"
  if [ -n "$sid" ]; then pass "restic snapshot ${sid:0:8} ($(printf '%s' "$out" | grep -o '"total_files_processed":[0-9]*' | cut -d: -f2) files)"; else fail "restic backup"; result "at=backup"; fi
  progress "4/5 retention"
  restic forget --tag web-m1 --keep-daily 7 --keep-weekly 4 --keep-monthly 6 --prune >/dev/null 2>&1 && pass "retention 7 daily / 4 weekly / 6 monthly" || warn "restic forget/prune"
  if [ "$TIMER" -eq 1 ]; then result "snapshot=${sid:0:8} proof=skipped(timer)"; fi
  progress "5/5 rebuild proof (restore into a temporary Garage)"
  local proof net name ip got want
  proof="$ROOT/var/tmp/nostek-media-proof"
  [ -d "$proof" ] && mv "$proof" "$BAK/proof-old-$(date +%s)" && find "$BAK" -maxdepth 1 -name 'proof-old-*' -mmin +60 -exec rm -rf {} + 2>/dev/null
  mkdir -p "$proof"
  restic restore "$sid" --target "$proof" >/dev/null 2>&1 || { fail "restic restore"; result "at=restore"; }
  local r="$proof$SRV" rs; rs="$(ls -1d "$r/snapshots"/*/ 2>/dev/null | tail -1)"
  [ -n "$rs" ] && [ -f "$rs/db.lmdb" ] || { fail "restored metadata snapshot missing"; result "at=restore"; }
  mkdir -p "$r/meta/db.lmdb"; cp "$rs/db.lmdb" "$r/meta/db.lmdb/data.mdb"
  net="nm-proof-$$"; name="nm-proof-garage-$$"
  docker network create --internal "$net" >/dev/null
  docker run -d --name "$name" --network "$net" -e GARAGE_CONFIG_FILE=/etc/garage/garage.toml \
    -v "$proof$ETC/garage:/etc/garage:ro" -v "$r/meta:/var/lib/garage/meta" -v "$r/data:/var/lib/garage/data" \
    -v "$r/snapshots:/var/lib/garage/snapshots" "$IMG_GARAGE" >/dev/null
  ip="$(docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' "$name")"
  got=""; for _ in $(seq 1 30); do got="$(curl -sS --max-time 5 -H "Host: $MEDIA_HOST" "http://$ip:3902/_nostek/canary.txt" 2>/dev/null | sha256sum | cut -d' ' -f1)"; [ "$got" = "$(cat "$ETC/canary.sha256")" ] && break; sleep 1; done
  want="$(cat "$ETC/canary.sha256")"
  docker rm -f "$name" >/dev/null 2>&1; docker network rm "$net" >/dev/null 2>&1
  if [ "$got" = "$want" ]; then
    printf 'snapshot=%s time=%s\n' "$sid" "$(date -u +%FT%TZ)" > "$ETC/backup-proof"
    pass "rebuild proof: restored Garage served the canary (sha ${want:0:12})"
  else fail "rebuild proof: canary from restored Garage does not match"; fi
  rm -rf "$proof"
  result "snapshot=${sid:0:8}"
}

# ---------------------------------------------------------------- VERIFY
verify() {
  progress "1/6 host"
  [ "$(timedatectl show -p Timezone --value 2>/dev/null)" = "Asia/Jakarta" ] && pass "timezone WIB" || fail "timezone not Asia/Jakarta"
  progress "2/6 containers"
  local svc want have
  for svc in garage:"$IMG_GARAGE" imgproxy:"$IMG_IMGPROXY" caddy:"$IMG_CADDY"; do
    want="${svc#*:}"; svc="${svc%%:*}"
    have="$(docker inspect -f '{{.Config.Image}} {{.State.Running}}' "${PROJECT}-${svc}-1" 2>/dev/null)"
    [ "$have" = "$want true" ] && pass "$svc running at its digest" || fail "$svc not running at $want (got: ${have:-none})"
  done
  progress "3/6 exposure"
  local leak; leak="$(ss -ltnH 2>/dev/null | awk '{print $4}' | grep -E ':(3900|3901|3902|3903|8080|8081)$' || true)"
  [ -z "$leak" ] && pass "no Garage/imgproxy port open on the host" || fail "internal port open on the host: $leak"
  [ "$(edge_get "$S3_HOST" "/" -o /dev/null -w '%{http_code}')" = "403" ] && pass "S3 API refuses anonymous listing (403)" || fail "S3 API anonymous listing not refused"
  [ "$(edge_get "$MEDIA_HOST" "/_nostek/canary.txt" -X PUT -d x -o /dev/null -w '%{http_code}')" = "405" ] && pass "media site refuses writes (405)" || fail "media site accepts a write method"
  progress "4/6 media + resize"
  local got; got="$(edge_get "$MEDIA_HOST" "/_nostek/canary.txt" | sha256sum | cut -d' ' -f1)"
  [ "$got" = "$(cat "$ETC/canary.sha256" 2>/dev/null)" ] && pass "$SCHEME://$MEDIA_HOST/_nostek/canary.txt served (TLS + DNS ok)" || fail "canary not served by $SCHEME://$MEDIA_HOST (DNS / certificate / garage)"
  local ct; ct="$(edge_get "$MEDIA_HOST" "/i/insecure/w400/plain/_nostek/canary.png" -H 'Accept: image/webp' -o /dev/null -w '%{content_type} %{http_code}')"
  [ "$ct" = "image/webp 200" ] && pass "resize /i/…/w400 returns image/webp" || fail "resize answer: $ct"
  [ "$(edge_get "$MEDIA_HOST" "/i/insecure/rs:fit:5000:5000/plain/_nostek/canary.png" -o /dev/null -w '%{http_code}')" != "200" ] && pass "free-form resize refused (presets only)" || fail "imgproxy accepts free-form options"
  progress "5/6 backup"
  if [ -s "$ETC/backup-proof" ] && [ -n "$(find "$ETC/backup-proof" -mmin -2880)" ]; then pass "backup + rebuild proof $(cut -d' ' -f1 "$ETC/backup-proof" | cut -c1-17) (< 48 h)"
  else fail "no backup rebuild proof in the last 48 h (run BACKUP) — NEW-VM-BACKUP"; fi
  systemctl is-enabled nostek-media-backup.timer >/dev/null 2>&1 && pass "backup timer enabled" || fail "backup timer not enabled"
  progress "6/6 space"
  local use; use="$(df --output=pcent "$SRV/data" | tail -1 | tr -dc 0-9)"
  [ "$use" -lt 80 ] && pass "data disk ${use}% used" || warn "data disk ${use}% used"
  result "media=$SCHEME://$MEDIA_HOST s3=$SCHEME://$S3_HOST"
}

# ---------------------------------------------------------------- WPKEY
wpkey() {
  if ! { : > /dev/tty; } 2>/dev/null; then fail "WPKEY shows the key on your terminal only; no terminal here"; result "no-tty"; fi
  local pair; pair="$(wp_secret_pair)" || true
  [ -n "$pair" ] || { fail "key $WP_KEY_NAME not found (run APPLY)"; result "no-key"; }
  {
    printf '\n  WordPress offload settings (copy into wp-admin, then clear the screen with: clear)\n'
    printf '  Endpoint : https://%s\n  Region   : garage\n  Bucket   : %s\n  Path style: on\n' "$S3_HOST" "$BUCKET"
    printf '  Access key: %s\n  Secret key: %s\n  Public URL: https://%s\n\n' "${pair%% *}" "${pair#* }" "$MEDIA_HOST"
  } > /dev/tty
  unset pair
  pass "key shown on the terminal only (not in stdout, stderr or the log)"
  result "key=$WP_KEY_NAME"
}

case "$MODE" in
  PREFLIGHT) preflight ;;
  APPLY) apply ;;
  BACKUP-SETUP) backup_setup ;;
  BACKUP) backup ;;
  VERIFY) verify ;;
  WPKEY) wpkey ;;
esac
