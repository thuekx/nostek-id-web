#!/usr/bin/env python3
"""Publish one article JSON file to WordPress over the REST API.

Used by the Nostek virtual team (and by people) to push Journal articles.
Contract: docs/content-api.md. Standard library only.

Credentials come from the environment, never from argv:
  NOSTEK_WP_URL           e.g. https://staging.nostek.id
  NOSTEK_WP_USER          WordPress username of the agent
  NOSTEK_WP_APP_PASSWORD  Application Password of that user

Usage:
  publish_article.py article.json            # dry run: validate + resolve terms
  publish_article.py article.json --apply    # create or update the post
"""
import argparse
import base64
import json
import mimetypes
import os
import pathlib
import re
import sys
import urllib.error
import urllib.parse
import urllib.request

STATUSES = {"draft", "pending", "future", "publish"}
SLUG = re.compile(r"^[a-z0-9]+(?:-[a-z0-9]+)*$")


class Fail(Exception):
    pass


def load_article(path):
    p = pathlib.Path(path)
    try:
        a = json.loads(p.read_text(encoding="utf-8"))
    except (OSError, json.JSONDecodeError) as e:
        raise Fail(f"cannot read {path}: {e}")

    for key in ("slug", "title", "content", "category"):
        if not isinstance(a.get(key), str) or not a[key].strip():
            raise Fail(f"field '{key}' is required (non-empty string)")
    if not SLUG.match(a["slug"]):
        raise Fail("slug must be lowercase words joined by '-'")
    a.setdefault("status", "pending")
    if a["status"] not in STATUSES:
        raise Fail(f"status must be one of {sorted(STATUSES)}")
    if a["status"] == "future" and not a.get("date"):
        raise Fail("status 'future' needs 'date' (YYYY-MM-DDTHH:MM:SS, site timezone)")
    for key in ("topics", "tags"):
        a.setdefault(key, [])
        if not isinstance(a[key], list) or not all(isinstance(x, str) for x in a[key]):
            raise Fail(f"'{key}' must be a list of slugs")
    md = a.get("meta_description", "")
    if not isinstance(md, str) or len(md) > 160:
        raise Fail("meta_description must be a string of at most 160 characters")
    img = a.get("featured_image")
    if img:
        img_path = (p.parent / img).resolve()
        if not img_path.is_file():
            raise Fail(f"featured_image not found: {img_path}")
        a["_image_path"] = img_path
        if not a.get("featured_image_alt"):
            raise Fail("featured_image_alt is required when featured_image is set")
    return a


def wp_error(body):
    """Short text for a WordPress JSON error; a PHP fatal error keeps its message, file and line."""
    try:
        err = json.loads(body)
    except ValueError:
        return body[:300]
    if not isinstance(err, dict):
        return body[:300]
    text = f"{err.get('code', '')}: {re.sub(r'<[^>]+>', ' ', str(err.get('message', ''))).strip()[:200]}"
    fatal = (err.get("data") or {}).get("error") if isinstance(err.get("data"), dict) else None
    if isinstance(fatal, dict) and fatal.get("message"):
        text += f" | PHP: {str(fatal['message'])[:600]} (in {fatal.get('file', '?')}:{fatal.get('line', '?')})"
    return text


