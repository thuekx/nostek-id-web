# Content API: publishing Journal articles and pages

How the Nostek virtual team (or any script) pushes articles to nostek.id.
Code goes through Git; **content goes through the WordPress REST API**, one WordPress user per agent.

## Accounts

| Item | Rule |
|---|---|
| User | One WordPress user per agent, named `<agent>-nostek`: `usman-nostek` (articles, display name "Usman Tech-Writer"), `hassan-nostek` (images). Never shared. Staging first, then production. |
| Role | `Nostek Agent` (from the Nostek Core plugin): can write articles and pages, upload images, pick categories and Topics; **cannot publish**, so everything lands as *Pending Review*. |
| Publisher | One account `publisher-nostek` with role `Nostek Publisher`, used only by the owner-approval tool, only after the owner approved an article or page (see *Publishing* below). |
| Credential | An Application Password on that user (Users → Profile → Application Passwords), created by the owner. Stored only in the agent's secret store, entered through a hidden prompt, never in chat, git or argv. Revoke it there to cut access at once. |
| Terms | Categories, Topics and tags must already exist. Agents never create or rename terms; Nostek Core 0.3.1+ refuses a new tag over REST from anyone who is not an editor. |

### What each role can do

| Capability | Nostek Agent | Nostek Publisher |
|---|---|---|
| Create / edit own drafts and Pending Review posts **and pages** | yes | yes |
| Withdraw (Trash) own unpublished posts and pages | yes | no |
| Upload media, set alt text, use any media item as featured image | yes | yes |
| Read menus (`GET /nostek/v1/menus`) and term trees | yes | yes |
| Publish posts and pages | no | yes |
| Edit or publish other users' posts and pages | no | yes |
| Change a published post or page / move it back to draft | no | yes |
| Delete other users' posts or pages, users, plugins, themes, settings, menus, terms | no | no |

The lists live in `nostek_core_role_caps()` (Nostek Core 0.3.0+). Changing a list bumps `NOSTEK_CORE_ROLES_VERSION`, so sites that already have the roles get the new capabilities once, on the next page load after the deploy (option `nostek_core_roles_version`).

## Tool

`tools/publish_article.py` (Python 3, standard library only).

```
export NOSTEK_WP_URL=https://nostek.id          # or https://staging.nostek.id
export NOSTEK_WP_USER=usman-nostek
export NOSTEK_WP_APP_PASSWORD=...               # from the secret store, never in argv or logs
python3 tools/publish_article.py article.json            # dry run: validates, resolves terms
python3 tools/publish_article.py article.json --apply    # create or update
```

Output is one line: `DRY RUN ok: …`, `PASS create post: id=… status=… link=…`, `PASS update post …`, or `FAIL …` (exit 1).

`tools/pending_posts.py` (same environment variables) lists what waits for review for that agent:

```
python3 tools/pending_posts.py                  # PENDING id=1668 source_id=… modified=… title=…  then  PASS 1 post(s) pending for usman-nostek
python3 tools/pending_posts.py --status draft   # or future, publish
```

## Article file

See [`examples/article.json`](examples/article.json).

| Field | Required | Notes |
|---|---|---|
| `slug` | yes | Lowercase words joined by `-`. Also the article's stable id: sending the same slug again **updates** that article instead of creating a duplicate (stored as post meta `nostek_source_id`). |
| `title` | yes | Keep the original Indonesian/English mix; do not translate. |
| `content` | yes | Gutenberg block markup (preferred) or plain HTML. Theme block styles and Nostek element blocks can be used: see [content-elements.md](content-elements.md). |
| `excerpt` | no | One or two sentences for cards; also the search snippet when `meta_description` is empty. |
| `meta_description` | no | Search snippet, at most 160 characters (post meta `nostek_meta_description`). Left out = the stored value is kept. |
| `category` | yes | One Journal section slug: `news`, `insights`, `guides-tutorials`, `reviews`, `founders-note`. |
| `topics` | no | Topic slugs, e.g. `ai`, `cybersecurity`, `cloud-infrastructure`, `networking`. |
| `tags` | no | 1-3 existing tag slugs from the list in `site/pages.json` (`tags`, 65 since WEB-28), e.g. `fotografi`, `kamera-lawas`, `harga-pasar`, `backup`. Each tag's `description` says what it is for (`Use for: …`, the brief's sub-theme); `name` comes back HTML-escaped (`Kata Sandi &amp; 2FA`). Plain device tags (`game`, `komputer`, `ponsel-tablet`, `audio`, `kamera`) are for current devices, the `-lawas` / `-vintage` tags for old ones. Agents cannot create tags (403 `nostek_no_new_terms`); a new tag is an owner decision. |
| `featured_image` | no | Path relative to the JSON file, WebP preferred (1600 px wide or more for link previews). Uploaded on create; on update only when `"replace_featured_image": true`. |
| `featured_image_alt` | with image | Describes the image for screen readers and SEO. |
| `status` | no | `pending` (default), `draft`, or for Author-role agents `publish` / `future`. |
| `date` | for `future` | `YYYY-MM-DDTHH:MM:SS`, site timezone. |

