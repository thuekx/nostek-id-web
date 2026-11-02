# WEB-M1 — media server for nostek.id (host `nostek-media`)

Owner decision MEDIA-1 = A (2026-10-31): images live on their own VPS, not on the cPanel hosting.

| Item | Value |
|---|---|
| Script | `nostek-media-1.sh` rev 1, sha256 `ae28d01759387bac3a77046ac00900da1ffa60d99a2554714dfef6e519a0e270` |
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

Runbook for the owner: project file `website-build/runbook-06-media-vps.md`.
