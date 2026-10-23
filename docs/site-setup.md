# Site setup: terms, base pages, menu and reading settings

The header and footer menus are part of the theme (`themes/nostek/parts/header.html`, `footer.html`) and link to fixed paths:
`/journal/`, `/topics/`, `/services/`, `/market/`, `/about/`. Those pages are created by `tools/setup_site.py` from `site/pages.json`.

| Page | Slug | Role |
|---|---|---|
| Home | `home` | Static front page; its layout comes from the theme template `front-page`, the page text is not shown |
| Journal | `journal` | Posts page (all articles, newest first) |
| Topics | `topics` | Lists every Topic (`nostek/topics-list` block); each Topic lives at `/topics/<slug>/` |
| Services, Market, About | `services`, `market`, `about` | Placeholder pages until their content is written |

The same run creates the Journal categories and the Topics listed in `site/pages.json` (agents never create terms):

| Kind | Slugs |
|---|---|
| Journal categories | `news`, `insights`, `guides-tutorials`, `reviews`, `founders-note` |
| Topics | `ai`, `cybersecurity`, `cloud-infrastructure`, `networking`, `gadget-hardware`, `software-applications`, `digital-business` |

Names and descriptions are only the starting text: a term that already exists is never changed, so edit them in wp-admin.

## Run from GitHub (no local Python needed)

1. Add two repository secrets: `STAGING_WP_USER` (a staging Administrator) and `STAGING_WP_APP_PASSWORD` (that user's Application Password).
2. Actions → **Site setup** → **Run workflow** (branch `main`). Unticked *apply* = dry run (`PLAN` / `SKIP` lines); run again with *apply* ticked to write (`PASS` lines).

The workflow only ever targets `https://staging.nostek.id`.

## Run locally

Needs an **Administrator** user with an Application Password (Settings > Reading needs `manage_options`). Credentials come from the environment only.

```
export NOSTEK_WP_URL=https://staging.nostek.id
export NOSTEK_WP_USER=<admin user>
read -rs NOSTEK_WP_APP_PASSWORD && export NOSTEK_WP_APP_PASSWORD   # hidden prompt
python3 tools/setup_site.py            # dry run: PLAN / SKIP lines
python3 tools/setup_site.py --apply    # PASS lines
```

- A term or page whose slug already exists is left untouched (`SKIP`), so running it again is safe.
- If a slug is held by a page in the Trash, the run stops with `FAIL … was created as '<slug>-2'`: empty the Trash or rename that page, then run again.
- cPanel Directory Privacy (HTTP basic auth) and Application Passwords both use the `Authorization` header, so the REST API (this tool and `publish_article.py`) cannot log in while Directory Privacy protects the site. Keep staging private with the under-construction plugin instead, or lift Directory Privacy for the run.
- ModSecurity on the host can block REST requests whose body holds block markup: the run then stops with `FAIL … from the web server/firewall, not WordPress (HTML error page)`. On staging, switch ModSecurity off for `staging.nostek.id` (cPanel → ModSecurity).
