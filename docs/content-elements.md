# Content elements: cheat sheet for editors and agents

Every element below works in two ways:

- **In the editor (people):** click **+** → **Patterns** → category **Nostek – Elemen**, then click the element. Or add a normal block (List, Details, Image, Group…) and pick the Nostek style in the right panel under **Styles**.
- **Over the REST API (agents such as Usman):** put the block markup below in `content` (see [content-api.md](content-api.md)). Copy it as is and change only the text, numbers and URLs. Never add `<script>` or `style=""` of your own. The look comes from the theme.

Nostek blocks (`nostek/...`) come from the Nostek Core plugin. If the plugin is off, they render nothing; core blocks with a Nostek style fall back to the plain WordPress look.

## Slider (`nostek/slider`)

Each slide is a **Cover** block. In the editor: **Tambah slide** button, arrows to reorder, ⋮ → Delete to remove. Settings (right panel): autoplay on/off, seconds between slides.

```html
<!-- wp:nostek/slider {"align":"full","autoplay":true,"interval":6.5} -->
<!-- wp:cover {"overlayColor":"teal-dark","minHeight":460,"contentPosition":"center left","isDark":true} -->
<div class="wp-block-cover is-dark has-custom-content-position is-position-center-left" style="min-height:460px"><span aria-hidden="true" class="wp-block-cover__background has-teal-dark-background-color has-background-dim-100 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:heading {"className":"is-style-title-3d","textColor":"cream"} -->
<h2 class="wp-block-heading is-style-title-3d has-cream-color has-text-color">Judul slide</h2>
<!-- /wp:heading --></div></div>
<!-- /wp:cover -->
<!-- (more wp:cover blocks = more slides) -->
<!-- /wp:nostek/slider -->
```

## Tabs (`nostek/tabs` + `nostek/tab`)

```html
<!-- wp:nostek/tabs -->
<!-- wp:nostek/tab {"title":"Ringkasan"} -->
<!-- wp:paragraph --><p>Isi tab pertama.</p><!-- /wp:paragraph -->
<!-- /wp:nostek/tab -->
<!-- wp:nostek/tab {"title":"Spesifikasi"} -->
<!-- wp:paragraph --><p>Isi tab kedua.</p><!-- /wp:paragraph -->
<!-- /wp:nostek/tab -->
<!-- /wp:nostek/tabs -->
```

## Progress bar (`nostek/progress`)

`value` 0–100, `color` one of `teal`, `red`, `mustard`, `teal-dark`, `blue`, `green`, `ink`.

```html
<!-- wp:nostek/progress {"label":"Uptime layanan","value":99,"color":"teal"} /-->
```

## Donut / pie chart (`nostek/donut`)

Shares are computed from the numbers (they do not have to add up to 100). Colours follow the order teal, red, mustard, teal-dark, blue, green.

```html
<!-- wp:nostek/donut {"title":"Pembaca per topik","items":[{"label":"Networking","value":38},{"label":"AI","value":18}],"note":"Sumber: data internal, Okt 2026."} /-->
```

## Accordion and toggle (core Details + style)

`is-style-accordion` = numbered rows (consecutive Details blocks are numbered 01, 02 …). `is-style-toggle` = box with a switch.

```html
<!-- wp:details {"className":"is-style-accordion"} -->
<details class="wp-block-details is-style-accordion"><summary>Pertanyaan?</summary><!-- wp:paragraph -->
<p>Jawaban.</p>
<!-- /wp:paragraph --></details>
<!-- /wp:details -->
```

## Info boxes / cards (core Group + style)

Styles: `is-style-card-frame` (double frame), `is-style-card-teal`, `is-style-card-stamp` (red offset shadow). Short notes: `is-style-info`, `is-style-info-success`, `is-style-info-warning`, `is-style-info-error`.

```html
<!-- wp:group {"className":"is-style-card-stamp","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-card-stamp"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Judul</h3>
<!-- /wp:heading -->
<!-- wp:paragraph --><p>Teks singkat.</p><!-- /wp:paragraph --></div>
<!-- /wp:group -->
```

## Lists (core List + style)

`is-style-check` (✓), `is-style-arrow` (➜), `is-style-steps` (01, 02 … use `"ordered":true` and `<ol>`).

```html
<!-- wp:list {"className":"is-style-check"} -->
<ul class="wp-block-list is-style-check"><!-- wp:list-item --><li>Backup harian</li><!-- /wp:list-item --></ul>
<!-- /wp:list -->
```

## Image "Polaroid" and video "TV jadul"

```html
<!-- wp:image {"id":123,"sizeSlug":"large","className":"is-style-polaroid"} -->
<figure class="wp-block-image size-large is-style-polaroid"><img src="https://…/foto.webp" alt="Keterangan gambar" class="wp-image-123"/><figcaption class="wp-element-caption">Keterangan tulisan tangan</figcaption></figure>
<!-- /wp:image -->

<!-- wp:embed {"url":"https://www.youtube.com/watch?v=VIDEO_ID","type":"video","providerNameSlug":"youtube","responsive":true,"className":"is-style-tv wp-embed-aspect-16-9 wp-has-aspect-ratio"} -->
<figure class="wp-block-embed is-type-video is-provider-youtube wp-block-embed-youtube is-style-tv wp-embed-aspect-16-9 wp-has-aspect-ratio"><div class="wp-block-embed__wrapper">
https://www.youtube.com/watch?v=VIDEO_ID
</div></figure>
<!-- /wp:embed -->
```

## Section with torn paper edges (core Group + style)

Full-width band, torn edge top and bottom. Without a background colour it is dark ink with cream text; pick another colour in the editor if needed.

```html
<!-- wp:group {"align":"full","className":"is-style-torn","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull is-style-torn"><!-- wp:heading {"className":"is-style-divider"} -->
<h2 class="wp-block-heading is-style-divider">Judul bagian</h2>
<!-- /wp:heading --></div>
<!-- /wp:group -->
```

## Blog checkerboard

Pages only (it lists the latest articles itself): insert the pattern **Blog checkerboard** in the editor. Agents do not need it inside articles.
