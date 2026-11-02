#!/usr/bin/env python3
"""Copy published Journal articles from production to staging (one way).

Keeps staging comparable with nostek.id, so a theme change can be checked on
staging against the same articles before it goes to production.

- Production is only READ, through its public REST API: no production
  credential exists in this tool.
- Staging is written with a staging Administrator's Application Password.
- Only Journal articles are copied: posts whose production category is one of
  the Journal sections (see JOURNAL) and published on/after --since.
- Each copy carries nostek_source_id = "mirror-<production id>"; an unchanged
  article (same production modified time and image) is skipped, a changed one is
  updated, a new one is created as published on staging.
- --prune moves staging copies whose production article is gone (404) or no
  longer published to the Trash. Articles written on staging itself are never touched.
- --fixtures DIR also keeps a fixed set of staging-only test articles
  (publish_article.py format, slugs "fixture-…") published on staging, so rare
  layouts (every block style, long titles, no image) are always there to check.
- Content is production's rendered HTML (the public API has no raw blocks);
  inline images keep pointing at production / the media server.

Environment (never argv):
  NOSTEK_PROD_URL         default https://nostek.id
  NOSTEK_WP_URL           staging base, e.g. https://staging.nostek.id
  NOSTEK_WP_USER          staging Administrator username
  NOSTEK_WP_APP_PASSWORD  that user's Application Password

Usage:
  mirror_content.py                      # dry run: list what would change
  mirror_content.py --apply [--prune] [--fixtures site/fixtures]   # write to staging
Output: one line per article (CREATE / UPDATE / SKIP / TRASH / WARN), then
  RESULT PASS created=… updated=… unchanged=… trashed=… skipped=… warn=…
or RESULT FAIL … (exit 1).
"""
import argparse
import hashlib
import html
import json
import os
import pathlib
import sys
import urllib.error
import urllib.parse
import urllib.request

sys.path.insert(0, str(pathlib.Path(__file__).resolve().parent))
from publish_article import WP, Fail, load_article  # noqa: E402

JOURNAL = {"news", "insights", "guides-tutorials", "reviews", "founders-note", "retro-stories", "price-index"}
PREFIX = "mirror-"
UA = "nostek-content-mirror/1"


class Public:
    """Anonymous reader for the production REST API."""

    def __init__(self, base):
        self.root = base.rstrip("/")
        self.base = self.root + "/wp-json/wp/v2/"

    def get(self, path, query=None):
        url = self.base + path + ("?" + urllib.parse.urlencode(query) if query else "")
        return self._fetch(url)

    def _fetch(self, url, raw=False):
        req = urllib.request.Request(url, headers={"Accept": "application/json", "User-Agent": UA})
        try:
            with urllib.request.urlopen(req, timeout=60) as r:
                data = r.read()
                if raw:
                    return data, r.headers.get("Content-Type", "application/octet-stream")
                return json.loads(data or b"null"), r.headers
        except urllib.error.HTTPError as e:
            raise Fail(f"production GET {url} -> HTTP {e.code}")
        except urllib.error.URLError as e:
            raise Fail(f"production GET {url} -> {e.reason}")
        except ValueError:
            raise Fail(f"production GET {url} -> not JSON (maintenance page or firewall?)")

    def all(self, path, query):
        out, page = [], 1
        while True:
            q = dict(query, per_page=100, page=page)
            items, headers = self.get(path, q)
            if not isinstance(items, list):
                raise Fail(f"production GET {path} -> unexpected answer")
            out.extend(items)
            pages = int(headers.get("X-WP-TotalPages", "1") or 1)
            if page >= pages:
                return out
            page += 1

    def download(self, url):
        return self._fetch(url, raw=True)


def prod_published(prod, prod_id):
    try:
        p, _ = prod.get(f"posts/{prod_id}", {"_fields": "id,status"})
    except Fail as e:
        if "HTTP 404" in str(e) or "HTTP 401" in str(e):
            return False
        raise
    return (p or {}).get("status") == "publish"


