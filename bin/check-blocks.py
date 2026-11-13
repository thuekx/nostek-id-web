#!/usr/bin/env python3
"""Fail when block comments in templates, parts or patterns are unbalanced."""
import pathlib
import re
import sys

ROOT = pathlib.Path(__file__).resolve().parent.parent
TOKEN = re.compile(r"<!--\s+(/?)wp:([a-z0-9/-]+)(?:\s+(\{.*?\}))?\s*(/?)-->", re.S)

failed = False
for path in sorted(ROOT.glob("themes/*/[tp]*/*.*")):
    if path.suffix not in {".html", ".php"}:
        continue
    stack = []
    for m in TOKEN.finditer(path.read_text(encoding="utf-8")):
        closing, name, _attrs, selfclose = m.groups()
        if selfclose:
            continue
        if closing:
            if not stack or stack[-1] != name:
                print(f"FAIL {path.relative_to(ROOT)}: unexpected </{name}> (open: {stack})")
                failed = True
                break
            stack.pop()
        else:
            stack.append(name)
    else:
        if stack:
            print(f"FAIL {path.relative_to(ROOT)}: unclosed {stack}")
            failed = True
# Journal categories live at /category/<slug>/; /journal/<slug>/ is a 404
# (the founders-note button pointed there until WEB-39).
BAD_LINK = re.compile(r"""href=(?:\\?["'])[^"'\\]*?/journal/[a-z0-9-]+/""")
for path in sorted(ROOT.glob("themes/*/[tp]*/*.*")) + [ROOT / "site/pages.json"]:
    for m in BAD_LINK.finditer(path.read_text(encoding="utf-8")):
        print(f"FAIL {path.relative_to(ROOT)}: {m.group(0)} -> use /category/<slug>/")
        failed = True
print("FAIL" if failed else "PASS block markup balanced, no /journal/<category>/ links")
sys.exit(1 if failed else 0)
