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
	<!-- wp:query {"queryId":32,"query":{"perPage":6,"postType":"post","order":"desc","orderBy":"date","inherit":false},"className":"nk-cards"} -->
	<div class="wp-block-query nk-cards">
		<!-- wp:post-template -->
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
</div>
<!-- /wp:group -->
