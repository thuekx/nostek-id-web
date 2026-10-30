<?php
/**
 * Title: News ticker (latest titles)
 * Slug: nostek/news-ticker
 * Categories: nostek
 * Description: A running strip of the latest Journal titles. Pauses on hover; static for visitors who prefer reduced motion.
 */
?>
<!-- wp:group {"align":"wide","className":"nk-ticker","style":{"spacing":{"padding":{"top":"0","bottom":"0"},"margin":{"top":"0"}}},"layout":{"type":"default"}} -->
<div class="wp-block-group alignwide nk-ticker" style="margin-top:0;padding-top:0;padding-bottom:0">
	<!-- wp:paragraph {"className":"nk-ticker__label"} -->
	<p class="nk-ticker__label">Terkini</p>
	<!-- /wp:paragraph -->
	<!-- wp:query {"queryId":31,"query":{"perPage":8,"postType":"post","order":"desc","orderBy":"date","inherit":false}} -->
	<div class="wp-block-query">
		<!-- wp:post-template -->
			<!-- wp:post-title {"level":3,"isLink":true,"fontFamily":"display"} /-->
		<!-- /wp:post-template -->
	</div>
	<!-- /wp:query -->
</div>
<!-- /wp:group -->
