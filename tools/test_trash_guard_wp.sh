#!/usr/bin/env bash
# WEB-45 end-to-end check of the roles v3 trash rules (Nostek Core
# includes/trash-guard.php) on a throwaway WordPress (SQLite). Not part of CI
# (downloads WordPress); run by hand after changing the roles or trash-guard.php:
#   bash tools/test_trash_guard_wp.sh   -> "RESULT trash-guard-wp PASS n/n"
# Needs php (pdo_sqlite, zip), curl, unzip. Nothing outside a temp dir is touched.
set -euo pipefail

REPO="$(cd "$(dirname "$0")/.." && pwd)"
WORK="$(mktemp -d)"
cleanup() { rm -rf "$WORK"; }
trap cleanup EXIT

cd "$WORK"
for u in https://wordpress.org/latest.tar.gz \
	https://downloads.wordpress.org/plugin/sqlite-database-integration.zip \
	https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar; do
	curl -fsSL -o "$(basename "$u")" "$u"
done
tar xzf latest.tar.gz
cd wordpress
unzip -q ../sqlite-database-integration.zip -d wp-content/plugins
sed "s#{SQLITE_IMPLEMENTATION_FOLDER_PATH}#$PWD/wp-content/plugins/sqlite-database-integration#; s#{SQLITE_PLUGIN}#sqlite-database-integration/load.php#" \
	wp-content/plugins/sqlite-database-integration/db.copy > wp-content/db.php
cp wp-config-sample.php wp-config.php
ln -s "$REPO/plugins/nostek-core" wp-content/plugins/nostek-core
wp() { php "$WORK/wp-cli.phar" --allow-root --path="$WORK/wordpress" "$@"; }
pw() { head -c 12 /dev/urandom | od -An -tx1 | tr -d ' \n'; }   # test-only, never stored
wp core install --url=http://127.0.0.1 --title=T --admin_user=a --admin_password="$(pw)" --admin_email=a@example.com --skip-email >/dev/null

# Roles v2 first, like the live sites today, then the upgrade to v3.
wp plugin activate nostek-core >/dev/null
wp eval 'foreach (["delete_posts","delete_others_posts","delete_pages","delete_others_pages"] as $c) get_role("nostek_publisher")->remove_cap($c);' >/dev/null
wp option update nostek_core_roles_version 2 >/dev/null
wp user create pub pub@example.com --role=nostek_publisher --user_pass="$(pw)" >/dev/null
wp user create agt agt@example.com --role=nostek_agent --user_pass="$(pw)" >/dev/null
wp user create agt2 agt2@example.com --role=nostek_agent --user_pass="$(pw)" >/dev/null
wp eval 'do_action("init");' >/dev/null   # first load after the deploy applies v3

ok=0; n=0
check() { n=$((n + 1)); if [ "$2" = "$3" ]; then ok=$((ok + 1)); echo "ok   $1"; else echo "FAIL $1: got '$2' want '$3'"; fi; }
mk() { wp post create --post_type="$1" --post_status="$2" --post_author="$(wp user get "$3" --field=ID)" --post_title="t-$1-$2" --porcelain; }
# REST call as a user, internally (permission callbacks + rest_pre_dispatch run as on the site).
cat > "$WORK/rest.php" <<'PHP'
<?php
$r = new WP_REST_Request( $args[0], $args[1] );
foreach ( array_slice( $args, 2 ) as $kv ) { [ $k, $v ] = explode( '=', $kv, 2 ); $r->set_param( $k, $v ); }
echo rest_get_server()->dispatch( $r )->get_status();
PHP
rest() { local u="$1"; shift; wp eval-file "$WORK/rest.php" "$@" --user="$u"; }
status() { wp post get "$1" --field=post_status; }

check 'roles version 3' "$(wp option get nostek_core_roles_version)" "3"
check 'publisher has delete_others_pages' "$(wp eval 'echo get_role("nostek_publisher")->has_cap("delete_others_pages") ? 1 : 0;')" "1"

D=$(mk page draft agt2)
check 'publisher trashes another author draft page (200)' "$(rest pub DELETE "/wp/v2/pages/$D")" "200"
check '  it is in the Trash' "$(status "$D")" "trash"
check '  audit meta written' "$(wp post meta get "$D" _nostek_trashed_by | php -r 'echo json_decode(stream_get_contents(STDIN))->old_status;')" "draft"
check 'second DELETE without force (410 already trashed)' "$(rest pub DELETE "/wp/v2/pages/$D")" "410"
check 'DELETE force=true on trashed (403)' "$(rest pub DELETE "/wp/v2/pages/$D" force=true)" "403"
check '  still in the Trash' "$(status "$D")" "trash"
check 'publisher restores it (untrash)' "$(wp eval --user=pub 'echo current_user_can("delete_post", '"$D"') && wp_untrash_post('"$D"') ? 1 : 0;')" "1"
check '  restore audit meta written' "$(wp post meta get "$D" _nostek_untrashed_by | php -r 'echo json_decode(stream_get_contents(STDIN))->user;')" "pub"

P=$(mk post pending agt2)
check 'publisher trashes another author pending post (200)' "$(rest pub DELETE "/wp/v2/posts/$P")" "200"
L=$(mk post publish agt2)
check 'publisher cannot trash a published post (403)' "$(rest pub DELETE "/wp/v2/posts/$L")" "403"
check '  still published' "$(status "$L")" "publish"
check '  wp_trash_post as publisher refused too' "$(wp eval --user=pub 'echo wp_trash_post('"$L"') ? 1 : 0;')" "0"
F=$(wp post create --post_status=future --post_date="2099-01-01 00:00:00" --post_title=f --post_author="$(wp user get agt2 --field=ID)" --porcelain)
check 'publisher cannot trash a scheduled post (403)' "$(rest pub DELETE "/wp/v2/posts/$F")" "403"
V=$(mk page private agt2)
check 'publisher cannot trash a private page (403)' "$(rest pub DELETE "/wp/v2/pages/$V")" "403"
X=$(mk page draft agt2)
check 'publisher DELETE force=true on a draft (403)' "$(rest pub DELETE "/wp/v2/pages/$X" force=true)" "403"
check '  draft untouched' "$(status "$X")" "draft"
check 'wp_delete_post force as publisher refused' "$(wp eval --user=pub 'echo wp_delete_post('"$X"', true) ? 1 : 0;')" "0"

O=$(mk post draft agt)
check 'agent trashes its own draft (200)' "$(rest agt DELETE "/wp/v2/posts/$O")" "200"
Q=$(mk post draft agt2)
check 'agent cannot trash another author draft (403)' "$(rest agt DELETE "/wp/v2/posts/$Q")" "403"
check 'admin keeps core behaviour (force delete 200)' "$(rest a DELETE "/wp/v2/posts/$Q" force=true)" "200"

if [ "$ok" = "$n" ]; then echo "RESULT trash-guard-wp PASS $ok/$n"; else echo "RESULT trash-guard-wp FAIL $ok/$n"; exit 1; fi
