# SEO, analytics and marketing hooks

What Nostek Core (0.2.0+) puts in every page for search engines, link previews and analytics, and what stays a setting outside git.
No third-party SEO plugin is needed. If Yoast SEO or Rank Math is ever activated, the head tags below switch off by themselves (no duplicates); the staging guard stays on.

## Live or not

`nostek_core_is_production()` decides everything below. It is true only when the home URL host is `nostek.id` or `www.nostek.id`, or when `wp-config.php` sets `define( 'NOSTEK_ENV', 'production' );` (any other value forces "not live").
A Softaculous clone of production on `staging.nostek.id` is therefore never treated as live, even if the copied database says "allow search engines".

| | Live (`nostek.id`) | Anything else (staging) |
|---|---|---|
| `<meta name="robots">` | core default (`max-image-preview:large`) | `noindex, nofollow` |
| `X-Robots-Tag` header | none | `noindex, nofollow` |
| `/robots.txt` | core rules + `Sitemap: https://nostek.id/wp-sitemap.xml` | `User-agent: *` / `Disallow: /` |
| `/wp-sitemap.xml` | on (core sitemap) | off (404) |
| GA4 / Meta Pixel | after consent, when an ID is set | never |

A physical `robots.txt` file in the web root bypasses WordPress, so none may exist on either site. Staging also keeps "Discourage search engines" ticked and the Under Construction plugin on (decision 2026-10-23).

## Head tags

| Tag | Source |
|---|---|
| `<title>` | `title-tag` theme support: `Post title | Nostek ID`; front page `Nostek ID | <tagline>` |
| `meta description` | post meta `nostek_meta_description` (max 160 characters, writable over REST by agents), else the excerpt, else the first 30 words; archives: term description; front page and Journal: the tagline |
| `link rel="canonical"` | singular: core; Journal, categories, Topics, author pages and the front page: Nostek Core. Always without query strings |
| Open Graph | `og:locale id_ID`, `og:site_name`, `og:type` (`article` on posts, else `website`), `og:title`, `og:description`, `og:url` (= canonical), `og:image` (+ width, height, alt) from the featured image (`large` size), else the Site Icon; posts add `article:published_time`, `article:modified_time`, `article:section` |
| Twitter card | `summary_large_image` when the image is at least 600 px wide, else `summary` (title/description/image come from Open Graph) |
| JSON-LD | front page: `Organization` (name, url, logo, `sameAs`); single posts: `Article` (headline, dates, author, publisher = the Organization, image, description, `inLanguage id-ID`) |

Search and 404 pages get no extra tags (core already marks search results `noindex`).

## Settings → Nostek (admins only, stored in the database, never in git)

| Field | Use | wp-config constant (wins when set) |
|---|---|---|
| GA4 Measurement ID | `G-XXXXXXXXXX`; empty = no Google Analytics and no banner | `NOSTEK_GA4_ID` |
| Meta Pixel ID | digits; **loads only when "Load the Meta Pixel" is ticked** (owner approval first) | `NOSTEK_META_PIXEL_ID` |
| Organization name | JSON-LD name, default `Nostek` | |
| Logo URL | square PNG/WebP, at least 512 px, from the Media Library; empty = Site Icon | |
| Social profiles | one URL per line, e.g. `https://www.instagram.com/nostek.id/`; becomes `sameAs` | |

Invalid IDs are refused with a notice and not stored. These are public IDs, not secrets, but they still stay out of git so staging and production can differ.
Softaculous "Push to Live" copies the database, so a value set on staging arrives on production too; on staging it never loads anything.

## Consent banner

Shown at the bottom of the page (fixed, so it never shifts the layout) only on the live site, only when an ID is set, and never to logged-in editors.

- Text (Indonesian): *"Kami memakai cookie analitik untuk memahami artikel mana yang dibaca dan dari mana pembaca datang. Data ini tidak dijual."* plus a link to the Privacy Policy page when one is set (Settings → Privacy). Buttons **Terima** / **Tolak**.
- Nothing from Google or Meta loads before **Terima**. The choice is stored in the first-party cookie `nostek_consent` (`granted` / `denied`, 180 days).
- GA4 loads with Consent Mode: `analytics_storage` granted, all `ad_*` denied.
- **Tolak** after an earlier **Terima** removes the `_ga*`, `_gid`, `_gat`, `_fbp`, `_fbc` cookies.
- The footer link **Pengaturan cookie** (any element with `data-nostek-consent-open`) opens the banner again; it is hidden where the banner is not active.

## UTM and clean URLs

Nothing in the theme or plugin redirects or strips query strings, so `utm_source`, `utm_medium`, `utm_campaign` (and `fbclid` / `gclid`) reach GA4 in the page URL. The canonical URL and `og:url` never carry them, so shared links with UTM do not create duplicate pages for Google.

## Images (PageSpeed: LCP / CLS)

- Resized copies of JPEG and PNG uploads are written as WebP when the server supports it (`image_editor_output_format`). Upload WebP originals where possible.
- Core adds `width` / `height`, `loading="lazy"` below the fold, `fetchpriority="high"` on the first large image (the featured image), and `srcset`. Templates use core image blocks only, so this stays on.
- Fonts are bundled and self-hosted (`themes/nostek/assets/fonts/`); no Google Fonts request.

## Checks after a deploy

```
curl -s https://staging.nostek.id/robots.txt            # User-agent: * / Disallow: /
curl -sI https://staging.nostek.id/ | grep -i x-robots   # X-Robots-Tag: noindex, nofollow
curl -s https://nostek.id/robots.txt | grep Sitemap      # Sitemap: https://nostek.id/wp-sitemap.xml (after launch)
```