class WP:
    def __init__(self, base, user, password):
        self.base = base.rstrip("/") + "/wp-json/wp/v2/"
        token = base64.b64encode(f"{user}:{password}".encode()).decode()
        self.auth = f"Basic {token}"

    def call(self, method, path, query=None, body=None, raw=None, headers=None):
        url = self.base + path
        if query:
            url += "?" + urllib.parse.urlencode(query)
        h = {"Authorization": self.auth, "Accept": "application/json"}
        data = None
        if body is not None:
            data = json.dumps(body).encode()
            h["Content-Type"] = "application/json"
        if raw is not None:
            data = raw
        h.update(headers or {})
        req = urllib.request.Request(url, data=data, method=method, headers=h)
        try:
            with urllib.request.urlopen(req, timeout=60) as r:
                return json.loads(r.read() or b"null")
        except urllib.error.HTTPError as e:
            body = e.read().decode(errors="replace")
            if body.lstrip().startswith("<"):
                # WordPress answers in JSON; an HTML page comes from the web
                # server or its firewall (e.g. ModSecurity) before WordPress.
                raise Fail(f"{method} {path} -> HTTP {e.code} from the web server/firewall, not WordPress (HTML error page)")
            raise Fail(f"{method} {path} -> HTTP {e.code}: {wp_error(body)}")
        except urllib.error.URLError as e:
            raise Fail(f"{method} {path} -> {e.reason}")

    def term_id(self, rest_base, slug):
        found = self.call("GET", rest_base, {"slug": slug, "_fields": "id,slug"})
        if not found:
            raise Fail(f"{rest_base} '{slug}' does not exist (terms are created by editors, not by agents)")
        return found[0]["id"]

    def post_by_source_id(self, source_id):
        # The slug is not usable: WordPress drops it for users who cannot publish.
        found = self.call("GET", "posts", {
            "nostek_source_id": source_id, "status": "any", "context": "edit", "_fields": "id,status,link",
        })
        return found[0] if found else None

    def upload(self, path, alt):
        mime = mimetypes.guess_type(path.name)[0] or "application/octet-stream"
        media = self.call(
            "POST", "media", raw=path.read_bytes(),
            headers={"Content-Type": mime, "Content-Disposition": f'attachment; filename="{path.name}"'},
        )
        self.call("POST", f"media/{media['id']}", body={"alt_text": alt})
        return media["id"]


def main():
    ap = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument("article")
    ap.add_argument("--apply", action="store_true", help="write to WordPress (default: dry run)")
    args = ap.parse_args()

    try:
        env = {k: os.environ.get(k, "") for k in ("NOSTEK_WP_URL", "NOSTEK_WP_USER", "NOSTEK_WP_APP_PASSWORD")}
        missing = [k for k, v in env.items() if not v]
        if missing:
            raise Fail("missing environment variables: " + ", ".join(missing))

        a = load_article(args.article)
        wp = WP(env["NOSTEK_WP_URL"], env["NOSTEK_WP_USER"], env["NOSTEK_WP_APP_PASSWORD"])

        post = {
            "slug": a["slug"],
            "title": a["title"],
            "content": a["content"],
            "excerpt": a.get("excerpt", ""),
            "status": a["status"],
            "categories": [wp.term_id("categories", a["category"])],
            "topic": [wp.term_id("topic", s) for s in a["topics"]],
            "tags": [wp.term_id("tags", s) for s in a["tags"]],
            "meta": {"nostek_source_id": a["slug"]},
        }
        if a.get("date"):
            post["date"] = a["date"]
        if "meta_description" in a:
            post["meta"]["nostek_meta_description"] = a["meta_description"]
        existing = wp.post_by_source_id(a["slug"])
        action = f"update post {existing['id']}" if existing else "create post"

        if not args.apply:
            print(f"DRY RUN ok: would {action} '{a['slug']}' as {a['status']} on {env['NOSTEK_WP_URL']}")
            return 0

        # Upload the image on create, or on update only when asked, so re-sending
        # an article does not pile up copies in the media library.
        if a.get("_image_path") and (not existing or a.get("replace_featured_image")):
            post["featured_media"] = wp.upload(a["_image_path"], a["featured_image_alt"])
        if existing and existing["status"] == "publish":
            raise Fail(f"post {existing['id']} is already published; only an editor changes it now")
        if existing:
            res = wp.call("POST", f"posts/{existing['id']}", body=post)
        else:
            res = wp.call("POST", "posts", body=post)
        print(f"PASS {action}: id={res['id']} status={res['status']} link={res['link']}")
        return 0
    except Fail as e:
        print(f"FAIL {e}", file=sys.stderr)
        return 1


if __name__ == "__main__":
    sys.exit(main())
