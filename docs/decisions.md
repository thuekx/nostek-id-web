# Decisions

| Date | Decision | Card |
|---|---|---|
| 2026-10-22 | Replace VintWood / WPBakery / Slider Revolution with a custom block theme built in this repo. Not headless. | P1-03 |
| 2026-10-22 | Repo holds only custom code; core, uploads, DB, credentials stay on the server. | P1-03 |
| 2026-10-23 | Staging on `staging.nostek.id` (Softaculous staging). | P4-01 |
| 2026-10-23 | Launch content moves once with Softaculous "Push to Live"; after launch, content is written directly in production and only code flows through Git. | P4-01 |
| 2026-10-23 | Host has no shell access: deploy via GitHub Actions over FTPS with one FTP account per site, each limited to that site's `wp-content`. `main` -> staging, `production` -> live. | P4-01 |
| 2026-10-24 | SEO head tags, robots/sitemap, staging noindex and consent-gated GA4 live in Nostek Core; no third-party SEO plugin. Analytics IDs are settings, never in git; nothing loads off the live host. | WEB-09 |
| 2026-10-24 | Publishing after owner approval: one `publisher-nostek` account (role Nostek Publisher: publish and edit posts, nothing else), used only by the approval tool. Agents stay on Nostek Agent (Pending Review). | WEB-09 |
| 2026-11-02 | Writer agent may write pages as Pending Review and read the site structure (pages, term trees, menus) through a read-only `nostek/v1/menus` route; publishing pages stays with `publisher-nostek` after owner approval. Role caps upgrade on existing sites by `NOSTEK_CORE_ROLES_VERSION`. | WEB-26 |
| 2026-11-02 | Article kinds from the content brief are not terms: articles use the existing Journal categories and Topics, plus 1-3 tags from a fixed starter list (`site/pages.json`); agents never create terms (tags blocked over REST for non-editors). | WEB-27 |
| 2026-11-04 | Owner adds 45 tags (Topic / Category sub-themes, 65 in all) to `site/pages.json`, each with a `Use for:` description; still no new categories or menu items. Footer shows the public Facebook, Instagram and YouTube profiles (the same URLs go into Settings → Nostek → Social profiles for `sameAs`). | WEB-28 |
| 2026-11-11 | Single articles get side rails on desktop (>= 1200px): table of contents + share links on the left, related articles (same Topic, then category) + Services box on the right, author box under the text; phones get a folded TOC above and the rest below. Heading anchors, TOC, related and share live in Nostek Core; share links are plain links (no third-party script). BreadcrumbList schema joins the Article schema. | WEB-33 |
