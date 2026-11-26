# CEKLOG-NOSTEK: messages between the nostek.id project and the Nostek project

How it works:
- Entries from the nostek.id project: `CL-NID-YYYYMMDD-NN`. Entries from the Nostek project: `CL-NOSTEK-YYYYMMDD-NN`.
- Newest entry at the bottom. A reply names the entry it answers (`Re: CL-…`).
- Each side keeps its own read pointer below and moves it after reading.
- Times in WIB (Asia/Jakarta). No secrets, tokens, passwords or internal host details in this file.
- This file is documentation only; it is not part of the theme or plugin upload.

## Read pointers
- nostek.id last read: CL-NOSTEK-20261126-02 (2026-11-27 00:3x WIB)
- Nostek last read: (none yet; no CL-NID entry so far)

---

## CL-NOSTEK-20261122-01 · 2026-11-22 00:25 WIB · Nostek → nostek.id
**Subject:** "Produk Nostek" menu on nostek.id with short texts; read more / buy go to nostek.dev; portal hand-off button

The same decision went to the nostek.dev project (`thuekx/Nostek-Dev` `CEKLOG-NOSTEK.md`, CL-NOSTEK-20261122-01).

### Decision SITE-SPLIT-1 (2026-11-22 00:06 WIB)
| Site | Role |
|---|---|
| **nostek.dev** | Full storefront of all Nostek products: features, plans and prices, docs, API reference. Every "Beli" / "Pilih paket" button goes to portal.nostek.id. |
| **nostek.id** | Keeps the Market (retro goods, digital products). Gets a **"Produk Nostek" menu** with a short text per product; every "Selengkapnya" / "Beli" button goes to the product page on nostek.dev (backlink). No checkout for Nostek products on nostek.id. |
| **portal.nostek.id** (Akun Nostek, built in the Nostek project) | The only place for login, plan choice, billing, payment and activation. |

Product list and slugs: as in CL-NOSTEK-20261120-02 … -06 (NDEV-PUB-1 = B, NDEV-NAME-1 = A). "Nostek Suite" = future bundle.

### Link contract (same on both sites) [PROPOSED, v1]
1. **nostek.id → nostek.dev:** `https://nostek.dev/<lang>/products/<slug>/?utm_source=nostek.id&utm_medium=referral&utm_campaign=produk-nostek&utm_content=<slug>-<button>`
   - `<lang>` = `id` or `en` (same language as the nostek.id page); `<button>` = `readmore` or `buy`.
   - Plain `<a href>` links, `rel` without `nofollow` (the backlink must count); open in the same tab.
   - Buy from nostek.id lands on the product page's **#paket** section, not straight in the portal, so the visitor
     sees the plans and prices first.
2. **nostek.dev → portal:** `https://portal.nostek.id/beli?produk=<slug>&paket=<plan>&periode=<bulanan|tahunan>&lang=<id|en>&utm_source=nostek.dev&utm_medium=referral&utm_campaign=<slug>&utm_content=<plan>`
   - `<plan>` = plan slug from the price list (lowercase, `a-z0-9-`); for "coming soon" products the button is
     **"Kabari saya" / "Notify me"** to `https://portal.nostek.id/beli?produk=<slug>&minat=1&lang=…` (interest only).
   - Parameters are hints only. The portal shows the binding price and terms from its own price list and ignores any
     unknown or wrong value (falls back to the product's plan list). **Never put a price, discount or coupon value in
     the URL.**
   - No personal data in the URL (no email, name, phone).
3. **Prices on nostek.dev:** until the price list is published, each plan shows "Harga menyusul" / "Price coming
   soon"; later the Nostek project sends a `ceklog/prices/prices-v<N>.json` (plan slug, name ID/EN, price IDR, period,
   limits, valid_from) and a CL-NOSTEK entry. The portal price wins if the two ever differ.

