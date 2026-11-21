#!/usr/bin/env bash
# WEB-41 end-to-end check of the /product/ shop URLs on a throwaway WordPress +
# WooCommerce (SQLite, PHP built-in server). Not part of CI (downloads ~55 MB);
# run by hand after changing plugins/nostek-core/includes/product-urls.php:
#   bash tools/test_product_urls_wp.sh   -> "RESULT product-urls-wp PASS n/n"
# Needs php (pdo_sqlite, curl, zip), curl, unzip. Nothing outside a temp dir is touched.
set -euo pipefail

REPO="$(cd "$(dirname "$0")/.." && pwd)"
WORK="$(mktemp -d)"
PORT="${PORT:-8099}"
H="http://127.0.0.1:${PORT}"
SRV=""
cleanup() { [ -n "$SRV" ] && kill "$SRV" 2>/dev/null; rm -rf "$WORK"; }
trap cleanup EXIT

cd "$WORK"
for u in https://wordpress.org/latest.tar.gz \
	https://downloads.wordpress.org/plugin/sqlite-database-integration.zip \
	https://downloads.wordpress.org/plugin/woocommerce.zip \
	https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar; do
	curl -fsSL -o "$(basename "$u")" "$u"
done
tar xzf latest.tar.gz
cd wordpress
unzip -q ../sqlite-database-integration.zip -d wp-content/plugins
unzip -q ../woocommerce.zip -d wp-content/plugins
sed "s#{SQLITE_IMPLEMENTATION_FOLDER_PATH}#$PWD/wp-content/plugins/sqlite-database-integration#; s#{SQLITE_PLUGIN}#sqlite-database-integration/load.php#" \
	wp-content/plugins/sqlite-database-integration/db.copy > wp-content/db.php
cp wp-config-sample.php wp-config.php
ln -s "$REPO/plugins/nostek-core" wp-content/plugins/nostek-core
wp() { php "$WORK/wp-cli.phar" --allow-root --path="$WORK/wordpress" "$@"; }
# Test-only admin password, generated here and never stored.
wp core install --url="$H" --title=T --admin_user=a --admin_password="$(head -c 12 /dev/urandom | od -An -tx1 | tr -d ' \n')" --admin_email=a@example.com --skip-email >/dev/null
wp option update home "$H" >/dev/null   # wp-cli guesses a /wordpress sub-path here
wp option update siteurl "$H" >/dev/null
wp rewrite structure '/%postname%/' >/dev/null
wp plugin activate woocommerce >/dev/null
php -S "127.0.0.1:${PORT}" >/dev/null 2>&1 &
SRV=$!
sleep 1

P=$(wp wc product_cat create --name="Nostek Digital" --slug=nostek-digital --user=a --porcelain)
EB=$(wp wc product_cat create --name=E-Book --slug=e-book --parent="$P" --user=a --porcelain)
VF=$(wp wc product_cat create --name=Vintage --slug=vintage-fashion --user=a --porcelain)
wp wc product create --name="Kaos Retro" --slug=kaos-retro --regular_price=10 --categories="[{\"id\":$VF}]" --user=a --porcelain >/dev/null
wp wc product create --name=Panduan --slug=panduan-ebook --regular_price=5 --categories="[{\"id\":$EB}]" --user=a --porcelain >/dev/null

ok=0; n=0
check() { n=$((n + 1)); if [ "$2" = "$3" ]; then ok=$((ok + 1)); echo "ok   $1"; else echo "FAIL $1: got '$2' want '$3'"; fi; }
get() { curl -s -o /dev/null -w "%{http_code} %{redirect_url}" "$H$1" | sed 's/ $//'; }

check 'before: WooCommerce default category URL' "$(get /product-category/nostek-digital/)" "200"
wp plugin activate nostek-core >/dev/null
curl -s -o /dev/null "$H/"   # first request after activation rebuilds the rules
check 'product /product/kaos-retro/' "$(get /product/kaos-retro/)" "200"
check 'product in sub-category /product/panduan-ebook/' "$(get /product/panduan-ebook/)" "200"
check 'category /product/nostek-digital/' "$(get /product/nostek-digital/)" "200"
check 'sub-category /product/nostek-digital/e-book/' "$(get /product/nostek-digital/e-book/)" "200"
check 'product feed' "$(get /product/kaos-retro/feed/)" "200"
check 'product embed' "$(get /product/kaos-retro/embed/)" "200"
check 'unknown slug 404' "$(get /product/tidak-ada/)" "404"
check 'old category 301' "$(get /product-category/nostek-digital/)" "301 $H/product/nostek-digital/"
check 'old flat sub-category 301' "$(get /product-category/e-book/)" "301 $H/product/nostek-digital/e-book/"
check 'old category feed 301' "$(get /product-category/nostek-digital/feed/)" "301 $H/product/nostek-digital/feed/"
check 'sub-category by slug alone 301' "$(get '/product/e-book/?orderby=price')" "301 $H/product/nostek-digital/e-book/?orderby=price"
check 'sub-category under wrong parent 301' "$(get /product/vintage-fashion/e-book/)" "301 $H/product/nostek-digital/e-book/"
check 'old unknown category 404' "$(get /product-category/tidak-ada/)" "404"
S=$(wp wc product create --name="Vintage Fashion" --slug=vintage-fashion --regular_price=1 --user=a --porcelain)
check 'product slug clash gets -2' "$(wp post get "$S" --field=post_name)" "vintage-fashion-2"
T=$(wp wc product_cat create --name="Kaos Retro" --slug=kaos-retro --user=a --porcelain)
check 'category slug clash gets -2' "$(wp term get product_cat "$T" --field=slug)" "kaos-retro-2"
wp plugin deactivate nostek-core >/dev/null
check 'deactivated: products still work' "$(get /product/kaos-retro/)" "200"
check 'deactivated: WooCommerce default category URL back' "$(get /product-category/nostek-digital/)" "200"
wp plugin activate nostek-core >/dev/null
curl -s -o /dev/null "$H/"
check 'reactivated: category under /product/' "$(get /product/nostek-digital/)" "200"
check 'reactivated: product' "$(get /product/kaos-retro/)" "200"

echo "versions: WordPress $(wp core version), WooCommerce $(wp plugin get woocommerce --field=version)"
if [ "$ok" = "$n" ]; then echo "RESULT product-urls-wp PASS $ok/$n"; else echo "RESULT product-urls-wp FAIL $ok/$n"; exit 1; fi
