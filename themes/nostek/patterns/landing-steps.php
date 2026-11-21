<?php
/**
 * Title: Landing: how we work band
 * Slug: nostek/landing-steps
 * Categories: nostek
 * Description: Full-width teal spray band with three numbered steps for landing pages.
 */
$nostek_steps = array(
	array( 'Ngobrol dulu', 'Ceritakan kebutuhan dan kondisi saat ini. Gratis, tanpa ikatan.' ),
	array( 'Rencana jelas', 'Kami kirim rancangan, biaya dan jadwal yang mudah dibaca.' ),
	array( 'Kerja & rawat', 'Dipasang rapi, diuji, lalu dirawat dengan laporan rutin.' ),
);
?>
<!-- wp:group {"align":"full","className":"is-style-band nk-landing-band","backgroundColor":"teal-dark","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull is-style-band nk-landing-band has-teal-dark-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:paragraph {"align":"center","className":"is-style-kicker"} -->
	<p class="has-text-align-center is-style-kicker">Cara kami bekerja</p>
	<!-- /wp:paragraph -->
	<!-- wp:heading {"textAlign":"center","className":"is-style-title-3d","textColor":"cream"} -->
	<h2 class="wp-block-heading has-text-align-center is-style-title-3d has-cream-color has-text-color">Tiga langkah, tanpa ribet</h2>
	<!-- /wp:heading -->
	<!-- wp:columns {"align":"wide","className":"nk-landing-steps"} -->
	<div class="wp-block-columns alignwide nk-landing-steps">
	<?php foreach ( $nostek_steps as $nostek_i => $nostek_step ) : ?>
		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:paragraph {"className":"nk-landing-steps__num"} -->
			<p class="nk-landing-steps__num"><?php echo esc_html( (string) ( $nostek_i + 1 ) ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:heading {"level":3} -->
			<h3 class="wp-block-heading"><?php echo esc_html( $nostek_step[0] ); ?></h3>
			<!-- /wp:heading -->
			<!-- wp:paragraph -->
			<p><?php echo esc_html( $nostek_step[1] ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->
	<?php endforeach; ?>
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
