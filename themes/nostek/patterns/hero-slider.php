<?php
/**
 * Title: Hero slider (latest articles)
 * Slug: nostek/hero-slider
 * Categories: nostek, featured
 * Description: Full-width slider of the latest Journal articles, featured image as background. Autoplays, pauses on hover.
 */
?>
<!-- wp:group {"align":"full","className":"is-style-slider nk-hero","style":{"spacing":{"padding":{"top":"0","bottom":"0"}}},"layout":{"type":"default"}} -->
<div class="wp-block-group alignfull is-style-slider nk-hero" style="padding-top:0;padding-bottom:0">
	<!-- wp:query {"queryId":30,"query":{"perPage":3,"postType":"post","order":"desc","orderBy":"date","inherit":false},"align":"full"} -->
	<div class="wp-block-query alignfull">
		<!-- wp:post-template -->
			<!-- wp:cover {"useFeaturedImage":true,"dimRatio":70,"overlayColor":"teal-dark","minHeight":560,"contentPosition":"bottom left","isDark":true,"layout":{"type":"default"}} -->
			<div class="wp-block-cover is-dark has-custom-content-position is-position-bottom-left" style="min-height:560px"><span aria-hidden="true" class="wp-block-cover__background has-teal-dark-background-color has-background-dim-70 has-background-dim"></span><div class="wp-block-cover__inner-container">
				<!-- wp:group {"className":"nk-hero__card","layout":{"type":"default"}} -->
				<div class="wp-block-group nk-hero__card">
					<!-- wp:post-terms {"term":"topic","className":"is-style-kicker"} /-->
					<!-- wp:post-title {"level":2,"isLink":true,"style":{"typography":{"lineHeight":"0.98"}},"fontSize":"xx-large"} /-->
					<!-- wp:post-excerpt {"excerptLength":26} /-->
					<!-- wp:read-more {"content":"Baca artikel"} /-->
				</div>
				<!-- /wp:group -->
			</div></div>
			<!-- /wp:cover -->
		<!-- /wp:post-template -->
	</div>
	<!-- /wp:query -->
</div>
<!-- /wp:group -->
