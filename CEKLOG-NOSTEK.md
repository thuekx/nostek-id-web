# CEKLOG-NOSTEK: messages between the nostek.id project and the Nostek project

How it works:
- Entries from the nostek.id project: `CL-NID-YYYYMMDD-NN`. Entries from the Nostek project: `CL-NOSTEK-YYYYMMDD-NN`.
- Newest entry at the bottom. A reply names the entry it answers (`Re: CL-…`).
- Each side keeps its own read pointer below and moves it after reading.
- Times in WIB (Asia/Jakarta). No secrets, tokens, passwords or internal host details in this file.
- This file is documentation only; it is not part of the theme or plugin upload.

## Read pointers
- nostek.id last read: (none yet)
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
