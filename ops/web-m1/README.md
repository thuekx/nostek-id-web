# WEB-M1 — media server for nostek.id (host `nostek-media`)

Owner decision MEDIA-1 = A (2026-10-31): images live on their own VPS, not on the cPanel hosting.

| Item | Value |
|---|---|
| Script | `nostek-media-1.sh` rev 2, sha256 `4af106caa2cceba01d4926cd57895903c6f399e9ac22c2e3a49de992d421af6a` (rev 1 `ae28d017…`, commit `82a924c5`) |
| Host | `nostek-media` (Ubuntu 24.04, Jakarta DC, 2 vCPU / 4 GB / 250 GB+) |
| Public names | `media.nostek.id` (read-only images, `/i/insecure/<preset>/plain/<key>` = resized WebP), `s3-media.nostek.id` (S3 API for WordPress uploads) |
| Images | Garage v2.5.0 `sha256:677e5b75…`, imgproxy v4.0.20 `sha256:f5db62d5…`, Caddy 2.11.7-alpine `sha256:e70b164d…` (pulled by digest) |
| Presets | w400, w800, w1600, w2400 (fit, WebP); free-form options refused |
| Ports | only Caddy: 80, 443 (tcp + udp). Garage 3900–3903 and imgproxy stay inside Docker |
| Secrets | generated on the host or read with `--stdin`; root-only files in `/etc/nostek-media`; never in argv, output or log |
| Backup | restic to an off-site repository, nightly 03:30 WIB timer; BACKUP restores the snapshot into a temporary Garage and checks the canary (rebuild proof) |
| Modes | PREFLIGHT (read-only) → APPLY (auto-rollback) → BACKUP-SETUP → BACKUP → VERIFY; WPKEY shows the WordPress key on the terminal only |

## Harness
`sudo bash ops/web-m1/h/e2e.sh` runs the real images in Docker (fakes only for hostname, timedatectl, systemctl,
free space; TLS off). Last run 2026-10-31: `RESULT e2e PASS 20/20`; mutants (presets off, restore broken,
host check off) all killed.
Rev 2 (2026-11-08): media site also serves `/<bucket>/<key>` (path-style URLs written by the WordPress offload plugin),
APPLY restarts Caddy when the Caddyfile changes, BACKUP-SETUP refuses `< > " ' ` $ \` in values and `backup.env` is read as data,
not sourced. Last run 2026-11-08: `RESULT e2e PASS 25/25` (incl. upgrade from a running rev 1).

Runbook for the owner: project file `website-build/runbook-06-media-vps.md`.