def sync_fixtures(wp, folder, apply, counts):
    files = sorted(pathlib.Path(folder).glob("*.json"))
    if not files:
        raise Fail(f"no fixture files in {folder}")
    for f in files:
        a = load_article(f)
        if not a["slug"].startswith("fixture-"):
            raise Fail(f"{f.name}: fixture slugs must start with 'fixture-'")
        digest = hashlib.sha256(f.read_bytes()).hexdigest()[:16]
        have = wp.post_by_source_id(a["slug"])
        if have:
            full = wp.call("GET", f"posts/{have['id']}", {"context": "edit", "_fields": "id,status,meta"})
            if (full.get("meta") or {}).get("nostek_mirror_state") == digest and full["status"] == "publish":
                counts["unchanged"] += 1
                continue
        action = "UPDATE" if have else "CREATE"
        counts["updated" if have else "created"] += 1
        if not apply:
            print(f"{action} (dry run) fixture {a['slug']}")
            continue
        body = {
            "slug": a["slug"], "title": a["title"], "content": a["content"], "excerpt": a.get("excerpt", ""),
            "status": "publish",
            "categories": [wp.term_id("categories", a["category"])],
            "topic": [wp.term_id("topic", t) for t in a["topics"]],
            "tags": [wp.term_id("tags", t) for t in a["tags"]],
            "meta": {"nostek_source_id": a["slug"], "nostek_mirror_state": digest},
        }
        if a.get("_image_path") and not have:
            body["featured_media"] = wp.upload(a["_image_path"], a["featured_image_alt"])
        res = wp.call("POST", f"posts/{have['id']}" if have else "posts", body=body)
        print(f"{action} fixture {a['slug']} -> staging id={res['id']} {res.get('link', '')}")


def text(field):
    return html.unescape((field or {}).get("rendered", "")).strip()


def staging_copies(wp):
    """All staging posts made by this tool: {source_id: post}."""
    out, page = {}, 1
    while True:
        items = wp.call("GET", "posts", {
            "status": "any", "context": "edit", "per_page": 100, "page": page,
            "_fields": "id,status,meta"
        })
        for p in items or []:
            sid = (p.get("meta") or {}).get("nostek_source_id", "")
            if sid.startswith(PREFIX):
                out[sid] = p
        if not items or len(items) < 100:
            return out
        page += 1


def term_map(prod, wp, rest_base, ids, warns, title):
    """Production term ids -> staging term ids, matched by slug."""
    out = []
    for tid in ids:
        slug = prod.slugs[rest_base].get(tid)
        if slug is None:
            continue
        found = wp.call("GET", rest_base, {"slug": slug, "_fields": "id"})
        if found:
            out.append(found[0]["id"])
        else:
            warns.append(f"WARN {title!r}: {rest_base} '{slug}' missing on staging (run Site setup); left out")
    return out


