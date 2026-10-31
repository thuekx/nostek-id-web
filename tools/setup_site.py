#!/usr/bin/env python3
"""Create the site's terms, base pages and reading settings over the REST API.

Reads site/pages.json. Journal categories, Topics, WooCommerce product
categories that already exist (same slug) and pages that already exist are
left as they are, so editors' changes
are never overwritten. The one exception: a page whose spec lists earlier
placeholder texts under "replaces" gets the new content when its current
content is still exactly one of those placeholders (nobody has edited it).
A page or term with "parent" is created under that parent (listed earlier in
the file), so /sell/faq/ and /support/faq/ cannot be mixed up. Product
categories are skipped with a WARN when WooCommerce is not active. Then sets
Settings > Reading: a static front page and the Journal as the posts page, and
the WooCommerce store settings listed under "shop_settings" (Rupiah, Indonesian
number format) through wc/v3; those are skipped with a WARN without WooCommerce.
Standard library only.

Credentials come from the environment, never from argv. The user must be an
Administrator (reading settings need manage_options):
  NOSTEK_WP_URL           e.g. https://staging.nostek.id
  NOSTEK_WP_USER          WordPress username
  NOSTEK_WP_APP_PASSWORD  Application Password of that user

Usage:
  setup_site.py                 # dry run: show what would change
  setup_site.py --apply         # create missing terms and pages, set reading settings
"""
import argparse
import json
import os
import pathlib
import sys

sys.path.insert(0, str(pathlib.Path(__file__).resolve().parent))
from publish_article import WP, Fail  # noqa: E402

SPEC = pathlib.Path(__file__).resolve().parent.parent / "site" / "pages.json"

# spec key -> (REST base, label in output)
TERMS = {
    "categories": ("categories", "category"),
    "topics": ("topic", "topic"),
    "product_categories": ("product_cat", "product category"),
}


def load_spec(path):
    try:
        spec = json.loads(path.read_text(encoding="utf-8"))
    except (OSError, json.JSONDecodeError) as e:
        raise Fail(f"cannot read {path}: {e}")
    slugs = [p["slug"] for p in spec["pages"]]
    for i, p in enumerate(spec["pages"]):
        if p.get("parent") and p["parent"] not in slugs[:i]:
            raise Fail(f"page '{p['slug']}': parent '{p['parent']}' must be listed before it")
    for key in ("front_page", "posts_page"):
        if spec.get(key) not in slugs:
            raise Fail(f"'{key}' must name one of the pages: {slugs}")
    for key in TERMS:
        for t in spec.get(key, []):
            if not all(t.get(f) for f in ("slug", "name")):
                raise Fail(f"every entry in '{key}' needs a slug and a name: {t}")
    return spec


def ensure_terms(wp, spec, apply):
    changes = 0
    for key, (rest_base, label) in TERMS.items():
        terms = spec.get(key, [])
        if not terms:
            continue
        if key == "product_categories":
            try:
                wp.call("GET", rest_base, {"per_page": 1, "_fields": "id"})
            except Fail as e:
                print(f"WARN {label}: skipped, WooCommerce taxonomy not reachable ({e})")
                continue
        ids = {}
        for t in terms:
            found = wp.call("GET", rest_base, {"slug": t["slug"], "_fields": "id"})
            if found:
                ids[t["slug"]] = found[0]["id"]
                print(f"SKIP {label} '{t['slug']}' exists (id={found[0]['id']})")
                continue
            changes += 1
            if not apply:
                print(f"PLAN create {label} '{t['slug']}'" + (f" under '{t['parent']}'" if t.get("parent") else ""))
                continue
            body = {"slug": t["slug"], "name": t["name"], "description": t.get("description", "")}
            if t.get("parent"):
                if t["parent"] not in ids:
                    raise Fail(f"{label} '{t['slug']}': parent '{t['parent']}' not found")
                body["parent"] = ids[t["parent"]]
            res = wp.call("POST", rest_base, body=body)
            if res["slug"] != t["slug"]:
                raise Fail(f"{label} '{t['slug']}' was created as '{res['slug']}'")
            ids[t["slug"]] = res["id"]
            print(f"PASS create {label} '{t['slug']}' id={res['id']}")
    return changes


