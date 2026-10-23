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
print("FAIL" if failed else "PASS block markup balanced")
sys.exit(1 if failed else 0)
