<?php
/**
 * Title: Brand hero slider (full width)
 * Slug: nostek/brand-hero-slider
 * Categories: nostek, featured
 * Description: Edge-to-edge hero slides: retro collage art on the right, framed paper caption on the left. Each slide is a Cover block: swap its image, edit the text, or duplicate it to add a slide.
 */

$nostek_img = get_theme_file_uri( 'assets/img/brand/' );
?>
<!-- wp:group {"align":"full","className":"is-style-slider nk-hero nk-stage","style":{"spacing":{"padding":{"top":"0","bottom":"0"},"margin":{"top":"0"}}},"layout":{"type":"default"}} -->
<div class="wp-block-group alignfull is-style-slider nk-hero nk-stage" style="margin-top:0;padding-top:0;padding-bottom:0">
	<!-- wp:cover {"url":"<?php echo esc_url( $nostek_img . 'hero-wide.webp' ); ?>","dimRatio":0,"focalPoint":{"x":0.66,"y":0.5},"minHeight":640,"contentPosition":"center left","className":"nk-stage__slide nk-stage__slide--brand","layout":{"type":"default"}} -->
	<div class="wp-block-cover has-custom-content-position is-position-center-left nk-stage__slide nk-stage__slide--brand" style="min-height:640px"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim"></span><img class="wp-block-cover__image-background" alt="" src="<?php echo esc_url( $nostek_img . 'hero-wide.webp' ); ?>" style="object-position:66% 50%" data-object-fit="cover" data-object-position="66% 50%"/><div class="wp-block-cover__inner-container">
		<!-- wp:group {"className":"nk-stage__card","layout":{"type":"default"}} -->
		<div class="wp-block-group nk-stage__card">
			<!-- wp:paragraph {"className":"nk-stage__kicker"} -->
			<p class="nk-stage__kicker"><?php echo esc_html_x( 'Retro Tech – Modern Twist', 'hero slide kicker', 'nostek' ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:heading {"level":1,"className":"is-style-title-3d nk-stage__title"} -->
			<h1 class="wp-block-heading is-style-title-3d nk-stage__title"><?php echo esc_html_x( 'Nostek ID: Teknologi, Pengalaman & Perspektif', 'hero slide title', 'nostek' ); ?></h1>
			<!-- /wp:heading -->
			<!-- wp:paragraph {"className":"nk-stage__lede"} -->
			<p class="nk-stage__lede"><?php echo esc_html_x( 'Tulisan, ulasan, dan perspektif tentang teknologi yang lebih dekat dengan kehidupan nyata.', 'hero slide lede', 'nostek' ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:buttons -->
			<div class="wp-block-buttons">
				<!-- wp:button {"className":"is-style-offset"} -->
				<div class="wp-block-button is-style-offset"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/journal/' ) ); ?>"><?php echo esc_html_x( 'Baca artikel →', 'hero slide button', 'nostek' ); ?></a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:group -->
	</div></div>
	<!-- /wp:cover -->
	<!-- wp:cover {"url":"<?php echo esc_url( $nostek_img . 'hero-portrait.webp' ); ?>","dimRatio":0,"focalPoint":{"x":0.5,"y":0.38},"minHeight":640,"contentPosition":"center left","className":"nk-stage__slide nk-stage__slide--poster","layout":{"type":"default"}} -->
	<div class="wp-block-cover has-custom-content-position is-position-center-left nk-stage__slide nk-stage__slide--poster" style="min-height:640px"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim"></span><img class="wp-block-cover__image-background" alt="" src="<?php echo esc_url( $nostek_img . 'hero-portrait.webp' ); ?>" style="object-position:50% 38%" data-object-fit="cover" data-object-position="50% 38%"/><div class="wp-block-cover__inner-container">
		<!-- wp:group {"className":"nk-stage__card","layout":{"type":"default"}} -->
		<div class="wp-block-group nk-stage__card">
			<!-- wp:paragraph {"className":"nk-stage__kicker"} -->
			<p class="nk-stage__kicker"><?php echo esc_html_x( 'Journal', 'hero slide kicker', 'nostek' ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:heading {"className":"is-style-title-3d nk-stage__title"} -->
			<h2 class="wp-block-heading is-style-title-3d nk-stage__title"><?php echo esc_html_x( 'Ulasan jujur, panduan yang bisa langsung dipakai', 'hero slide title', 'nostek' ); ?></h2>
			<!-- /wp:heading -->
			<!-- wp:paragraph {"className":"nk-stage__lede"} -->
			<p class="nk-stage__lede"><?php echo esc_html_x( 'Dari jaringan rumah sampai cloud untuk usaha: dicoba dulu, baru ditulis.', 'hero slide lede', 'nostek' ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:buttons -->
			<div class="wp-block-buttons">
				<!-- wp:button {"className":"is-style-offset"} -->
				<div class="wp-block-button is-style-offset"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/topics/' ) ); ?>"><?php echo esc_html_x( 'Jelajahi topik →', 'hero slide button', 'nostek' ); ?></a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:group -->
	</div></div>
	<!-- /wp:cover -->
</div>
<!-- /wp:group -->
