<?php
/**
 * Title: Homepage (editorial-first)
 * Slug: nostek/home-editorial
 * Categories: nostek
 * Description: Journal first (about two thirds of the page), then Topics, Services and Market.
 */
?>
<!-- wp:group {"align":"full","className":"is-style-sunburst","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull is-style-sunburst" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--50)">
	<!-- wp:heading {"level":1,"textAlign":"center","className":"is-style-title-3d"} -->
	<h1 class="wp-block-heading has-text-align-center is-style-title-3d">Nostek</h1>
	<!-- /wp:heading -->
	<!-- wp:paragraph {"className":"is-style-ribbon"} -->
	<p class="is-style-ribbon">Teknologi, pengalaman &amp; perspektif</p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"nostek/section-divider"} /-->

<!-- wp:query {"queryId":20,"query":{"perPage":1,"postType":"post","order":"desc","orderBy":"date","inherit":false,"sticky":""},"align":"wide"} -->
<div class="wp-block-query alignwide">
	<!-- wp:post-template -->
		<!-- wp:columns {"verticalAlignment":"center"} -->
		<div class="wp-block-columns are-vertically-aligned-center">
			<!-- wp:column {"width":"58%"} -->
			<div class="wp-block-column" style="flex-basis:58%">
				<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/10","className":"is-style-sepia"} /-->
			</div>
			<!-- /wp:column -->
			<!-- wp:column -->
			<div class="wp-block-column">
				<!-- wp:post-terms {"term":"topic","className":"is-style-kicker"} /-->
				<!-- wp:post-title {"level":2,"isLink":true,"fontSize":"xx-large","style":{"elements":{"link":{"color":{"text":"var:preset|color|ink"}}}}} /-->
				<!-- wp:post-excerpt {"excerptLength":36} /-->
				<!-- wp:post-date /-->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->
	<!-- /wp:post-template -->
</div>
<!-- /wp:query -->

<!-- wp:query {"queryId":21,"query":{"perPage":6,"offset":1,"postType":"post","order":"desc","orderBy":"date","inherit":false},"align":"wide"} -->
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
