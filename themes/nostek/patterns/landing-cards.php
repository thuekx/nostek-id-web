<?php
/**
 * Title: Landing: service cards
 * Slug: nostek/landing-cards
 * Categories: nostek
 * Description: Section title and a row of framed cards (number, title, short line, link) for landing pages.
 */
$nostek_cards = array(
	array( 'Network Infrastructure', 'Desain, pasang dan rawat jaringan kantor, Wi-Fi dan firewall.', '/services/network-infrastructure/' ),
	array( 'IT Managed Services', 'Server, backup 3-2-1 dan monitoring yang dirawat rutin.', '/services/it-managed-services/' ),
	array( 'Photography', 'Foto produk, acara dan profil bisnis dengan sentuhan editorial.', '/services/photography/' ),
	array( 'Videography', 'Video profil, liputan acara dan konten pendek untuk media sosial.', '/services/videography/' ),
	array( 'Nostek AI', 'Asisten AI dan otomasi untuk tim kecil dan operasional IT.', '/services/nostek-ai/' ),
);
?>
<!-- wp:group {"align":"wide","className":"nk-landing-section","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide nk-landing-section">
	<!-- wp:heading {"className":"is-style-divider"} -->
	<h2 class="wp-block-heading is-style-divider">Layanan Kami</h2>
	<!-- /wp:heading -->
	<!-- wp:group {"className":"nk-landing-cards","layout":{"type":"default"}} -->
	<div class="wp-block-group nk-landing-cards">
	<?php foreach ( $nostek_cards as $nostek_i => $nostek_card ) : ?>
		<!-- wp:group {"className":"nk-landing-card","layout":{"type":"default"}} -->
		<div class="wp-block-group nk-landing-card">
			<!-- wp:paragraph {"className":"nk-landing-card__num"} -->
			<p class="nk-landing-card__num"><?php echo esc_html( sprintf( '%02d', $nostek_i + 1 ) ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:heading {"level":3} -->
			<h3 class="wp-block-heading"><?php echo esc_html( $nostek_card[0] ); ?></h3>
			<!-- /wp:heading -->
			<!-- wp:paragraph -->
			<p><?php echo esc_html( $nostek_card[1] ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"className":"nk-landing-card__link"} -->
			<p class="nk-landing-card__link"><a href="<?php echo esc_url( home_url( $nostek_card[2] ) ); ?>">Selengkapnya</a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
	<?php endforeach; ?>
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
