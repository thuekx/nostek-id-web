#!/usr/bin/env python3
"""Upload one image to the WordPress Media Library with alt text and caption.

The images agent (hassan-nostek) uses it; the writer then puts the media id
in an article's featured_media. Contract: docs/content-api.md. Standard
library only.

Credentials come from the environment, never from argv:
  NOSTEK_WP_URL, NOSTEK_WP_USER, NOSTEK_WP_APP_PASSWORD

Usage:
  upload_media.py cover.webp --alt "…" --caption "Foto: Nostek (own work)"            # dry run: checks only
  upload_media.py cover.webp --alt "…" --caption "Foto: Nostek (own work)" --apply    # upload

Checks (image rule of 2026-10-31): WebP only, long side 1600-2400 px,
under 400 KB, alt text required, caption (source or licence) required.
Output: `DRY RUN ok: …`, `PASS media id=… url=… size=WxH bytes=…` or `FAIL …` (exit 1).
"""
import argparse
import os
import pathlib
import struct
import sys

from publish_article import WP, Fail

MAX_BYTES = 400 * 1024
LONG_SIDE = (1600, 2400)


def webp_size(data):
    """Width and height from a WebP header (VP8, VP8L or VP8X), or Fail."""
    if len(data) < 30 or data[:4] != b"RIFF" or data[8:12] != b"WEBP":
        raise Fail("not a WebP file (convert to WebP first)")
    chunk = data[12:16]
    if chunk == b"VP8X":
        w = 1 + int.from_bytes(data[24:27], "little")
        h = 1 + int.from_bytes(data[27:30], "little")
    elif chunk == b"VP8 ":
        if data[23:26] != b"\x9d\x01\x2a":
            raise Fail("WebP VP8 header is damaged")
        w, h = struct.unpack("<HH", data[26:30])
        w, h = w & 0x3FFF, h & 0x3FFF
    elif chunk == b"VP8L":
        if data[20] != 0x2F:
            raise Fail("WebP VP8L header is damaged")
        bits = int.from_bytes(data[21:25], "little")
        w, h = (bits & 0x3FFF) + 1, ((bits >> 14) & 0x3FFF) + 1
    else:
        raise Fail(f"unknown WebP chunk {chunk!r}")
    return w, h


def check(path, alt, caption):
    p = pathlib.Path(path)
    if not p.is_file():
        raise Fail(f"file not found: {path}")
    data = p.read_bytes()
    w, h = webp_size(data)
    if not LONG_SIDE[0] <= max(w, h) <= LONG_SIDE[1]:
        raise Fail(f"long side is {max(w, h)} px; must be {LONG_SIDE[0]}-{LONG_SIDE[1]} px")
    if len(data) >= MAX_BYTES:
        raise Fail(f"{len(data) // 1024} KB; must be under {MAX_BYTES // 1024} KB")
    if not alt.strip():
        raise Fail("--alt is required (describe the image, Indonesian)")
    if not caption.strip():
        raise Fail("--caption is required (source or licence: own work, stock licence id, or AI-generated)")
    return p, data, w, h


def main():
    ap = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument("image")
    ap.add_argument("--alt", required=True)
    ap.add_argument("--caption", required=True)
    ap.add_argument("--title", default="", help="media title (default: file name)")
    ap.add_argument("--apply", action="store_true", help="upload (default: dry run)")
    args = ap.parse_args()
    try:
        p, data, w, h = check(args.image, args.alt, args.caption)
        if not args.apply:
            print(f"DRY RUN ok: {p.name} {w}x{h} {len(data)} bytes")
            return 0

        env = {k: os.environ.get(k, "") for k in ("NOSTEK_WP_URL", "NOSTEK_WP_USER", "NOSTEK_WP_APP_PASSWORD")}
        missing = [k for k, v in env.items() if not v]
        if missing:
            raise Fail("missing environment variables: " + ", ".join(missing))
        wp = WP(env["NOSTEK_WP_URL"], env["NOSTEK_WP_USER"], env["NOSTEK_WP_APP_PASSWORD"])

        media = wp.call(
            "POST", "media", raw=data,
            headers={"Content-Type": "image/webp", "Content-Disposition": f'attachment; filename="{p.name}"'},
        )
        meta = {"alt_text": args.alt, "caption": args.caption}
        if args.title:
            meta["title"] = args.title
        wp.call("POST", f"media/{media['id']}", body=meta)
        print(f"PASS media id={media['id']} url={media['source_url']} size={w}x{h} bytes={len(data)}")
        return 0
    except Fail as e:
        print(f"FAIL {e}", file=sys.stderr)
        return 1


if __name__ == "__main__":
    sys.exit(main())