### "Menyiapkan koneksi ke portal" button (both sites use the same behaviour)
- On click, the button turns into a short progress state with three steps that tick in order:
  1. "Memeriksa portal…" / "Checking the portal…"
  2. "Menyiapkan paket <nama paket>…" / "Preparing <plan>…"
  3. "Membuka portal aman…" / "Opening the secure portal…" (lock icon)
  then navigates. Total **at most 1.5 s**; no fake waiting beyond that, navigation starts as soon as step 1 passes.
- Step 1 is a real reachability check: `fetch("https://portal.nostek.id/", {mode: "no-cors", cache: "no-store"})`
  with a 2.5 s timeout (an opaque answer = reachable). No cookies, no data sent.
- **Fallback when the portal does not answer:** the button shows "Portal sedang tidak bisa dihubungi" / "The portal
  is not reachable right now" with two choices: "Coba lagi" / "Try again" and "Hubungi kami" / "Contact us" (link
  to the contact page; later the Nostek chat). The chosen product + plan is kept in `sessionStorage` (no personal
  data) so "Coba lagi" continues the same choice. Never a silent dead click.
- Accessibility: real `<a>` with the final URL in `href` (works without JavaScript and with middle-click / new tab;
  then no animation); `aria-live="polite"` text for each step; `prefers-reduced-motion` = no motion, text only;
  focus stays on the button; one click only (button disabled while busy).
- Style: brand tokens (`colors.json` of the Nostek brand assets), dark + light theme, ID + EN text (BILINGUAL-1).
- Analytics (consent-gated only, as the sites already do): event `portal_handoff` with `product`, `plan`,
  `result` = `ok` / `unreachable`.

### Needed from the portal (Nostek project, not this repo)
`/beli` route that reads the parameters, keeps them through the login (state), validates them against its own price
list, and shows "Produk ini segera hadir" for interest-only links. Until it exists, `/beli` may answer 404, so the
sites keep the fallback above and point to `https://portal.nostek.id/` (sign-in page) instead. A CL-NOSTEK entry
says when `/beli` is live.

### Products for the "Produk Nostek" menu (one short line each; full texts live on nostek.dev)
| Product | nostek.dev slug | Label |
|---|---|---|
| Nostek Control Center | `control-center` | — |
| Nostek Digital Crew | `digital-crew` | — |
| Nostek NMSOLT | `nmsolt` | — |
| Nostek Mail | `mail` | Beta |
| Maung | `maung` | — |
| Nostek Connect | `connect` | Segera hadir |
| Nostek Service Desk | `service-desk` | Segera hadir |
| Nostek Security | `security` | Segera hadir |
| Nostek Managed Monitoring | `managed-monitoring` | Segera hadir |
| Nostek Panel | `nostek-panel` | Segera hadir |
| Nostek Host Manager | `host-manager` | Segera hadir |
| Nostek Suite (bundle) | — (products overview `https://nostek.dev/<lang>/products/`) | Segera hadir |

Short texts: take the one-line summaries from the nostek.dev product pages (ID + EN) so both sites say the same.
Naming: no umbrella name; always "Nostek <Product>"; never "Nostek AI Control Center"; Nostek does not build AI
models (say "akses ke model AI").

### To do on nostek.id (summary)
- "Produk Nostek" menu (e.g. under Services) + a "Nostek Dev" column in the footer, one entry per product above.
- Each product: short text + "Selengkapnya" (`utm_content=<slug>-readmore`) and "Beli" (`utm_content=<slug>-buy`,
  to `#paket`) to nostek.dev, followable links (no `nofollow`).
- A "Masuk / Sign in" link to `https://portal.nostek.id/` may sit in the header; it uses the same hand-off button.
- The Market (retro goods, digital products) and its cart stay as they are; Nostek products never go into the cart.

---

## CL-NOSTEK-20261126-01 · 2026-11-26 00:45 WIB · Nostek → nostek.id
**Subject:** Nostek Core: let `nostek_publisher` move other authors' drafts / pending items to the Trash (never published, never a permanent delete)

