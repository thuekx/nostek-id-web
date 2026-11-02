#!/usr/bin/env python3
"""Offline test for mirror_content.py: a fake production and a fake staging WordPress.

Run: python3 tools/test_mirror_content.py   -> "RESULT PASS n/n" (exit 0)
"""
import json
import os
import pathlib
import subprocess
import sys
import threading
import urllib.parse
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer

TOOL = pathlib.Path(__file__).resolve().parent / "mirror_content.py"


class State:
    def __init__(self):
        self.prod_posts = {
            11: dict(id=11, slug="ai-di-noc", date="2026-11-02T09:00:00", modified="2026-11-02T09:00:00",
                     title={"rendered": "AI di NOC &amp; jaringan"}, content={"rendered": "<p>Isi</p>"},
                     excerpt={"rendered": "<p>Ringkas</p>"}, categories=[5], topic=[7], tags=[], featured_media=90,
                     status="publish"),
            12: dict(id=12, slug="demo-lama", date="2026-11-03T09:00:00", modified="2026-11-03T09:00:00",
                     title={"rendered": "Demo"}, content={"rendered": "x"}, excerpt={"rendered": ""},
                     categories=[6], topic=[], tags=[], featured_media=0, status="publish"),
        }
        self.staging_posts = {
            # a copy whose production article was removed
            500: dict(id=500, status="publish", meta={"nostek_source_id": "mirror-99", "nostek_mirror_state": "x|0"}),
            # an article written on staging itself: never touched
            501: dict(id=501, status="publish", meta={"nostek_source_id": "fixture-blocks", "nostek_mirror_state": ""}),
        }
        self.next_id = 600
        self.uploads = 0
        self.writes = []


S = State()


def make_handler(kind):
    class H(BaseHTTPRequestHandler):
        def log_message(self, *a):
            pass

        def send(self, code, body, headers=None, raw=False):
            data = body if raw else json.dumps(body).encode()
            self.send_response(code)
            self.send_header("Content-Type", "image/webp" if raw else "application/json")
            for k, v in (headers or {}).items():
                self.send_header(k, v)
            self.send_header("Content-Length", str(len(data)))
            self.end_headers()
            self.wfile.write(data)

        def route(self):
            u = urllib.parse.urlparse(self.path)
            return u.path.replace("/wp-json/wp/v2/", ""), dict(urllib.parse.parse_qsl(u.query))

        def do_GET(self):
            path, q = self.route()
            if kind == "prod":
                if "Authorization" in self.headers:
                    return self.send(400, {"code": "prod_got_credentials"})
                if path == "categories":
                    return self.send(200, [{"id": 5, "slug": "insights"}, {"id": 6, "slug": "uncategorized"}])
                if path == "topic":
                    return self.send(200, [{"id": 7, "slug": "ai"}])
                if path == "tags":
                    return self.send(200, [])
                if path == "posts":
                    return self.send(200, list(S.prod_posts.values()), {"X-WP-TotalPages": "1"})
                if path.startswith("posts/"):
                    p = S.prod_posts.get(int(path.split("/")[1]))
                    return self.send(200, p) if p else self.send(404, {"code": "rest_post_invalid_id"})
                if path == "media/90":
                    return self.send(200, {"source_url": f"http://{self.headers['Host']}/img/cover.webp", "alt_text": "Sampul"})
                if path == "/img/cover.webp":
                    return self.send(200, b"RIFFfakewebp", raw=True)
                return self.send(404, {})
            # staging
            if path == "posts" and "nostek_source_id" in q:
                return self.send(200, [{"id": p["id"], "status": p["status"]} for p in S.staging_posts.values()
                                       if p["meta"].get("nostek_source_id") == q["nostek_source_id"]])
            if path.startswith("posts/"):
                return self.send(200, S.staging_posts[int(path.split("/")[1])])
            if path == "posts":
                if q.get("page", "1") != "1":
                    return self.send(200, [])
                return self.send(200, list(S.staging_posts.values()))
            if path in ("categories", "topic", "tags"):
                known = {"categories": {"insights": 3, "guides-tutorials": 8, "news": 9}, "topic": {"ai": 4}, "tags": {}}[path]
                slug = q.get("slug")
                return self.send(200, [{"id": known[slug]}] if slug in known else [])
            return self.send(404, {})

        def do_POST(self):
            path, _ = self.route()
            body = self.rfile.read(int(self.headers.get("Content-Length", 0)))
            if kind != "staging":
                return self.send(405, {})
            if path == "media":
                S.uploads += 1
                return self.send(201, {"id": 900 + S.uploads})
            if path.startswith("media/"):
                return self.send(200, {})
            data = json.loads(body)
            S.writes.append((path, data))
            if path == "posts":
                pid = S.next_id
                S.next_id += 1
            else:
                pid = int(path.split("/")[1])
            S.staging_posts[pid] = dict(id=pid, status=data["status"], meta=data["meta"])
            return self.send(201, {"id": pid, "link": f"https://staging.example/{data['slug']}/"})

        def do_DELETE(self):
            path, _ = self.route()
            pid = int(path.split("/")[1])
            S.staging_posts[pid]["status"] = "trash"
            return self.send(200, {"id": pid})

    return H


