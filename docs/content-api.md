# Content API: publishing Journal articles

How the Nostek virtual team (or any script) pushes articles to nostek.id.
Code goes through Git; **content goes through the WordPress REST API**, one WordPress user per agent.

## Accounts

| Item | Rule |
|---|---|
| User | One WordPress user per agent, e.g. `agent-writer-ai`. Never shared. |
| Role | `Nostek Agent` (from the Nostek Core plugin): can write, upload images, pick categories and Topics; **cannot publish**, so every article lands as *Pending Review*. A trusted agent may later be switched to *Author* to publish directly. |
| Credential | An Application Password on that user (Users → Profile → Application Passwords). Stored only in the agent's secret store. Revoke it there to cut access at once. |
| Terms | Categories, Topics and tags must already exist. Agents never create or rename terms. |

## Tool

`tools/publish_article.py` (Python 3, standard library only).

```
export NOSTEK_WP_URL=https://nostek.id          # or https://staging.nostek.id
export NOSTEK_WP_USER=agent-writer-ai
export NOSTEK_WP_APP_PASSWORD=...               # from the secret store, never in argv or logs
python3 tools/publish_article.py article.json            # dry run: validates, resolves terms
python3 tools/publish_article.py article.json --apply    # create or update
```

Output is one line: `DRY RUN ok: …`, `PASS create post: id=… status=… link=…`, `PASS update post …`, or `FAIL …` (exit 1).

## Article file

See [`examples/article.json`](examples/article.json).

| Field | Required | Notes |
|---|---|---|
| `slug` | yes | Lowercase words joined by `-`. Also the article's stable id: sending the same slug again **updates** that article instead of creating a duplicate (stored as post meta `nostek_source_id`). |
| `title` | yes | Keep the original Indonesian/English mix; do not translate. |
| `content` | yes | Gutenberg block markup (preferred) or plain HTML. Theme block styles can be used, e.g. `is-style-info-warning` on a group. |
| `excerpt` | no | One or two sentences for cards. |
| `category` | yes | One Journal section slug: `news`, `insights`, `guides-tutorials`, `reviews`, `founders-note`. |
| `topics` | no | Topic slugs, e.g. `ai`, `cybersecurity`, `cloud-infrastructure`, `networking`. |
| `tags` | no | Existing tag slugs. |
| `featured_image` | no | Path relative to the JSON file. Uploaded on create; on update only when `"replace_featured_image": true`. |
| `featured_image_alt` | with image | Describes the image for screen readers and SEO. |
| `status` | no | `pending` (default), `draft`, or for Author-role agents `publish` / `future`. |
| `date` | for `future` | `YYYY-MM-DDTHH:MM:SS`, site timezone. |

## Rules

- Once an article is published, the agent can no longer change it; edits go through an editor.
- An unknown category, Topic or tag fails the run before anything is written.
- Test new agents against `https://staging.nostek.id` first: Actions → **Agent publish test** sends `site/agent-test-article.json` as the agent (secrets `STAGING_AGENT_USER`, `STAGING_AGENT_APP_PASSWORD`). Expect `PASS create post: … status=pending`; a second run prints `PASS update post` for the same id. Delete the test article afterwards.

## Raw REST (for agents that do not run the tool)

| Step | Call |
|---|---|
| Find term ids | `GET /wp-json/wp/v2/categories?slug=<slug>`, `GET /wp-json/wp/v2/topic?slug=<slug>` |
| Find existing article | `GET /wp-json/wp/v2/posts?nostek_source_id=<slug>&status=any&context=edit` |
| Upload image | `POST /wp-json/wp/v2/media` (binary body, `Content-Disposition: attachment; filename="cover.jpg"`), then `POST /wp-json/wp/v2/media/<id>` with `{"alt_text": "..."}` |
| Create | `POST /wp-json/wp/v2/posts` with `title, content, excerpt, status, categories, topic, tags, featured_media, meta: {nostek_source_id}` |
| Update | `POST /wp-json/wp/v2/posts/<id>` with the same body |

Authentication: HTTP Basic with the username and Application Password.
