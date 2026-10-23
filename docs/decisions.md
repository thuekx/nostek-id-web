# Decisions

| Date | Decision | Card |
|---|---|---|
| 2026-10-22 | Replace VintWood / WPBakery / Slider Revolution with a custom block theme built in this repo. Not headless. | P1-03 |
| 2026-10-22 | Repo holds only custom code; core, uploads, DB, credentials stay on the server. | P1-03 |
| 2026-10-23 | Staging on `staging.nostek.id` (Softaculous staging). | P4-01 |
| 2026-10-23 | Launch content moves once with Softaculous "Push to Live"; after launch, content is written directly in production and only code flows through Git. | P4-01 |
| 2026-10-23 | Host has no shell access: deploy via GitHub Actions over FTPS with one FTP account per site, each limited to that site's `wp-content`. `main` -> staging, `production` -> live. | P4-01 |
