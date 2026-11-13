<?php
/**
 * Title: Info box (3 kartu)
 * Slug: nostek/el-info-boxes
 * Categories: nostek-elements
 * Keywords: info box, kartu, card, layanan
 * Description: Tiga kartu info: bingkai ganda, teal, stempel merah. Ganti ikon, judul dan teks; gaya kartu bisa diganti di panel Styles.
 */
$nostek_icon = get_theme_file_uri( 'assets/img/icons/' );
?>
<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide">
	<!-- wp:column -->
	<div class="wp-block-column">
		<!-- wp:group {"className":"is-style-card-frame","layout":{"type":"default"}} -->
		<div class="wp-block-group is-style-card-frame">
			<!-- wp:image {"width":"58px","sizeSlug":"full","className":"is-style-icon"} -->
			<figure class="wp-block-image size-full is-resized is-style-icon"><img src="<?php echo esc_url( $nostek_icon . 'monitor.svg' ); ?>" alt="" style="width:58px"/></figure>
			<!-- /wp:image -->
			<!-- wp:heading {"level":3} -->
			<h3 class="wp-block-heading">Managed IT</h3>
			<!-- /wp:heading -->
			<!-- wp:paragraph -->
			<p>Server, jaringan dan backup dikelola rapi, dipantau setiap hari.</p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"className":"is-style-kicker","textColor":"red"} -->
			<p class="is-style-kicker has-red-color has-text-color"><a href="/services/">Selengkapnya</a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:column -->
	<!-- wp:column -->
	<div class="wp-block-column">
		<!-- wp:group {"className":"is-style-card-teal","layout":{"type":"default"}} -->
		<div class="wp-block-group is-style-card-teal">
			<!-- wp:image {"width":"58px","sizeSlug":"full","className":"is-style-icon"} -->
			<figure class="wp-block-image size-full is-resized is-style-icon"><img src="<?php echo esc_url( $nostek_icon . 'shield.svg' ); ?>" alt="" style="width:58px"/></figure>
			<!-- /wp:image -->
			<!-- wp:heading {"level":3} -->
			<h3 class="wp-block-heading">Keamanan</h3>
			<!-- /wp:heading -->
			<!-- wp:paragraph -->
			<p>Audit, firewall dan kebijakan akses untuk bisnis kecil dan menengah.</p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"className":"is-style-kicker","textColor":"mustard"} -->
			<p class="is-style-kicker has-mustard-color has-text-color"><a href="/services/">Selengkapnya</a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:column -->
	<!-- wp:column -->
	<div class="wp-block-column">
		<!-- wp:group {"className":"is-style-card-stamp","layout":{"type":"default"}} -->
		<div class="wp-block-group is-style-card-stamp">
			<!-- wp:image {"width":"58px","sizeSlug":"full","className":"is-style-icon"} -->
			<figure class="wp-block-image size-full is-resized is-style-icon"><img src="<?php echo esc_url( $nostek_icon . 'globe.svg' ); ?>" alt="" style="width:58px"/></figure>
			<!-- /wp:image -->
			<!-- wp:heading {"level":3} -->
			<h3 class="wp-block-heading">Jaringan</h3>
			<!-- /wp:heading -->
			<!-- wp:paragraph -->
			<p>Desain, instalasi dan perawatan jaringan kantor dan ISP.</p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"className":"is-style-kicker","textColor":"red"} -->
			<p class="is-style-kicker has-red-color has-text-color"><a href="/services/">Selengkapnya</a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:column -->
</div>
<!-- /wp:columns -->
