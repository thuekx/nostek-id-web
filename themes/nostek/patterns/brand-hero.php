<?php
/**
 * Title: Brand hero (Retro Tech Editorial)
 * Slug: nostek/brand-hero
 * Categories: nostek, featured
 * Description: Double-framed opening box: 3D headline, mono lede, offset "Baca artikel" button, retro N collage and a mono side column.
 */
?>
<!-- wp:group {"align":"wide","className":"is-style-frame nk-brand-hero","style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}},"layout":{"type":"default"}} -->
<div class="wp-block-group alignwide is-style-frame nk-brand-hero" style="margin-top:var(--wp--preset--spacing--40)">
	<!-- wp:columns {"verticalAlignment":"center"} -->
	<div class="wp-block-columns are-vertically-aligned-center">
		<!-- wp:column {"verticalAlignment":"center","width":"48%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:48%">
			<!-- wp:heading {"level":1,"className":"is-style-title-3d nk-brand-hero__title"} -->
			<h1 class="wp-block-heading is-style-title-3d nk-brand-hero__title"><?php echo esc_html_x( 'Nostek ID: Teknologi, Pengalaman & Perspektif', 'brand hero title', 'nostek' ); ?></h1>
			<!-- /wp:heading -->
			<!-- wp:paragraph {"className":"nk-brand-hero__lede"} -->
			<p class="nk-brand-hero__lede"><?php echo esc_html_x( 'Tulisan, ulasan, dan perspektif tentang teknologi yang lebih dekat dengan kehidupan nyata.', 'brand hero lede', 'nostek' ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:buttons -->
			<div class="wp-block-buttons">
				<!-- wp:button {"className":"is-style-offset"} -->
				<div class="wp-block-button is-style-offset"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/journal/' ) ); ?>"><?php echo esc_html_x( 'Baca artikel →', 'brand hero button', 'nostek' ); ?></a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:column -->
		<!-- wp:column {"verticalAlignment":"center","width":"40%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:40%">
			<!-- wp:image {"sizeSlug":"full","className":"nk-brand-hero__art"} -->
			<figure class="wp-block-image size-full nk-brand-hero__art"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/img/brand/hero-art.webp' ) ); ?>" alt="<?php echo esc_attr_x( 'Kolase retro: huruf N Nostek, komputer jadul dan gedung bergaya cetak halftone', 'brand hero image alt', 'nostek' ); ?>" width="704" height="434" fetchpriority="high"/></figure>
			<!-- /wp:image -->
		</div>
		<!-- /wp:column -->
		<!-- wp:column {"verticalAlignment":"top","width":"12%","className":"nk-brand-hero__side"} -->
		<div class="wp-block-column is-vertically-aligned-top nk-brand-hero__side" style="flex-basis:12%">
			<!-- wp:list {"className":"nk-brand-hero__tags"} -->
			<ul class="wp-block-list nk-brand-hero__tags">
				<!-- wp:list-item --><li><?php echo esc_html_x( 'Teknologi', 'brand hero side', 'nostek' ); ?></li><!-- /wp:list-item -->
				<!-- wp:list-item --><li><?php echo esc_html_x( 'Pengalaman', 'brand hero side', 'nostek' ); ?></li><!-- /wp:list-item -->
				<!-- wp:list-item --><li><?php echo esc_html_x( 'Perspektif', 'brand hero side', 'nostek' ); ?></li><!-- /wp:list-item -->
				<!-- wp:list-item --><li><?php echo esc_html_x( 'Untuk hari ini', 'brand hero side', 'nostek' ); ?></li><!-- /wp:list-item -->
				<!-- wp:list-item --><li><?php echo esc_html_x( 'dan besok', 'brand hero side', 'nostek' ); ?></li><!-- /wp:list-item -->
			</ul>
			<!-- /wp:list -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
