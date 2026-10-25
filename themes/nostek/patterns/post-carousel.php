<?php
/**
 * Title: Journal carousel (6 posts)
 * Slug: nostek/post-carousel
 * Categories: nostek
 * Description: Six latest articles as retro cards in a sliding row with arrows.
 */
?>
<!-- wp:group {"align":"wide","className":"is-style-carousel","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide is-style-carousel">
	<!-- wp:query {"queryId":32,"query":{"perPage":6,"postType":"post","order":"desc","orderBy":"date","inherit":false}} -->
	<div class="wp-block-query">
		<!-- wp:post-template -->
			<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/10","className":"is-style-sepia"} /-->
			<!-- wp:post-terms {"term":"topic","className":"is-style-kicker"} /-->
			<!-- wp:post-title {"level":3,"isLink":true,"fontSize":"large","style":{"elements":{"link":{"color":{"text":"var:preset|color|ink"}}}}} /-->
			<!-- wp:post-date /-->
		<!-- /wp:post-template -->
	</div>
	<!-- /wp:query -->
</div>
<!-- /wp:group -->
