<?php
/**
 * Title: Homepage (editorial-first)
 * Slug: nostek/home-editorial
 * Categories: nostek
 * Description: Hero slider of the latest articles and a news ticker, then the Journal grid (about two thirds of the page), Topics, Services and Market.
 */
?>
<!-- wp:heading {"level":1,"className":"screen-reader-text"} -->
<h1 class="wp-block-heading screen-reader-text">Nostek ID: teknologi, pengalaman &amp; perspektif</h1>
<!-- /wp:heading -->

<!-- wp:pattern {"slug":"nostek/hero-slider"} /-->

<!-- wp:pattern {"slug":"nostek/news-ticker"} /-->

<!-- wp:pattern {"slug":"nostek/section-divider"} /-->

<!-- wp:query {"queryId":21,"query":{"perPage":6,"offset":3,"postType":"post","order":"desc","orderBy":"date","inherit":false},"align":"wide"} -->
<div class="wp-block-query alignwide">
	<!-- wp:post-template {"layout":{"type":"grid","columnCount":3,"minimumColumnWidth":"16rem"}} -->
		<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/10","className":"is-style-sepia"} /-->
		<!-- wp:post-terms {"term":"topic","className":"is-style-kicker"} /-->
		<!-- wp:post-title {"level":3,"isLink":true,"fontSize":"large","style":{"elements":{"link":{"color":{"text":"var:preset|color|ink"}}}}} /-->
		<!-- wp:post-excerpt {"excerptLength":22,"fontSize":"small"} /-->
		<!-- wp:post-date /-->
	<!-- /wp:post-template -->
</div>
<!-- /wp:query -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons">
	<!-- wp:button -->
	<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/journal/">Semua artikel Journal</a></div>
	<!-- /wp:button -->
</div>
<!-- /wp:buttons -->

<!-- wp:heading {"className":"is-style-divider"} -->
<h2 class="wp-block-heading is-style-divider">Topics</h2>
<!-- /wp:heading -->

<!-- wp:nostek/topics-list /-->

<!-- wp:pattern {"slug":"nostek/cta-band"} /-->

<!-- wp:heading {"className":"is-style-divider"} -->
<h2 class="wp-block-heading is-style-divider">Services &amp; Market</h2>
<!-- /wp:heading -->

<!-- wp:pattern {"slug":"nostek/banner-boxes"} /-->
