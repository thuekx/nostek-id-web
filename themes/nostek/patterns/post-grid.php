<?php
/**
 * Title: Latest Journal (3 posts)
 * Slug: nostek/post-grid
 * Categories: nostek
 * Description: Three latest articles as retro cards.
 */
?>
<!-- wp:query {"queryId":10,"query":{"perPage":3,"postType":"post","order":"desc","orderBy":"date","inherit":false},"align":"wide"} -->
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
