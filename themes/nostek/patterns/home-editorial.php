<?php
/**
 * Title: Homepage (editorial-first)
 * Slug: nostek/home-editorial
 * Categories: nostek
 * Description: Full-width brand hero slider and news ticker, the latest articles as journal cards, a slider of highlights, then Topics, Services and Market.
 */
?>
<!-- wp:pattern {"slug":"nostek/brand-hero-slider"} /-->

<!-- wp:pattern {"slug":"nostek/news-ticker"} /-->

<!-- wp:heading {"className":"is-style-divider"} -->
<h2 class="wp-block-heading is-style-divider">Artikel Terbaru</h2>
<!-- /wp:heading -->

<!-- wp:query {"queryId":21,"query":{"perPage":8,"postType":"post","order":"desc","orderBy":"date","inherit":false},"align":"wide","className":"nk-cards"} -->
<div class="wp-block-query alignwide nk-cards">
	<!-- wp:post-template {"layout":{"type":"grid","columnCount":4,"minimumColumnWidth":"15rem"}} -->
		<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/10","className":"nk-halftone"} /-->
		<!-- wp:post-terms {"term":"category","className":"nk-badge"} /-->
		<!-- wp:post-title {"level":3,"isLink":true} /-->
		<!-- wp:group {"className":"nk-card__meta","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
		<div class="wp-block-group nk-card__meta">
			<!-- wp:post-date /-->
			<!-- wp:read-more {"content":"Baca artikel"} /-->
		</div>
		<!-- /wp:group -->
	<!-- /wp:post-template -->
</div>
<!-- /wp:query -->

<!-- wp:heading {"className":"is-style-divider"} -->
<h2 class="wp-block-heading is-style-divider">Sorotan</h2>
<!-- /wp:heading -->

<!-- wp:pattern {"slug":"nostek/hero-slider"} /-->

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

<!-- wp:nostek/topics-list {"align":"wide"} /-->

<!-- wp:pattern {"slug":"nostek/cta-band"} /-->

<!-- wp:heading {"className":"is-style-divider"} -->
<h2 class="wp-block-heading is-style-divider">Services &amp; Market</h2>
<!-- /wp:heading -->

<!-- wp:pattern {"slug":"nostek/banner-boxes"} /-->