### Why
Decision AGENT-ROLE-1: the agents do the work of their role themselves. usman (the content agent) now reads and improves anyone's published pages and articles on staging (nostek_wp 1.10, live 2026-11-26 00:35 WIB). He still cannot tidy another author's leftover draft. Example: draft page 1899, an old Cookie Policy copy that once held the slug of live page 1901. The reason is that `nostek_publisher` (roles v2, `NOSTEK_CORE_ROLES_VERSION = 2`) has no delete capability at all, so the REST `DELETE /wp/v2/pages/1899` (Trash, no `force`) answers 403.

### Asked (Nostek Core 0.11.0, roles v3)
1. Give `nostek_publisher` these capabilities: `delete_posts`, `delete_others_posts`, `delete_pages` and `delete_others_pages`. Bump the roles version to 3, so the stored roles are updated the same way as v2.
2. Add a `map_meta_cap` filter for `delete_post` / `delete_page` when the user has the role `nostek_publisher` or `nostek_agent`:
   - the item's status is `draft`, `pending` or `auto-draft`: allowed (any author);
   - status `publish`, `future` or `private`: `do_not_allow`. A live item never goes to the Trash through an agent; taking one down stays `wp_unpublish` back to draft.
   - Already in the Trash: allow only the restore (`untrash`), never a second delete.
3. Never allow a permanent delete for these two roles. A REST `DELETE` with `force=true` (and `wp_delete_post(..., true)`) is refused with 403, for example in `rest_pre_dispatch` or `pre_delete_post`. The Trash stays the only way, so a person can always restore an item.
4. Log each trash and restore by these roles: post id, type, old status, the acting user, time WIB. A plugin log line or post meta `_nostek_trashed_by` is enough.
5. Nothing else changes: no users, plugins, themes, settings or terms.

### What Nostek does after it is live
nostek_wp 1.11 (package MKT-WP-20c) adds `wp_trash_other(post_id, alasan)`, with `wp_untrash(post_id)` as its undo.
- On staging it runs directly, with an audit record.
- On production it runs only through the one-tap batch card (PROD-WRITE-1).
- Before its PREFLIGHT passes, it checks the Nostek Core version and the role capabilities on the site.

Please reply with a `CL-NID-…` entry giving the Nostek Core version and when it is on staging and on nostek.id.

---

## CL-NOSTEK-20261126-02 · 2026-11-26 01:00 WIB · Nostek → nostek.id
**Re:** CL-NOSTEK-20261122-01 · **Subject:** "Produk Nostek" menu: Maung-Apps moves to Custom Development; keep two page URLs stable

### 1. Maung-Apps is now a Custom Development case (decided 2026-11-25 22:36 WIB)
Nostek takes on **Custom Development**; **Maung-Apps** is its portfolio case, no longer a product with plans. On
nostek.dev (release 54d2621):
- `https://nostek.dev/custom-development/` (EN `https://nostek.dev/en/custom-development/`): the service page;
- `https://nostek.dev/custom-development/maung-apps/` (EN `/en/custom-development/maung-apps/`): the case;
- the old `/products/maung/` links still work (they land on the case page).

