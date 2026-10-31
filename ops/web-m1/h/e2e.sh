#!/usr/bin/env bash
# shellcheck disable=SC2034  # rc / SEC are read inside check() strings
# WEB-M1 end-to-end harness: real Garage / imgproxy / Caddy images in Docker, fakes only for
# hostname, timedatectl, systemctl and df (free space). TLS off (NM_SCHEME=http), ports 18080/18443.
# Run: sudo bash ops/web-m1/h/e2e.sh   -> "RESULT e2e PASS n/n"
set -uo pipefail
HERE="$(cd "$(dirname "$0")" && pwd)"
PKG="$HERE/../nostek-media-1.sh"
W="$(mktemp -d /tmp/nm-e2e.XXXX)"
FAKE="$W/fakebin"; mkdir -p "$FAKE"
export NM_ROOT="$W/root" NM_SCHEME=http NM_HTTP_PORT=18080 NM_HTTPS_PORT=18443
mkdir -p "$NM_ROOT/etc/systemd/system" "$NM_ROOT/etc"
cat > "$FAKE/hostname" <<'F'
#!/bin/sh
case "$1" in -I) echo "127.0.0.1" ;; *) echo "${NM_FAKE_HOST:-nostek-media}" ;; esac
F
cat > "$FAKE/timedatectl" <<'F'
#!/bin/sh
case "$1" in show) echo "Asia/Jakarta" ;; *) exit 0 ;; esac
F
cat > "$FAKE/systemctl" <<'F'
#!/bin/sh
exit 0
F
cat > "$FAKE/df" <<'F'
#!/bin/sh
for a in "$@"; do [ "$a" = "--output=avail" ] && { echo " Avail"; echo " 200G"; exit 0; }; done
exec /usr/bin/df "$@"
F
chmod +x "$FAKE"/*
export PATH="$FAKE:$PATH"
OK=0; N=0
check() { N=$((N+1)); if eval "$2"; then OK=$((OK+1)); echo "ok   $1"; else echo "FAIL $1"; fi; }
run() { bash "$PKG" "$@" 2>&1; }
cleanup() { docker compose -p nostek-media -f "$NM_ROOT/opt/nostek-media/compose.yaml" down -v >/dev/null 2>&1; rm -rf "$W"; }
trap cleanup EXIT

out="$(NM_FAKE_HOST=web-old run PREFLIGHT --ack WEB-M1 < /dev/null)"; rc=$?
check "wrong host refused with one loud line" '[ $rc -ne 0 ] && grep -q "❌ WRONG HOST: this is web-old — run WEB-M1 PREFLIGHT on nostek-media; nothing done" <<<"$out"'
out="$(run APPLY < /dev/null)"; rc=$?
check "no --ack refused" '[ $rc -ne 0 ] && grep -q "REFUSE: pass --ack WEB-M1" <<<"$out"'
out="$(run PREFLIGHT --ack WEB-M1 < /dev/null)"; rc=$?
check "PREFLIGHT passes on a fresh host" '[ $rc -eq 0 ] && grep -q "^RESULT PREFLIGHT PASS fail=0" <<<"$out"'
check "PREFLIGHT changed nothing" '[ ! -e "$NM_ROOT/opt/nostek-media" ] && [ ! -e "$NM_ROOT/etc/nostek-media" ]'
out="$(run APPLY --ack WEB-M1 < /dev/null)"; rc=$?; echo "$out" | grep -E "^(FAIL|RESULT)" 
check "APPLY passes" '[ $rc -eq 0 ] && grep -q "^RESULT APPLY PASS fail=0" <<<"$out"'
check "APPLY shows progress" '[ "$(grep -c "^PROGRESS" <<<"$out")" -ge 9 ]'
check "secret files are 600 root" '[ "$(stat -c %a "$NM_ROOT/etc/nostek-media/garage/rpc_secret")" = 600 ] && [ "$(stat -c %a "$NM_ROOT/etc/nostek-media/garage/admin_token")" = 600 ]'
export SEC; SEC="$(docker exec nostek-media-garage-1 /garage key info --show-secret wp-nostek 2>/dev/null | awk -F': *' '/^Secret key/{print $2}')"
check "WP secret never in output or log" '[ -n "$SEC" ] && ! grep -q "$SEC" <<<"$out" && ! grep -rq "$SEC" "$NM_ROOT/var/log"'
out="$(run VERIFY --ack WEB-M1 < /dev/null)"; rc=$?
check "VERIFY fails before backup proof (NEW-VM-BACKUP)" '[ $rc -ne 0 ] && grep -q "FAIL no backup rebuild proof" <<<"$out" && [ "$(grep -c "^FAIL" <<<"$out")" -eq 1 ]'
check "VERIFY media + resize + exposure lines pass" 'grep -q "PASS http://media.nostek.id/_nostek/canary.txt served" <<<"$out" && grep -q "PASS resize /i/…/w400 returns image/webp" <<<"$out" && grep -q "PASS S3 API refuses anonymous listing" <<<"$out" && grep -q "PASS free-form resize refused" <<<"$out" && grep -q "PASS media site refuses writes" <<<"$out" && grep -q "PASS no Garage/imgproxy port open" <<<"$out"'
out="$(printf 'only two\n' | run BACKUP-SETUP --ack WEB-M1 --stdin)"; rc=$?
check "BACKUP-SETUP rejects bad input" '[ $rc -ne 0 ] && grep -q "expected 4 values" <<<"$out"'
PASSPHRASE="fake-test-passphrase-$RANDOM-$RANDOM"
out="$(printf '%s\n' "$W/restic-repo fakekey fakesecret $PASSPHRASE" | run BACKUP-SETUP --ack WEB-M1 --stdin)"; rc=$?
check "BACKUP-SETUP stores target, no secret printed" '[ $rc -eq 0 ] && ! grep -q "$PASSPHRASE" <<<"$out" && [ "$(stat -c %a "$NM_ROOT/etc/nostek-media/restic.pass")" = 600 ]'
check "timer unit written for 03:30 WIB" 'grep -q "OnCalendar=\*-\*-\* 03:30:00 Asia/Jakarta" "$NM_ROOT/etc/systemd/system/nostek-media-backup.timer"'
out="$(run BACKUP --ack WEB-M1 < /dev/null)"; rc=$?; echo "$out" | grep -E "^(FAIL|RESULT|PASS rebuild)"
check "BACKUP passes with rebuild proof" '[ $rc -eq 0 ] && grep -q "PASS rebuild proof: restored Garage served the canary" <<<"$out"'
check "BACKUP shows progress" 'grep -q "PROGRESS backup" <<<"$out" && [ "$(grep -c "^PROGRESS [0-9]/5" <<<"$out")" -eq 5 ]'
check "no proof container left" '[ -z "$(docker ps -aq --filter name=nm-proof)" ]'
out="$(run VERIFY --ack WEB-M1 < /dev/null)"; rc=$?; echo "$out" | grep -E "^(FAIL|RESULT)"
check "VERIFY passes after BACKUP" '[ $rc -eq 0 ] && grep -q "^RESULT VERIFY PASS fail=0" <<<"$out"'
# re-APPLY is idempotent and keeps data
out="$(run APPLY --ack WEB-M1 < /dev/null)"; rc=$?
check "second APPLY passes and keeps the canary" '[ $rc -eq 0 ] && grep -q "layout already assigned" <<<"$out" && [ "$(curl -s -H "Host: media.nostek.id" http://127.0.0.1:18080/_nostek/canary.txt | sha256sum | cut -d" " -f1)" = "$(cat "$NM_ROOT/etc/nostek-media/canary.sha256")" ]'
# a failing step rolls back and keeps the previous install
out="$(IMGPROXY_BREAK=1 bash -c 'sed "s#darthsim/imgproxy:v4.0.20@sha256:f5db#darthsim/imgproxy:v4.0.20@sha256:0000#" "$0" > "$1"; bash "$1" APPLY --ack WEB-M1 < /dev/null' "$PKG" "$W/broken.sh" 2>&1)"; rc=$?
check "broken image digest -> FAIL + rollback, previous stack running" '[ $rc -ne 0 ] && grep -q "ROLLBACK previous install started again" <<<"$out" && [ "$(docker inspect -f "{{.State.Running}}" nostek-media-caddy-1)" = true ] && ls "$NM_ROOT/var/backups/nostek-media" | grep -q FAILED-'
out="$(run WPKEY --ack WEB-M1 < /dev/null)"
check "WPKEY never writes the secret to stdout/stderr" '! grep -q "$SEC" <<<"$out"'
unset SEC
echo "RESULT e2e $([ $OK -eq $N ] && echo PASS || echo FAIL) $OK/$N"
[ $OK -eq $N ]
