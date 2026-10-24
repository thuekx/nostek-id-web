# nostek-id-web

Custom code for [nostek.id](https://nostek.id): the **Nostek** block theme and the **Nostek Core** plugin.
WordPress core, uploads, third-party plugins, the database and all credentials stay on the server and never enter this repo.

## Layout

| Path | What |
|---|---|
| `themes/nostek/` | Block theme: `theme.json` (colors, fonts, spacing), templates, parts, patterns, `assets/css/retro.css` (textures, 3D title, ribbon, frames) |
| `plugins/nostek-core/` | Site structure that must survive a theme change: Topics taxonomy, breadcrumb and Topics list blocks, agent and publisher roles, SEO head tags, staging noindex, consent-gated GA4 (`docs/seo-analytics.md`) |
| `tools/publish_article.py` | Publishes an article JSON over the REST API (see `docs/content-api.md`); `tools/pending_posts.py` lists an agent's posts waiting for review |
| `.github/workflows/ci.yml` | PHP 8.1 lint, JSON, Python tools, block markup, gitleaks |
| `.github/workflows/deploy.yml` | Uploads theme + plugin over FTPS: `main` to staging, `production` to live |

## Branches and deploy

The host has no shell access, so deploys go over FTPS from GitHub Actions.

- `main` deploys to **staging.nostek.id** with an FTP account whose home is the staging `wp-content`.
- `production` deploys to **nostek.id** with a separate FTP account whose home is the live `wp-content`.
- Until the FTP secrets exist the workflow skips and uploads nothing. Any other branch is refused.
- Rollback = revert the commit; the next deploy uploads the previous files. JetBackup keeps daily server backups.

## Design

Retro editorial look recreated from scratch (no VintWood code or assets): Barlow Condensed (display), Merriweather (body), Yellowtail (ribbon script), all SIL OFL fonts bundled in `themes/nostek/assets/fonts/`.
Block styles editors can pick: Retro 3D heading, Section divider, Ribbon, Kicker, Double frame, Spray band, Info/Success/Error/Warning box, Sunburst, Ruled button, Accordion, Sepia image.

## Content model

- **Journal sections** = native categories: News, Insights, Guides & Tutorials, Reviews, Founder's Note.
- **Topics** = `topic` taxonomy (`/topics/<slug>/`), with a short description shown on the Topics list and archive page.
- Contributors (WordPress role *Contributor*) can write drafts and pick Topics; only editors create Topics or publish.
- Virtual-team agents use the *Nostek Agent* role and publish through the REST API: [docs/content-api.md](docs/content-api.md).
