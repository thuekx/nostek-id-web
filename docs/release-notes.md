# Release notes (production)

`main` deploys to staging.nostek.id; `production` deploys to nostek.id ([README](../README.md#branches-and-deploy)).
nostek.id launched on 2026-11-26 (Push to Live of staging, runbook 08 rev 2, Nostek Core 0.11.0, theme 0.12.3): everything listed under "Released" was live from that day. Later releases go `main` → `production` ([README](../README.md#release-to-nostekid-after-the-launch-of-2026-11-26)).
Each entry: what changes on nostek.id, what to check after the deploy, and checks still open on staging.

## Next production release (unreleased)

### WEB-46 production lanes (no change on the live site itself)

- Site setup can target nostek.id (from branch `production`, environment `production`, own Application Password).
- Release flow in the README.

## Released 2026-11-26 (launch, Push to Live)

### WEB-45 roles v3: publisher may trash unpublished items (nostek-core 0.11.0)

- `publisher-nostek` (Nostek Publisher) can move any author's draft / Pending Review post or page to the Trash and restore it. Published, scheduled and private items stay out of reach (403); permanent delete is refused for both agent roles (403). Details: [content-api.md](content-api.md#what-each-role-can-do).
- Applied once on the first page load after the deploy (option `nostek_core_roles_version` = 3).

Tests before release: real WordPress `bash tools/test_trash_guard_wp.sh` (RESULT trash-guard-wp PASS 22/22).

Staging after deploy:
- [ ] `publisher-nostek`: REST `DELETE /wp/v2/pages/<draft of another author>` = 200 (item in the Trash); `DELETE /wp/v2/pages/<published page>` = 403.

After the production deploy (nostek.id):
- [ ] Same two checks once `publisher-nostek` has a production Application Password.

### WEB-43 cookie banner with categories + Cookie Policy page (PR #50, merged, nostek-core 0.10.0, theme 0.12.3)

- Consent banner gets **Atur** (per-category switches: Wajib locked on, Analitik, Pemasaran when the Pixel is on) next to **Terima semua** / **Tolak**; Google Consent Mode v2 defaults all denied. Details: [seo-analytics.md](seo-analytics.md#consent-banner-web-43).
- New page **Cookie Policy** under Legal (`/legal/cookie-policy/`, created by Site setup), linked from the banner, the Privacy Policy and the footer Nostek column.
- Visitors who chose before keep their choice (`granted` = Analitik only).

Staging after deploy + Site setup apply:
- [ ] `/legal/cookie-policy/` returns 200 and shows the cookie tables (staging shows the "only essential cookies" note: the banner never runs off the live site).
- [ ] Footer Nostek column lists Cookie Policy (Site setup adds it to the existing menu after Privacy Policy, WEB-44: `PASS add to menu 'footer-nostek' … Cookie Policy`).
- [ ] Existing Privacy Policy page: Site setup does not rewrite it; its Cookie paragraph can link to `/legal/cookie-policy/` by hand.

After the production deploy (nostek.id), when a GA4 ID is set:
- [ ] First visit in a private window: banner shows, no request to googletagmanager.com before a choice (browser DevTools > Network).
- [ ] **Atur** > Analitik on > **Simpan pilihan**: gtag.js loads; cookie `nostek_consent` = `2.a1.mx`.
- [ ] Footer **Pengaturan cookie** > **Tolak**: `_ga` cookies gone.

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
- [x] One old `/product-category/<category>/` URL returns 301 to `/product/<category>/` (2026-11-27 00:1x WIB: `/product-category/e-book/` → `/product/nostek-digital/e-book/`).
- [ ] `/product/<product>/` returns 200 (when a product exists).
- [ ] Header Market menu links point to `/product/...`. If the Header template part was edited in the Site Editor, the database copy keeps the old links (they still work through the 301); resetting it (Appearance > Editor > Patterns > Header, menu ⋮ > Reset) shows the new links.
