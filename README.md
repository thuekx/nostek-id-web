# nostek-id-web

Custom code for [nostek.id](https://nostek.id): the **Nostek** block theme and the **Nostek Core** plugin.
WordPress core, uploads, third-party plugins, the database and all credentials stay on the server and never enter this repo.

## Layout

| Path | What |
|---|---|
| `themes/nostek/` | Block theme: `theme.json` (colors, fonts, spacing), templates, parts, patterns, `assets/css/retro.css` (textures, 3D title, ribbon, frames) |
| `plugins/nostek-core/` | Site structure that must survive a theme change: Topics taxonomy, breadcrumb block, Topics list block |
| `bin/deploy.sh` | Copies theme + plugin into the WordPress install for the checked-out branch, keeps the previous copy for rollback |
| `.cpanel.yml` | cPanel Git Version Control runs `bin/deploy.sh` on "Deploy HEAD Commit" |
| `.github/workflows/ci.yml` | PHP 8.1 lint, JSON, block markup, shellcheck, gitleaks |
| `.github/workflows/deploy-production.yml` | On push to `production`: cPanel backup, pull, deploy (needs owner-set secrets) |

## Branches and deploy

- `main` deploys to **staging.nostek.id** (cPanel clone tracking `main`, owner clicks Deploy).
- `production` deploys to **nostek.id** (merge `main` into `production`; the workflow runs once secrets exist).
- Any other branch: `bin/deploy.sh` refuses and changes nothing.

## Design

Retro editorial look recreated from scratch (no VintWood code or assets): Barlow Condensed (display), Merriweather (body), Yellowtail (ribbon script), all SIL OFL fonts bundled in `themes/nostek/assets/fonts/`.
Block styles editors can pick: Retro 3D heading, Section divider, Ribbon, Kicker, Double frame, Spray band, Info/Success/Error/Warning box, Sunburst, Ruled button, Accordion, Sepia image.

## Content model

- **Journal sections** = native categories: News, Insights, Guides & Tutorials, Reviews, Founder's Note.
- **Topics** = `topic` taxonomy (`/topics/<slug>/`), with a short description shown on the Topics list and archive page.
- Contributors (WordPress role *Contributor*) can write drafts and pick Topics; only editors create Topics or publish.
