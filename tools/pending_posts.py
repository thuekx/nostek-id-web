#!/usr/bin/env python3
"""List the articles of one agent that wait for review (or any other status).

Same credentials as publish_article.py, from the environment only:
  NOSTEK_WP_URL, NOSTEK_WP_USER, NOSTEK_WP_APP_PASSWORD

Usage:
  pending_posts.py                  # my posts in Pending Review
  pending_posts.py --status draft   # or draft, future, publish

Output: one line per post `PENDING id=… source_id=… modified=… title=…`,
then `PASS <n> post(s) <status> for <user>`; `FAIL …` and exit 1 on error.
"""
import argparse
import os
import sys

from publish_article import WP, Fail

STATUSES = ("pending", "draft", "future", "publish")


def main():
    ap = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument("--status", choices=STATUSES, default="pending")
    args = ap.parse_args()
    try:
        env = {k: os.environ.get(k, "") for k in ("NOSTEK_WP_URL", "NOSTEK_WP_USER", "NOSTEK_WP_APP_PASSWORD")}
        missing = [k for k, v in env.items() if not v]
        if missing:
            raise Fail("missing environment variables: " + ", ".join(missing))
        wp = WP(env["NOSTEK_WP_URL"], env["NOSTEK_WP_USER"], env["NOSTEK_WP_APP_PASSWORD"])

        me = wp.call("GET", "users/me", {"_fields": "id,slug"})
        page, count = 1, 0
        while True:
            posts = wp.call("GET", "posts", {
                "author": me["id"], "status": args.status, "context": "edit", "per_page": 100, "page": page,
                "orderby": "modified", "order": "desc", "_fields": "id,modified,title,meta,link",
            })
            for p in posts:
                count += 1
                source = (p.get("meta") or {}).get("nostek_source_id", "")
                title = p["title"]["raw"].replace("\n", " ")
                print(f"{args.status.upper()} id={p['id']} source_id={source} modified={p['modified']} title={title}")
            if len(posts) < 100:
                break
            page += 1
        print(f"PASS {count} post(s) {args.status} for {me['slug']}")
        return 0
    except Fail as e:
        print(f"FAIL {e}", file=sys.stderr)
        return 1


if __name__ == "__main__":
    sys.exit(main())
