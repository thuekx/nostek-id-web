# Production plugin map (VintWood site → Nostek theme)

Read-only check of the public production site on 2026-10-25 (page markup and `/wp-json/` namespaces).
Production still runs the VintWood demo (WPBakery layout, demo coffee shop products, `noindex, nofollow`).

| On production | What it does there | Nostek replacement | Status |
|---|---|---|---|
| VintWood theme + WPBakery Page Builder | Layout, retro look, page builder | `themes/nostek` block theme (Site Editor, patterns, block styles) | Done |
| Slider Revolution 6.7.58 | Header slider, page sliders | Theme motion layer: `nostek/hero-slider`, `nostek/banner-slider`, *Slider* / *Carousel* group styles | Done (WEB-11, WEB-12) |
| VintWood mega menu (Blog: 3 columns) | Mega menu | Core Navigation submenus with descriptions, styled as a mega panel (`nk-mega`) | Done (WEB-11) |
| VintWood animations / preloader | Motion | Scroll reveal, hover, ticker, sticky header; no preloader (slows first paint) | Done (WEB-11) |
| WooCommerce | Shop, cart, checkout | Kept. Theme styles (`assets/css/shop.css`), `archive-product` template, mini cart in the header, product carousel on Market | Done (WEB-12) |
| Contact Form 7 | Contact form | Keep the plugin (theme independent); a Contact page can embed its shortcode | Open: Contact page not in the menu yet |
| Jetpack | Stats, misc | Not needed by the theme; GA4 is consent-gated in Nostek Core. Deactivate after launch if unused | Owner decides |
| WP Google Maps | Map on the contact page | A Contact page can use a plain map embed; plugin optional | Owner decides |
| Under Construction page | Hides the site | Keep on production until launch | Owner |

Plugins the Nostek theme does not need at all: WPBakery, Slider Revolution, VintWood helpers. Remove them only on production cut-over, after the Nostek theme is active there.
