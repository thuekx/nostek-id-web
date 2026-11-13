<?php
/**
 * Title: Blog checkerboard
 * Slug: nostek/el-checkerboard
 * Categories: nostek-elements
 * Keywords: blog, artikel, checkerboard, grid, journal
 * Description: Artikel terbaru dalam pola papan catur: gambar dan teks bergantian, empat warna. Otomatis mengambil artikel terbaru.
 */
?>
<!-- wp:query {"queryId":38,"query":{"perPage":4,"postType":"post","order":"desc","orderBy":"date","inherit":false},"align":"wide","className":"nk-checker"} -->
<div class="wp-block-query alignwide nk-checker"><!-- wp:post-template -->
<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"1"} /-->
<!-- wp:group {"className":"nk-checker__text","layout":{"type":"default"}} -->
<div class="wp-block-group nk-checker__text"><!-- wp:post-terms {"term":"topic"} /-->
<!-- wp:post-title {"level":3,"isLink":true} /-->
<!-- wp:post-excerpt {"excerptLength":14} /-->
<!-- wp:read-more {"content":"Baca artikel →"} /--></div>
<!-- /wp:group -->
<!-- /wp:post-template --></div>
<!-- /wp:query -->
