#!/usr/bin/env bash
# Deploy the theme and Nostek Core to a Nostek Panel website over SFTP.
#
# The SFTP account's folder is the website's /wp-content, so "/" on the
# server is wp-content. Only two folders are ever written, each mirrored
# exactly (files removed from the repo are removed on the server):
#   themes/nostek/        -> /themes/nostek/
#   plugins/nostek-core/  -> /plugins/nostek-core/
#
# Environment (set by .github/workflows/deploy.yml from secrets):
#   SFTP_HOST           server name or address
#   SFTP_PORT           default 2222 (Nostek Panel SFTP)
#   SFTP_USER           SFTP account name
#   LFTP_PASSWORD       SFTP account password (read by lftp --env-password,
#                       never on a command line)
#   SFTP_HOSTKEY_SHA256 pinned host key, e.g. SHA256:abc... (ssh-keygen -lf)
#   SITE                label for the result line
#   DRY_RUN=1           list what would change, write nothing
set -euo pipefail

say() { echo "$*"; }
fail() { echo "FAIL: $*"; exit 1; }

port="${SFTP_PORT:-2222}"
[ -n "${SFTP_HOST:-}" ] || fail "SFTP_HOST is empty; nothing deployed"
[ -n "${SFTP_USER:-}" ] || fail "SFTP_USER is empty; nothing deployed"
[ -n "${LFTP_PASSWORD:-}" ] || fail "SFTP password is empty; nothing deployed"
case "$port" in ''|*[!0-9]*) fail "SFTP_PORT is not a number; nothing deployed" ;; esac

# Never mirror an empty or wrong folder: --delete would empty the server copy.
[ -f themes/nostek/style.css ] || fail "themes/nostek/style.css missing; run from the repo root; nothing deployed"
[ -f plugins/nostek-core/nostek-core.php ] || fail "plugins/nostek-core/nostek-core.php missing; nothing deployed"

work="$(mktemp -d)"
trap 'rm -rf "$work"' EXIT
kh="$work/known_hosts"

# Pin the server's host key: scan it, compare its fingerprint with the pin.
ssh-keyscan -T 15 -p "$port" -t ed25519 "$SFTP_HOST" 2>/dev/null > "$kh" || true
[ -s "$kh" ] || fail "no ed25519 host key from $SITE SFTP (port $port); nothing deployed"
seen="$(ssh-keygen -lf "$kh" | awk 'NR==1{print $2}')"
if [ -z "${SFTP_HOSTKEY_SHA256:-}" ]; then
  echo "REFUSE: SFTP_HOSTKEY_SHA256 is not set. The server shows $seen; compare it with the fingerprint read on the server, then save it as the secret. Nothing deployed."
  exit 1
fi
[ "$seen" = "$SFTP_HOSTKEY_SHA256" ] || fail "host key of $SITE SFTP is $seen, pinned $SFTP_HOSTKEY_SHA256 (WRONG HOST or changed key); nothing deployed"
say "OK host key pinned ($seen)"

dry=""
[ "${DRY_RUN:-0}" = "1" ] && dry="--dry-run"

ssh_cmd="ssh -a -x -o UserKnownHostsFile=$kh -o GlobalKnownHostsFile=/dev/null -o StrictHostKeyChecking=yes -o PubkeyAuthentication=no -o PreferredAuthentications=password,keyboard-interactive -o NumberOfPasswordPrompts=1 -o ConnectTimeout=20"

cat > "$work/script.lftp" <<EOF
set cmd:fail-exit yes
set net:max-retries 3
set net:reconnect-interval-base 5
set net:timeout 30
set sftp:auto-confirm no
set sftp:connect-program "$ssh_cmd"
set xfer:clobber yes
open --env-password -u "$SFTP_USER" -p $port sftp://$SFTP_HOST
mkdir -p -f /themes /plugins
mirror --reverse --delete --no-perms --verbose=1 $dry themes/nostek/ /themes/nostek/
mirror --reverse --delete --no-perms --verbose=1 $dry plugins/nostek-core/ /plugins/nostek-core/
bye
EOF

set +e
lftp -f "$work/script.lftp" 2>&1 | sed -E 's/(pass(word)?[=:] *)[^ ]+/\1***/Ig'
rc=${PIPESTATUS[0]}
set -e
[ "$rc" -eq 0 ] || fail "lftp exit $rc while deploying to $SITE"
if [ -n "$dry" ]; then
  say "PASS dry run for $SITE (nothing written)"
else
  say "PASS deployed ${GITHUB_SHA:-local} to $SITE over SFTP"
fi
