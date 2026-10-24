#!/usr/bin/env python3
"""Move the theme's demo content on STAGING to the Trash over the REST API.

Keeps: the pages in site/pages.json, the pages listed under "keep_pages"
(WooCommerce and privacy pages) and every post that carries the post meta
nostek_source_id (articles sent with publish_article.py). Everything else of
the chosen types goes to the Trash, never deleted for good: WordPress keeps
it there for 30 days and Trash > Restore brings it back. An item that fails
is reported and skipped; the run then ends with FAIL. Media files are
never touched (they have no Trash). Standard library only.

Credentials come from the environment, never from argv. The user must be an
Administrator (it trashes other users' content):
  NOSTEK_WP_URL           e.g. https://staging.nostek.id (production is refused)
  NOSTEK_WP_USER          WordPress username
  NOSTEK_WP_APP_PASSWORD  Application Password of that user

Usage:
  cleanup_demo.py                              # dry run: list what would go
  cleanup_demo.py --apply                      # move it to the Trash
  cleanup_demo.py --types posts,pages          # leave WooCommerce products
"""
import argparse
import json
import os
import pathlib
import sys
import urllib.parse

sys.path.insert(0, str(pathlib.Path(__file__).resolve().parent))
from publish_article import WP, Fail  # noqa: E402

SPEC = pathlib.Path(__file__).resolve().parent.parent / "site" / "pages.json"
PRODUCTION_HOSTS = {"nostek.id", "www.nostek.id"}
STATUSES = "publish,draft,pending,private,future"
# --types name -> REST base
TYPES = {"posts": "posts", "pages": "pages", "products": "product"}


def list_all(wp, rest_base, fields):
    items, page = [], 1
    while True:
        try:
            batch = wp.call("GET", rest_base, {
                "status": STATUSES, "context": "edit", "per_page": 100, "page": page, "_fields": fields,
            })
        except Fail as e:
            # WordPress answers 400 rest_post_invalid_page_number past the last page.
            if page > 1 and "rest_post_invalid_page_number" in str(e):
                return items
            raise
        items += batch
        if len(batch) < 100:
            return items
        page += 1


def main():
    ap = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument("--apply", action="store_true", help="move to the Trash (default: dry run)")
    ap.add_argument("--types", default="posts,pages,products", help="comma list of: " + ", ".join(TYPES))
    ap.add_argument("--spec", default=str(SPEC), help="pages file (default: site/pages.json)")
    args = ap.parse_args()

    try:
        env = {k: os.environ.get(k, "") for k in ("NOSTEK_WP_URL", "NOSTEK_WP_USER", "NOSTEK_WP_APP_PASSWORD")}
        missing = [k for k, v in env.items() if not v]
        if missing:
            raise Fail("missing environment variables: " + ", ".join(missing))
        host = (urllib.parse.urlsplit(env["NOSTEK_WP_URL"]).hostname or "").lower()
        if host in PRODUCTION_HOSTS:
            print(f"REFUSE: demo cleanup never runs on production ({host}); nothing done")
            return 1
        types = [t.strip() for t in args.types.split(",") if t.strip()]
        unknown = [t for t in types if t not in TYPES]
        if unknown or not types:
            raise Fail(f"--types must be a comma list of {', '.join(TYPES)} (got {args.types!r})")
        try:
            spec = json.loads(pathlib.Path(args.spec).read_text(encoding="utf-8"))
        except (OSError, json.JSONDecodeError) as e:
            raise Fail(f"cannot read {args.spec}: {e}")
        keep_slugs = {p["slug"] for p in spec["pages"]} | set(spec.get("keep_pages", []))

        wp = WP(env["NOSTEK_WP_URL"], env["NOSTEK_WP_USER"], env["NOSTEK_WP_APP_PASSWORD"])
        # Fails early with HTTP 401/403 when the user is not an Administrator.
        wp.call("GET", "settings", {"_fields": "title"})

        doomed = []
        for t in types:
            fields = "id,slug,status,title,meta" if t == "posts" else "id,slug,status,title"
            try:
                items = list_all(wp, TYPES[t], fields)
            except Fail as e:
                if t == "products" and "HTTP 404" in str(e):
                    print("SKIP products: no product REST route (WooCommerce off or products not in REST)")
                    continue
                raise
            kept = 0
            for it in items:
                if t == "pages" and it["slug"] in keep_slugs:
                    kept += 1
                    continue
                if t == "posts" and (it.get("meta") or {}).get("nostek_source_id"):
                    kept += 1
                    continue
                doomed.append((t, it))
            print(f"SCAN {t}: {len(items)} found, {kept} kept, {len(items) - kept} to Trash")

        failed = 0
        for k, (t, it) in enumerate(doomed, 1):
            title = (it.get("title") or {}).get("raw") or (it.get("title") or {}).get("rendered") or ""
            line = f"{t[:-1]} id={it['id']} slug={it['slug']} status={it['status']} title={title[:60]!r}"
            if not args.apply:
                print(f"PLAN trash {line}")
                continue
            # One item failing (e.g. a PHP error in an old plugin's trash hook)
            # does not stop the rest: each item only moves to the Trash.
            try:
                res = wp.call("DELETE", f"{TYPES[t]}/{it['id']}")
            except Fail as e:
                failed += 1
                print(f"FAIL {k}/{len(doomed)} trash {line}: {e}")
                continue
            if res.get("status") != "trash":
                failed += 1
                print(f"FAIL {k}/{len(doomed)} trash {line}: not moved to the Trash (status={res.get('status')!r})")
                continue
            print(f"PROGRESS {k}/{len(doomed)} trashed {line}")

        if failed:
            print(f"FAIL moved {len(doomed) - failed} of {len(doomed)} item(s) to the Trash on "
                  f"{env['NOSTEK_WP_URL']}; {failed} failed (see the FAIL lines above)")
            return 1
        mode = "PASS" if args.apply else "DRY RUN ok:"
        verb = "moved" if args.apply else "would move"
        print(f"{mode} {verb} {len(doomed)} item(s) to the Trash on {env['NOSTEK_WP_URL']}")
        return 0
    except Fail as e:
        print(f"FAIL {e}", file=sys.stderr)
        return 1


if __name__ == "__main__":
    sys.exit(main())