def ensure_shop_settings(wp, spec, apply):
    want = spec.get("shop_settings", {})
    if not want:
        return 0
    site = wp.base.split("/wp-json/")[0]
    wc = WP(site, "", "")
    wc.base, wc.auth = site + "/wp-json/wc/v3/", wp.auth
    try:
        have = {s["id"]: s.get("value") for s in wc.call("GET", "settings/general")}
    except Fail as e:
        print(f"WARN shop settings: skipped, WooCommerce REST not reachable ({e})")
        return 0
    diff = {k: v for k, v in want.items() if have.get(k) != v}
    if not diff:
        print("SKIP shop settings already set")
        return 0
    if not apply:
        print(f"PLAN shop settings {json.dumps(diff)}")
        return 1
    body = {"update": [{"id": k, "value": v} for k, v in diff.items()]}
    res = wc.call("POST", "settings/general/batch", body=body)
    bad = [u for u in res.get("update", []) if "error" in u]
    if bad:
        raise Fail(f"shop settings not saved: {json.dumps(bad)}")
    print(f"PASS shop settings {json.dumps(diff)}")
    return 1


def refresh_page(wp, page, current, apply):
    """Swap an untouched placeholder page for its new content. True when it changes."""
    pid = current["id"]
    raw = (current.get("content") or {}).get("raw", "").strip()
    if raw == page["content"].strip():
        print(f"SKIP page '{page['slug']}' up to date (id={pid})")
        return False
    if raw not in [r.strip() for r in page.get("replaces", [])]:
        print(f"SKIP page '{page['slug']}' exists (id={pid}, {current['status']})")
        return False
    if not apply:
        print(f"PLAN update page '{page['slug']}' id={pid} (placeholder -> new content)")
        return True
    wp.call("POST", f"pages/{pid}", body={"content": page["content"]})
    print(f"PASS update page '{page['slug']}' id={pid} (placeholder -> new content)")
    return True


def main():
    ap = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument("--apply", action="store_true", help="write to WordPress (default: dry run)")
    ap.add_argument("--spec", default=str(SPEC), help="pages file (default: site/pages.json)")
    args = ap.parse_args()

    try:
        env = {k: os.environ.get(k, "") for k in ("NOSTEK_WP_URL", "NOSTEK_WP_USER", "NOSTEK_WP_APP_PASSWORD")}
        missing = [k for k, v in env.items() if not v]
        if missing:
            raise Fail("missing environment variables: " + ", ".join(missing))

        spec = load_spec(pathlib.Path(args.spec))
        wp = WP(env["NOSTEK_WP_URL"], env["NOSTEK_WP_USER"], env["NOSTEK_WP_APP_PASSWORD"])
        # Fails early with HTTP 401/403 when the user is not an Administrator.
        settings = wp.call("GET", "settings", {"_fields": "show_on_front,page_on_front,page_for_posts"})

        changes = ensure_terms(wp, spec, args.apply)
        ids = {}
        for page in spec["pages"]:
            parent = page.get("parent")
            if parent and parent not in ids:
                # Parent is only planned (dry run): the child is new too.
                changes += 1
                print(f"PLAN create page '{parent}/{page['slug']}'")
                continue
            query = {
                "slug": page["slug"], "status": "publish,draft,pending,private,future",
                "context": "edit", "_fields": "id,status,content",
            }
            if parent:
                query["parent"] = ids[parent]
            found = wp.call("GET", "pages", query)
            if found:
                ids[page["slug"]] = found[0]["id"]
                if refresh_page(wp, page, found[0], args.apply):
                    changes += 1
                continue
            changes += 1
            if not args.apply:
                print(f"PLAN create page '{page['slug']}'")
                continue
            body = {"slug": page["slug"], "title": page["title"], "content": page["content"], "status": "publish"}
            if parent:
                body["parent"] = ids[parent]
            res = wp.call("POST", "pages", body=body)
            if res["slug"] != page["slug"]:
                raise Fail(f"page '{page['slug']}' was created as '{res['slug']}' (slug taken, e.g. by a page in Trash)")
            ids[page["slug"]] = res["id"]
            print(f"PASS create page '{page['slug']}' id={res['id']}")

        want = {"show_on_front": "page"}
        for key, slug in (("page_on_front", spec["front_page"]), ("page_for_posts", spec["posts_page"])):
            if slug in ids:
                want[key] = ids[slug]
        diff = {k: v for k, v in want.items() if settings.get(k) != v}
        if not args.apply and len(want) < 3:
            diff.update({k: "(new page)" for k in ("page_on_front", "page_for_posts") if k not in want})
        if not diff:
            print("SKIP reading settings already set")
        elif not args.apply:
            changes += 1
            print(f"PLAN reading settings {json.dumps(diff)}")
        else:
            changes += 1
            wp.call("POST", "settings", body=diff)
            print(f"PASS reading settings {json.dumps(diff)}")

        changes += ensure_shop_settings(wp, spec, args.apply)

        mode = "PASS" if args.apply else "DRY RUN ok:"
        print(f"{mode} {changes} change(s) on {env['NOSTEK_WP_URL']}")
        return 0
    except Fail as e:
        print(f"FAIL {e}", file=sys.stderr)
        return 1


if __name__ == "__main__":
    sys.exit(main())
