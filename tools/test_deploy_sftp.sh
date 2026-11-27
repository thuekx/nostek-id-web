#!/usr/bin/env bash
# Local test of bin/deploy-sftp.sh against a throwaway OpenSSH SFTP server
# (chrooted internal-sftp on 127.0.0.1:2299, password login, like a Nostek
# Panel SFTP account whose folder is /wp-content). Needs root, sshd, lftp.
# Prints one RESULT line per case and "RESULT all N passed" at the end.
set -uo pipefail
[ "$(id -u)" = 0 ] || { echo "REFUSE: run as root (starts a test sshd)"; exit 1; }
repo="$(cd "$(dirname "$0")/.." && pwd)"
mkdir -p /srv; t="$(mktemp -d /srv/nk-sftp-test.XXXXXX)"; port=2299; user=nkdeploytest  # chroot parents must be root-owned, not /tmp
pass="$(head -c 18 /dev/urandom | base64 | tr -dc 'A-Za-z0-9' | head -c 20)"
n=0; bad=0
ok() { n=$((n+1)); if eval "$2"; then echo "RESULT ok   $1"; else echo "RESULT FAIL $1"; bad=$((bad+1)); fi; }
cleanup() { [ -f "$t/sshd.pid" ] && kill "$(cat "$t/sshd.pid")" 2>/dev/null; userdel "$user" 2>/dev/null; rm -rf "$t"; }
trap cleanup EXIT

id "$user" >/dev/null 2>&1 || useradd -M -s /usr/sbin/nologin "$user"
printf '%s:%s\n' "$user" "$pass" | chpasswd
# Like the panel, "/" of the account is wp-content itself (chroot needs it root-owned).
mkdir -p "$t/root/wp-content/themes" "$t/root/wp-content/plugins" && chown root:root "$t" "$t/root" "$t/root/wp-content" && chmod 755 "$t" "$t/root" "$t/root/wp-content"
ssh-keygen -q -t ed25519 -N '' -f "$t/hostkey"
mkdir -p /run/sshd
cat > "$t/sshd_config" <<EOF
Port $port
ListenAddress 127.0.0.1
HostKey $t/hostkey
PidFile $t/sshd.pid
PasswordAuthentication yes
KbdInteractiveAuthentication no
UsePAM yes
Subsystem sftp internal-sftp
Match User $user
  ChrootDirectory $t/root/wp-content
  ForceCommand internal-sftp
  AllowTcpForwarding no
EOF
if (exec 3<>/dev/tcp/127.0.0.1/$port) 2>/dev/null; then echo "REFUSE: port $port already in use (old test sshd?)"; exit 1; fi
/usr/sbin/sshd -f "$t/sshd_config" || { echo "FAIL: test sshd did not start"; exit 1; }
sleep 1
pin="$(ssh-keygen -lf "$t/hostkey.pub" | awk '{print $2}')"

# Server copy starts with a stale file (must go) and a neighbour (must stay).
mkdir -p "$t/root/wp-content/themes/nostek" "$t/root/wp-content/themes/other" "$t/root/wp-content/uploads"
echo old > "$t/root/wp-content/themes/nostek/removed-template.html"
echo keep > "$t/root/wp-content/themes/other/style.css"
echo keep > "$t/root/wp-content/uploads/photo.jpg"
chown -R "$user" "$t/root/wp-content/themes" "$t/root/wp-content/plugins" "$t/root/wp-content/uploads"

run() { # run deploy with overrides: run VAR=val ...
  (cd "$repo" && env SFTP_HOST=127.0.0.1 SFTP_PORT=$port SFTP_USER=$user LFTP_PASSWORD="$pass" \
     SFTP_HOSTKEY_SHA256="$pin" SITE=test "$@" bash bin/deploy-sftp.sh) > "$t/out" 2>&1; echo $? > "$t/rc"; { echo "--- run $*"; cat "$t/out"; } >> "${TEST_LOG:-/dev/null}"
}
W="$t/root/wp-content"

run SFTP_HOSTKEY_SHA256=
ok "no pin -> REFUSE, nothing written" 'grep -q "^REFUSE: SFTP_HOSTKEY_SHA256" "$t/out" && [ "$(cat $t/rc)" != 0 ] && [ ! -e "$W/plugins/nostek-core" ]'
run SFTP_HOSTKEY_SHA256=SHA256:AAAAwrongpinAAAA
ok "wrong pin -> FAIL WRONG HOST, nothing written" 'grep -q "WRONG HOST" "$t/out" && [ "$(cat $t/rc)" != 0 ] && [ ! -e "$W/plugins/nostek-core" ]'
run LFTP_PASSWORD=wrong-password-123
ok "wrong password -> FAIL, nothing written" 'grep -q "^FAIL: lftp exit" "$t/out" && [ ! -e "$W/plugins/nostek-core" ]'
ok "password never printed" '! grep -qF "$pass" "$t/out"'
run DRY_RUN=1
ok "dry run writes nothing" 'grep -q "^PASS dry run" "$t/out" && [ ! -e "$W/plugins/nostek-core" ] && [ -f "$W/themes/nostek/removed-template.html" ]'
run
ok "deploy PASS line" 'grep -q "^PASS deployed local to test over SFTP" "$t/out" && [ "$(cat $t/rc)" = 0 ]'
ok "theme identical to repo" 'diff -r "$repo/themes/nostek" "$W/themes/nostek" >/dev/null'
ok "plugin identical to repo" 'diff -r "$repo/plugins/nostek-core" "$W/plugins/nostek-core" >/dev/null'
ok "stale theme file removed" '[ ! -e "$W/themes/nostek/removed-template.html" ]'
ok "other theme and uploads untouched" '[ -f "$W/themes/other/style.css" ] && [ -f "$W/uploads/photo.jpg" ]'
ok "password never printed" '! grep -qF "$pass" "$t/out"'
run
ok "second deploy PASS (idempotent)" 'grep -q "^PASS deployed" "$t/out" && diff -r "$repo/themes/nostek" "$W/themes/nostek" >/dev/null'
(cd "$t" && mkdir -p fake && cd fake && env SFTP_HOST=127.0.0.1 SFTP_PORT=$port SFTP_USER=$user LFTP_PASSWORD="$pass" SFTP_HOSTKEY_SHA256="$pin" SITE=test bash "$repo/bin/deploy-sftp.sh") > "$t/out" 2>&1
ok "wrong folder -> FAIL before connecting, server copy kept" 'grep -q "style.css missing" "$t/out" && [ -f "$W/themes/nostek/style.css" ]'

if [ "$bad" = 0 ]; then echo "RESULT all $n passed"; else echo "RESULT $bad of $n FAILED"; exit 1; fi
