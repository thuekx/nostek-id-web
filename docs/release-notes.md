# Release notes (production)

`main` deploys to staging.nostek.id; `production` deploys to nostek.id ([README](../README.md#branches-and-deploy)).
The `production` branch does not exist yet, so the first production release carries everything on `main`.
Each entry: what changes on nostek.id, what to check after the deploy, and checks still open on staging.

## Next production release (unreleased)

### WEB-41 shop URLs under `/product/` (PR #47, merged 2026-11-21, nostek-core 0.9.0, theme 0.12.1)

Decision 2026-11-21 (option A), register row in [decisions.md](decisions.md):
- Product categories at `/product/<category>/`, sub-categories at `/product/<parent>/<child>/`.
- Single products unchanged at `/product/<product>/`.
- Old `/product-category/...` URLs (flat links such as `/product-category/e-book/`, `/page/N/`, feeds, query strings kept) redirect 301 to the new address.
- Header Market menu links use `/product/...`.
- Nostek Core deactivated = WooCommerce default URLs, no error.

Tests before release: offline `php tools/test_product_urls.php` (CI RESULT product-urls PASS 21/21); real WordPress 7.1.5 + WooCommerce 11.3.1 `bash tools/test_product_urls_wp.sh` (RESULT product-urls-wp PASS 20/20).
Staging after deploy: category URL 200, old `/product-category/...` 301 to the new URL.

Open on staging (before the production release):
- [ ] `/product/<product>/` returns 200 once a product exists on staging.

After the production deploy (nostek.id):
- [ ] One category URL `/product/<category>/` returns 200.
- [ ] One old `/product-category/<category>/` URL returns 301 to `/product/<category>/`.
- [ ] `/product/<product>/` returns 200 (when a product exists).
- [ ] Header Market menu links point to `/product/...`. If the Header template part was edited in the Site Editor, the database copy keeps the old links (they still work through the 301); resetting it (Appearance > Editor > Patterns > Header, menu ⋮ > Reset) shows the new links.