def main():
    ap = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument("--apply", action="store_true", help="write to staging (default: dry run)")
    ap.add_argument("--prune", action="store_true", help="trash staging copies whose production article is gone")
    ap.add_argument("--fixtures", metavar="DIR", help="also keep the staging-only fixture articles in DIR published")
    ap.add_argument("--since", default=os.environ.get("MIRROR_SINCE", "2026-11-01"),
                    help="only articles published on/after this date (YYYY-MM-DD, default 2026-11-01 = launch)")
    args = ap.parse_args()
    counts = dict(created=0, updated=0, unchanged=0, trashed=0, skipped=0, warn=0)
    try:
        env = {k: os.environ.get(k, "") for k in ("NOSTEK_WP_URL", "NOSTEK_WP_USER", "NOSTEK_WP_APP_PASSWORD")}
        missing = [k for k, v in env.items() if not v]
        if missing:
            raise Fail("missing environment variables: " + ", ".join(missing))
        prod_url = os.environ.get("NOSTEK_PROD_URL", "https://nostek.id")
        if prod_url.rstrip("/") == env["NOSTEK_WP_URL"].rstrip("/"):
            raise Fail("REFUSE: staging URL equals production URL; this tool never writes to production")
        host = urllib.parse.urlparse(env["NOSTEK_WP_URL"]).hostname or ""
        if not (host.startswith("staging.") or host == "127.0.0.1"):  # 127.0.0.1 = offline test only
            raise Fail("REFUSE: NOSTEK_WP_URL is not a staging site")

        prod = Public(prod_url)
        wp = WP(env["NOSTEK_WP_URL"], env["NOSTEK_WP_USER"], env["NOSTEK_WP_APP_PASSWORD"])
        prod.slugs = {}
        for rest_base in ("categories", "topic", "tags"):
            prod.slugs[rest_base] = {t["id"]: t["slug"] for t in prod.all(rest_base, {"_fields": "id,slug"})}
        journal_ids = {i for i, s in prod.slugs["categories"].items() if s in JOURNAL}

        posts = prod.all("posts", {
            "after": f"{args.since}T00:00:00",
            "_fields": "id,slug,date,modified,title,content,excerpt,categories,topic,tags,featured_media",
        }) if journal_ids else []
        copies = staging_copies(wp)
        seen = set()
        for p in posts:
            title = text(p.get("title"))
            if not journal_ids.intersection(p.get("categories") or []):
                counts["skipped"] += 1
                print(f"SKIP not a Journal article: prod id={p['id']} {title!r}")
                continue
            sid = f"{PREFIX}{p['id']}"
            seen.add(sid)
            state = f"{p['modified']}|{p.get('featured_media') or 0}"
            have = copies.get(sid)
            if have and (have.get("meta") or {}).get("nostek_mirror_state") == state and have["status"] == "publish":
                counts["unchanged"] += 1
                continue
            warns = []
            body = {
                "slug": p["slug"],
                "title": title,
                "content": (p.get("content") or {}).get("rendered", ""),
                "excerpt": text(p.get("excerpt")),
                "date": p["date"],
                "status": "publish",
                "categories": term_map(prod, wp, "categories", p.get("categories") or [], warns, title),
                "topic": term_map(prod, wp, "topic", p.get("topic") or [], warns, title),
                "tags": term_map(prod, wp, "tags", p.get("tags") or [], warns, title),
                "meta": {"nostek_source_id": sid, "nostek_mirror_state": state},
            }
            for w in warns:
                print(w)
            counts["warn"] += len(warns)
            action = "UPDATE" if have else "CREATE"
            if not args.apply:
                print(f"{action} (dry run) prod id={p['id']} {title!r}")
                counts["updated" if have else "created"] += 1
                continue
            old_media = ((have or {}).get("meta") or {}).get("nostek_mirror_state", "|").split("|")[-1]
            if p.get("featured_media") and str(p["featured_media"]) != old_media:
                media, _ = prod.get(f"media/{p['featured_media']}", {"_fields": "source_url,alt_text"})
                data, ctype = prod.download(media["source_url"])
                name = urllib.parse.urlparse(media["source_url"]).path.rsplit("/", 1)[-1] or "image"
                up = wp.call("POST", "media", raw=data, headers={
                    "Content-Type": ctype.split(";")[0], "Content-Disposition": f'attachment; filename="{name}"'})
                wp.call("POST", f"media/{up['id']}", body={"alt_text": media.get("alt_text", "")})
                body["featured_media"] = up["id"]
            elif not p.get("featured_media"):
                body["featured_media"] = 0
            res = wp.call("POST", f"posts/{have['id']}" if have else "posts", body=body)
            counts["updated" if have else "created"] += 1
            print(f"{action} prod id={p['id']} -> staging id={res['id']} {res.get('link', '')}")

        if args.prune:
            for sid, have in copies.items():
                if sid in seen or have["status"] == "trash":
                    continue
                if prod_published(prod, sid[len(PREFIX):]):
                    continue  # older than --since but still live: keep
                counts["trashed"] += 1
                if args.apply:
                    wp.call("DELETE", f"posts/{have['id']}")
                print(f"TRASH{' (dry run)' if not args.apply else ''} staging id={have['id']} ({sid} no longer published on production)")

        if args.fixtures:
            sync_fixtures(wp, args.fixtures, args.apply, counts)

        mode = "" if args.apply else " (dry run)"
        print("RESULT PASS" + mode + " " + " ".join(f"{k}={v}" for k, v in counts.items()))
        return 0
    except Fail as e:
        print(f"RESULT FAIL {e}", file=sys.stderr)
        return 1


if __name__ == "__main__":
    sys.exit(main())