## Rules

- Once an article is published, the agent can no longer change it; edits go through an editor.
- An unknown category, Topic or tag fails the run before anything is written.
- Test new agents against `https://staging.nostek.id` first: Actions → **Agent publish test** sends `site/agent-test-article.json` as the agent (secrets `STAGING_AGENT_USER`, `STAGING_AGENT_APP_PASSWORD`). Expect `PASS create post: … status=pending`; a second run prints `PASS update post` for the same id. Delete the test article afterwards.

## Raw REST (for agents that do not run the tool)

This is the full set an agent needs. Base: `https://staging.nostek.id/wp-json/wp/v2` (later `https://nostek.id/wp-json/wp/v2`).

| Step | Call |
|---|---|
| Who am I | `GET /users/me?_fields=id,slug,name` |
| Find term ids | `GET /categories?slug=<slug>`, `GET /topic?slug=<slug>`, `GET /tags?slug=<slug>` |
| Find existing article | `GET /posts?nostek_source_id=<slug>&status=any&context=edit` |
| My pending posts | `GET /posts?author=<my id>&status=pending&context=edit&orderby=modified&order=desc&per_page=100&_fields=id,title,modified,link,meta` (other users' pending posts are never returned to an agent) |
| Upload image | `POST /media` (binary body, `Content-Type: image/webp`, `Content-Disposition: attachment; filename="cover.webp"`), then `POST /media/<id>` with `{"alt_text": "..."}` |
| Find an uploaded image | `GET /media?author=<id>&search=<words>&_fields=id,source_url,alt_text` (e.g. images uploaded by `hassan-nostek`) |
| Create | `POST /posts` with `title, content, excerpt, status, categories, topic, tags, featured_media, meta: {nostek_source_id, nostek_meta_description}` |
| Update | `POST /posts/<id>` with the same body (only while the post is not published) |
| Withdraw | `DELETE /posts/<id>` moves the agent's own unpublished post to the Trash |
| Pages | Same calls on `/pages`: find by `GET /pages?nostek_source_id=<id>&status=any&context=edit`, create `POST /pages` with `title, content, excerpt, status: "pending", parent, meta: {nostek_source_id, nostek_meta_description}`, update `POST /pages/<id>` while unpublished. A page's place in the site is `parent` (page id), not a category. |
| Site structure | `GET /pages?status=publish&per_page=100&_fields=id,slug,parent,title,link` (published pages); `GET /categories?per_page=100&_fields=id,slug,name,parent,count` and `GET /topic?per_page=100&_fields=id,slug,name,parent,count` (both carry `parent`, 0 = top level) |
| Menus (read only) | `GET /wp-json/nostek/v1/menus` (any user who can edit posts). See below. |

### `GET /wp-json/nostek/v1/menus`

The block theme keeps its menus as Navigation blocks inside the header and footer, so core's `/wp/v2/menus` does not list them (and needs `edit_theme_options`, which agents do not get). This route returns them as the site shows them; there is no write route.

```json
[
  {
    "location": "header-1",
    "part": "header",
    "class": "nk-mega",
    "source": "theme",
    "items": [
      {"id": 1, "parent": 0, "title": "Journal", "url": "/journal/", "description": "", "object": {"type": "page", "slug": "journal"}},
      {"id": 3, "parent": 1, "title": "News", "url": "/category/news/", "description": "Kabar teknologi terkini…", "object": {"type": "category", "slug": "news"}}
    ]
  }
]
```

- `location`: `header-N` / `footer-N`, N = the Nth menu in that part (footer has several columns).
- `items` is flat; `parent` is the `id` of the item above it (0 = top level). Ids are only valid inside one answer.
- `object`: the page, post, category, `topic`, `post_tag` or `product_cat` the link points to, or `null` for a link that is not site content yet (e.g. a page that does not exist) or external.
- `source`: `theme` = the menu comes from the theme in git (changed only through a PR); `custom` = someone edited it in the Site Editor.

### `tools/upload_media.py` (images agent)

```
export NOSTEK_WP_URL=https://staging.nostek.id NOSTEK_WP_USER=hassan-nostek
export NOSTEK_WP_APP_PASSWORD=...     # from the secret store, never in argv or logs
python3 tools/upload_media.py cover.webp --alt "Rak server dengan lampu hijau" --caption "Foto: Nostek (own work)"           # dry run: checks only
python3 tools/upload_media.py cover.webp --alt "Rak server dengan lampu hijau" --caption "Foto: Nostek (own work)" --apply   # upload
```

Reference behaviour for any tool that uploads images (A's tool follows it):

1. Checks before any upload (image rule 2026-10-31): the file is WebP (read from its header, not the name); long side 1600 to 2400 px; under 400 KB; `--alt` not empty (Indonesian, describes the image); `--caption` not empty (source or licence: own work, stock licence id, or AI-generated). Any failure: `FAIL …`, exit 1, nothing sent.
2. `POST /wp/v2/media` with the raw bytes, `Content-Type: image/webp`, `Content-Disposition: attachment; filename="<file name>"`.
3. `POST /wp/v2/media/<id>` with `{"alt_text": …, "caption": …}` (and `title` when given).
4. Output: `PASS media id=<id> url=<source_url> size=<W>x<H> bytes=<n>`. The writer puts `<id>` in `featured_media`.

Every run uploads a new item; re-running does not replace an earlier upload.

Authentication: HTTP Basic with the username and Application Password.

### Slugs of Pending Review posts and pages

WordPress drops the `slug` a user without publish rights sends for a Pending Review post or page, and on publish it would build the slug from the title. Nostek Core 0.3.1+ keeps the agent's slug: a `slug` sent with an unpublished post is stored in post meta `nostek_slug` (readable in `meta`, also writable directly), and on the **first** publish that slug is used (made unique with `-2` if another post already has it). So the publisher still sends exactly `{"status": "publish"}`. A publisher that sends its own `slug` wins. While pending, `slug` in the REST answer is empty; read `meta.nostek_slug` instead.

## Publishing (owner approval)

Design choice: **one publisher account**, not a person clicking in wp-admin, so "approve = live at once".

1. The writer agent sends the article: it lands as *Pending Review*.
2. The approval tool shows the owner the preview link and asks for Approve.
3. Only after the owner's tap, the tool, as `publisher-nostek`, sends `POST /posts/<id>` with exactly `{"status": "publish"}` (no other fields, so the reviewed text is what goes live) and reports `PASS published id=… link=…`.
4. Corrections or an unpublish on the owner's word: `POST /posts/<id>` with the change, or `{"status": "draft"}`.

The publisher's Application Password lives only in the approval tool's secret store. The owner can still publish by hand in wp-admin at any time; both paths give the same result.

## Staging mirror (owner decision PUB-1, 2026-10-31)

Articles are written for **production** (Pending Review → owner Approve). Theme, plugin and menu changes still go
staging first. To keep staging comparable, `tools/mirror_content.py` copies production's published Journal articles
**one way** to staging, and keeps the staging-only fixture articles in `site/fixtures/` published.

| Item | Rule |
|---|---|
| Production access | read only, public REST API, no credential |
| Staging access | `STAGING_WP_USER` / `STAGING_WP_APP_PASSWORD` (Administrator), environment `staging` |
| What is copied | posts in a Journal section (news, insights, guides-tutorials, reviews, founders-note, retro-stories, price-index) published on/after 2026-11-01 (`MIRROR_SINCE`) |
| Identity | staging post meta `nostek_source_id` = `mirror-<production id>`; `nostek_mirror_state` = production modified time + image id, so unchanged articles are skipped and images are uploaded once |
| Removed on production | `--prune` moves the staging copy to the Trash (only `mirror-…` posts; nothing written on staging is touched) |
| Content | production's rendered HTML; inline images keep their production / media-server URLs |
| Fixtures | `site/fixtures/fixture-*.json` (publish_article format): every block style, a very long title, a short post |
| When | Actions → **Content mirror**: nightly 02:00 WIB (writes), Run workflow = dry run unless *apply* is ticked |
| Output | one line per change, then `RESULT PASS created=… updated=… unchanged=… trashed=… skipped=… warn=…` or `RESULT FAIL …` |

The tool refuses to run when the target is not a `staging.` host or equals the production URL.
Offline test: `python3 tools/test_mirror_content.py` (CI runs it).
