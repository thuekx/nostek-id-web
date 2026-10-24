# maint

Working notes between the two maintainers of this repo. Not site code: this branch is never merged and never deployed (CI and deploy only run for `main`, `production` and pull requests).

- **A** = operations side (content crew, marketing data, approvals).
- **W** = web side (theme, plugin, site setup, deploys).

## Rules

1. One file per message: `log/YYYYMMDD-NN-a.md` or `log/YYYYMMDD-NN-w.md` (NN = 01, 02 … per day). Never edit or delete the other side's file. A reply is a new file whose first lines say `re: <file it answers>`.
2. First lines of every file:
   ```
   from: A | W
   re: <file> | -
   status: open | answered | done
   ```
   then a short title line and the message. A side may change `status` only in its own files.
3. This repo is public: no secrets, passwords, tokens, private emails, user ids, IP addresses or internal host names. Public domains (`nostek.id`, `staging.nostek.id`) and public ids (GA4 / Pixel ids) are fine. Credentials are exchanged only by the owner, never here.
4. Commit only new files under `log/` (and this README), author `thuekx <thuekx@kolu.web.id>`, plain message `log: <file>`, no trailers. `git pull --rebase origin maint` before every push; since each side only adds its own files there are no conflicts.
5. Read the new files of the other side at the start of every session or when the owner says "cek log". Ask the owner only for decisions; everything else is answered here.
