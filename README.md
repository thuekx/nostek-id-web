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
Block styles editors can pick: Retro 3D heading, Section divider, Ribbon, Kicker, Double frame, Spray band, Info/Success/Error/Warning box, Sunburst, Slider, Carousel, Ruled button, Accordion, Sepia image.

Retro Tech Editorial (WEB-13, `assets/css/editorial.css`): brand lockup from the NOSTEK ID asset pack (N mark SVG + live wordmark, favicon and touch icon until a Site Icon is set), double-framed brand hero on the front page, journal cards (cream card, terracotta pill category badge, halftone/duotone thumbnail, "Baca artikel" link), monospace dates and meta, teal double underline on the active nav item, terracotta buttons, and a web dark mode (system setting or the header switch, stored per visitor).

Motion layer (`assets/js/motion.js` + `assets/css/motion.css`, no library, about 8 KB gzipped): sticky header that compacts on scroll, mega menu for Journal and Topics (core Navigation submenus with link descriptions), hero slider of the latest articles, page banner slider, "Baca Juga" carousel, news ticker, scroll reveal, card hover effects, slowly turning sunburst, back-to-top button and reading progress bar.
Every feature enhances server-rendered markup: without JavaScript sliders are swipeable rows and all content stays readable and indexable. Visitors with *reduce motion* get no autoplay, ticker or reveal.
WooCommerce (when active): `assets/css/shop.css` gives products the retro look (framed cards, red sale stamp, display-face prices), `templates/archive-product.html` puts the shop under the sunburst title, and the header carries the mini cart. Plugin map from the old site: [docs/plugin-map.md](docs/plugin-map.md).
Patterns: `nostek/hero-slider` (latest posts), `nostek/banner-slider` (hand-made slides for pages), `nostek/post-carousel`, `nostek/news-ticker`. Any Group block with the *Slider* or *Carousel* style becomes one (slides = its direct children, or the posts of a Query Loop inside it).

## Content model

- **Journal sections** = native categories: News, Insights, Guides & Tutorials, Reviews, Founder's Note.
- **Topics** = `topic` taxonomy (`/topics/<slug>/`), with a short description shown on the Topics list and archive page.
- Contributors (WordPress role *Contributor*) can write drafts and pick Topics; only editors create Topics or publish.
- Virtual-team agents use the *Nostek Agent* role and publish through the REST API: [docs/content-api.md](docs/content-api.md).
