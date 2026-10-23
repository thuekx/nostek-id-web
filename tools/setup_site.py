#!/usr/bin/env python3
"""Create the site's base pages and reading settings over the REST API.

Reads site/pages.json. Pages that already exist (same slug) are left as they
are, so editors' changes are never overwritten. Then sets Settings > Reading:
a static front page and the Journal as the posts page. Standard library only.

Credentials come from the environment, never from argv. The user must be an
Administrator (reading settings need manage_options):
  NOSTEK_WP_URL           e.g. https://staging.nostek.id
  NOSTEK_WP_USER          WordPress username
  NOSTEK_WP_APP_PASSWORD  Application Password of that user

Usage:
  setup_site.py                 # dry run: show what would change
  setup_site.py --apply         # create missing pages, set reading settings
"""
import argparse
import json
import os
import pathlib
import sys

sys.path.insert(0, str(pathlib.Path(__file__).resolve().parent))
from publish_article import WP, Fail  # noqa: E402

SPEC = pathlib.Path(__file__).resolve().parent.parent / "site" / "pages.json"


def load_spec(path):
    try:
        spec = json.loads(path.read_text(encoding="utf-8"))
    except (OSError, json.JSONDecodeError) as e:
        raise Fail(f"cannot read {path}: {e}")
    slugs = [p["slug"] for p in spec["pages"]]
    for key in ("front_page", "posts_page"):
        if spec.get(key) not in slugs:
            raise Fail(f"'{key}' must name one of the pages: {slugs}")
    return spec


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

        ids = {}
        changes = 0
        for page in spec["pages"]:
            found = wp.call("GET", "pages", {
                "slug": page["slug"], "status": "publish,draft,pending,private,future",
                "context": "edit", "_fields": "id,status",
            })
            if found:
                ids[page["slug"]] = found[0]["id"]
                print(f"SKIP page '{page['slug']}' exists (id={found[0]['id']}, {found[0]['status']})")
                continue
            changes += 1
            if not args.apply:
                print(f"PLAN create page '{page['slug']}'")
                continue
            res = wp.call("POST", "pages", body={
                "slug": page["slug"], "title": page["title"], "content": page["content"], "status": "publish",
            })
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

        mode = "PASS" if args.apply else "DRY RUN ok:"
        print(f"{mode} {changes} change(s) on {env['NOSTEK_WP_URL']}")
        return 0
    except Fail as e:
        print(f"FAIL {e}", file=sys.stderr)
        return 1


if __name__ == "__main__":
    sys.exit(main())