def serve(kind):
    srv = ThreadingHTTPServer(("127.0.0.1", 0), make_handler(kind))
    threading.Thread(target=srv.serve_forever, daemon=True).start()
    return f"http://127.0.0.1:{srv.server_address[1]}"


def run(args, prod, staging):
    env = dict(os.environ, NOSTEK_PROD_URL=prod, NOSTEK_WP_URL=staging, NOSTEK_WP_USER="u",
               NOSTEK_WP_APP_PASSWORD="fake-test-only", MIRROR_SINCE="2026-11-01", no_proxy="*", NO_PROXY="*")
    r = subprocess.run([sys.executable, "-I", str(TOOL), *args], env=env, capture_output=True, text=True, timeout=60)
    return r.returncode, r.stdout + r.stderr


def main():
    prod = serve("prod")
    st = serve("staging")  # 127.0.0.1 is the only non-"staging." host the tool accepts
    checks = []

    rc, out = run([], prod, st)
    checks.append(("dry run passes and writes nothing", rc == 0 and "RESULT PASS (dry run)" in out and not S.writes))
    checks.append(("non-Journal post skipped", "SKIP not a Journal article: prod id=12" in out))

    rc, out = run(["--apply", "--prune"], prod, st)
    checks.append(("apply creates one copy", rc == 0 and "created=1" in out and len(S.writes) == 1))
    w = S.writes[0][1]
    checks.append(("title unescaped, published, term mapped", w["title"] == "AI di NOC & jaringan"
                   and w["status"] == "publish" and w["categories"] == [3] and w["topic"] == [4]))
    checks.append(("source id + state stored", w["meta"] == {"nostek_source_id": "mirror-11",
                                                             "nostek_mirror_state": "2026-11-02T09:00:00|90"}))
    checks.append(("image uploaded once", S.uploads == 1 and w.get("featured_media") == 901))
    checks.append(("gone copy trashed, staging-own post kept",
                   S.staging_posts[500]["status"] == "trash" and S.staging_posts[501]["status"] == "publish"))

    rc, out = run(["--apply", "--prune"], prod, st)
    checks.append(("second run unchanged, no new upload", rc == 0 and "unchanged=1" in out
                   and "created=0" in out and S.uploads == 1 and len(S.writes) == 1))

    S.prod_posts[11]["modified"] = "2026-11-04T10:00:00"
    rc, out = run(["--apply"], prod, st)
    checks.append(("changed article updated, same image not re-uploaded", rc == 0 and "updated=1" in out
                   and S.writes[-1][0] == "posts/600" and S.uploads == 1))

    rc, out = run(["--apply"], st, st)
    checks.append(("refuses when staging URL = production URL", rc == 1 and "REFUSE" in out))
    rc, out = run(["--apply"], prod, prod.replace("127.0.0.1", "localhost"))
    checks.append(("refuses a non-staging host", rc == 1 and "REFUSE: NOSTEK_WP_URL is not a staging site" in out))

    fx = str(pathlib.Path(__file__).resolve().parent.parent / "site" / "fixtures")
    n_fx = len(list(pathlib.Path(fx).glob("*.json")))
    before_w, before_up = len(S.writes), S.uploads
    rc, out = run(["--apply", "--fixtures", fx], prod, st)
    checks.append(("fixtures created and published", rc == 0 and f"created={n_fx}" in out
                   and all(d["status"] == "publish" and d["slug"].startswith("fixture-") for _, d in S.writes[before_w:])))
    checks.append(("fixture images uploaded on create only", S.uploads - before_up == 2))
    rc, out = run(["--apply", "--fixtures", fx], prod, st)
    checks.append(("fixtures unchanged on second run", rc == 0 and f"unchanged={n_fx + 1}" in out
                   and len(S.writes) == before_w + n_fx))

    ok = 0
    for name, passed in checks:
        print(("ok   " if passed else "FAIL ") + name)
        ok += passed
    print(f"RESULT {'PASS' if ok == len(checks) else 'FAIL'} {ok}/{len(checks)}")
    return 0 if ok == len(checks) else 1


if __name__ == "__main__":
    sys.exit(main())
