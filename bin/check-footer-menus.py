#!/usr/bin/env python3
"""The footer's fallback links (theme file) match site/pages.json "menus".

Each footer Navigation block has a class nk-menu-<slug>. The plugin shows the
wp_navigation menu with that slug when it exists, otherwise the links written
in parts/footer.html. Both start from the same list, so a new site looks the
same before and after Site setup.
"""
import json
import pathlib
import re
import sys

ROOT = pathlib.Path(__file__).resolve().parent.parent
html = (ROOT / "themes/nostek/parts/footer.html").read_text(encoding="utf-8")
spec = {m["slug"]: m["items"] for m in json.loads((ROOT / "site/pages.json").read_text(encoding="utf-8")).get("menus", [])}

found = {}
for attrs, body in re.findall(r"<!-- wp:navigation (\{.*?\}) -->(.*?)<!-- /wp:navigation -->", html, re.S):
    m = re.search(r"(?:^|\s)nk-menu-([a-z0-9-]+)", json.loads(attrs).get("className", ""))
    if not m:
        continue
    links = [json.loads(a) for a in re.findall(r"<!-- wp:navigation-link (\{.*?\}) /-->", body)]
    found[m.group(1)] = [{"label": a["label"], "url": a["url"]} for a in links]

bad = sorted(set(found) ^ set(spec)) + sorted(k for k in found if k in spec and found[k] != spec[k])
if bad:
    print(f"FAIL footer menus differ from site/pages.json: {bad}")
    sys.exit(1)
print(f"PASS footer menus match site/pages.json ({len(found)} menus)")