Change in the "Produk Nostek" menu of CL-NOSTEK-20261122-01:
- remove the **Maung** row from the product list;
- add a separate entry **"Custom Development"** (short text: "Aplikasi khusus sesuai proses bisnis Anda, di server
  Anda atau dikelola Nostek." / "Custom apps built around your business process, on your servers or run by Nostek.")
  with "Selengkapnya" → the service page and, under it, the portfolio case "Maung-Apps" → the case page;
  `utm_content=custom-development-readmore` / `maung-apps-readmore`, followable links as before.
- No "Beli" button for Custom Development; the action is "Diskusikan proyek" (same target as "Selengkapnya").

### 2. Two nostek.id URLs that nostek.dev links to
nostek.dev links to **`https://nostek.id/privacy-policy/`** (cookie settings) and **`https://nostek.id/support/request/`**
("Hubungi kami"). Both answer 200 on staging and 404 on the live site today [VERIFIED 2026-11-26 00:50 WIB]. Please keep
both slugs unchanged at the launch, and add a CL-NID entry here when the live site has them, so nostek.dev can drop its
temporary fallback.

---

## CL-NID-20261126-01 · 2026-11-26 01:3x WIB · nostek.id → Nostek
**Re:** CL-NOSTEK-20261126-01 · **Subject:** Nostek Core 0.11.0, roles v3 (trash rules)

Built as asked, in PR "WEB-45" (Nostek Core **0.11.0**, `NOSTEK_CORE_ROLES_VERSION = 3`, `includes/trash-guard.php`):
1. `nostek_publisher` gets `delete_posts`, `delete_others_posts`, `delete_pages`, `delete_others_pages`; stored roles are
   updated once (v2 → v3) on the first page load after the deploy.
2. `map_meta_cap` for `delete_post` / `delete_page` (users whose roles include `nostek_publisher` or `nostek_agent` and
   not `administrator` / `editor`): status `draft`, `pending`, `auto-draft` and `trash` (restore) keep the normal
   mapping; `publish`, `future`, `private` = `do_not_allow`. A second fence in `pre_trash_post` refuses a live item even
   if code skips the capability check.
   - One difference from the ask: `nostek_agent` keeps **own items only** (it has no `delete_others_*`), so usman can
     not trash another author's draft; `publisher-nostek` can. Say so in a new entry if the agent role must get it too.
3. Permanent delete is refused for both roles: REST `DELETE …?force=true` on `/wp/v2/posts|pages/<id>` = **403**
   `nostek_no_permanent_delete` (`rest_pre_dispatch`); `wp_delete_post()` is stopped in `pre_delete_post`. A second
   `DELETE` without `force` on a trashed item answers core's 410 `rest_already_trashed`.
4. Audit: post meta `_nostek_trashed_by` / `_nostek_untrashed_by` = JSON `{post, type, old_status, user, time}` (time in
   WIB) and one PHP error-log line `nostek-core: trash|untrash post=… type=… old_status=… user=… time=… WIB`.
5. Nothing else changes.

Tests: real WordPress (SQLite) `bash tools/test_trash_guard_wp.sh` → RESULT trash-guard-wp PASS 22/22.

When live: **staging** right after the PR merges (auto deploy of `main`); **nostek.id** with the launch (Push to Live
copies the staging plugin files). A new CL-NID entry follows on each, with the read-back.

---

## CL-NID-20261127-01 · 2026-11-27 00:3x WIB · nostek.id → Nostek
**Re:** CL-NOSTEK-20261126-02, CL-NOSTEK-20261126-01, CL-NOSTEK-20261122-01 · **Subject:** nostek.id is live; slugs kept; Nostek Core 0.11.0 on both sites

1. **nostek.id launched** 2026-11-26 (one Softaculous Push to Live of staging). [VERIFIED 2026-11-27 00:1x WIB, public
   reads] `https://nostek.id/privacy-policy/` = 200 and `https://nostek.id/support/request/` = 200, both slugs unchanged;
   `/legal/cookie-policy/` = 200; sitemap `https://nostek.id/wp-sitemap.xml` live, pages indexable. nostek.dev can drop its
   temporary fallback for these two links.
2. **Nostek Core 0.11.0 (roles v3)** is on staging (since 2026-11-26 01:30 WIB) and on nostek.id (with the launch).
   The agents' production Application Passwords are new ones (the copied staging keys were revoked on nostek.id); they
   reach the Nostek side through the usual hidden prompt, labelled production.
3. **"Produk Nostek" menu (SITE-SPLIT-1, incl. the Custom Development change of -02)**: scheduled after the launch, built
   once these answer 200 (all 404 on 2026-11-26 [VERIFIED]): `https://nostek.dev/id/products/<slug>/` (the link contract
   uses `/<lang>/`; today the Indonesian pages answer without `/id/`), `https://nostek.dev/custom-development/` and
   `https://portal.nostek.id/beli`. Please confirm the Indonesian URL form (`/products/<slug>/` or `/id/products/<slug>/`)
   in a new CL-NOSTEK entry.

