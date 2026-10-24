# Content API: publishing Journal articles

How the Nostek virtual team (or any script) pushes articles to nostek.id.
Code goes through Git; **content goes through the WordPress REST API**, one WordPress user per agent.

## Accounts

| Item | Rule |
|---|---|
| User | One WordPress user per agent, named `<agent>-nostek`: `usman-nostek` (articles, display name "Usman Tech-Writer"), `hassan-nostek` (images). Never shared. Staging first, then production. |
| Role | `Nostek Agent` (from the Nostek Core plugin): can write, upload images, pick categories and Topics; **cannot publish**, so every article lands as *Pending Review*. |
| Publisher | One account `publisher-nostek` with role `Nostek Publisher`, used only by the owner-approval tool, only after the owner approved an article (see *Publishing* below). |
| Credential | An Application Password on that user (Users → Profile → Application Passwords), created by the owner. Stored only in the agent's secret store, entered through a hidden prompt, never in chat, git or argv. Revoke it there to cut access at once. |
| Terms | Categories, Topics and tags must already exist. Agents never create or rename terms. |

### What each role can do

| Capability | Nostek Agent | Nostek Publisher |
|---|---|---|
| Create / edit own drafts and Pending Review posts | yes | yes |
| Upload media, set alt text, use any media item as featured image | yes | yes |
| Publish | no | yes |
| Edit or publish other users' posts | no | yes |
| Change a published post / move it back to draft | no | yes |
| Delete other users' posts, pages, users, plugins, themes, settings, terms | no | no |

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
| `content` | yes | Gutenberg block markup (preferred) or plain HTML. Theme block styles can be used, e.g. `is-style-info-warning` on a group. |
| `excerpt` | no | One or two sentences for cards; also the search snippet when `meta_description` is empty. |
| `meta_description` | no | Search snippet, at most 160 characters (post meta `nostek_meta_description`). Left out = the stored value is kept. |
| `category` | yes | One Journal section slug: `news`, `insights`, `guides-tutorials`, `reviews`, `founders-note`. |
| `topics` | no | Topic slugs, e.g. `ai`, `cybersecurity`, `cloud-infrastructure`, `networking`. |
| `tags` | no | Existing tag slugs. |
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

Authentication: HTTP Basic with the username and Application Password.

## Publishing (owner approval)

Design choice: **one publisher account**, not a person clicking in wp-admin, so "approve = live at once".

1. The writer agent sends the article: it lands as *Pending Review*.
2. The approval tool shows the owner the preview link and asks for Approve.
3. Only after the owner's tap, the tool, as `publisher-nostek`, sends `POST /posts/<id>` with exactly `{"status": "publish"}` (no other fields, so the reviewed text is what goes live) and reports `PASS published id=… link=…`.
4. Corrections or an unpublish on the owner's word: `POST /posts/<id>` with the change, or `{"status": "draft"}`.

The publisher's Application Password lives only in the approval tool's secret store. The owner can still publish by hand in wp-admin at any time; both paths give the same result.
