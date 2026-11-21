<?php
/**
 * Title: Page rail: help box and Services
 * Slug: nostek/page-rail
 * Categories: nostek
 * Inserter: no
 * Description: Right rail of the reading page layout (WEB-40): last update, a retro help box with links, and the Services box.
 */
?>
<!-- wp:group {"className":"nk-rail-info","layout":{"type":"default"}} -->
<div class="wp-block-group nk-rail-info">
	<!-- wp:paragraph {"className":"nk-rail__title"} -->
	<p class="nk-rail__title">Info halaman</p>
	<!-- /wp:paragraph -->
	<!-- wp:group {"className":"nk-rail-info__date","layout":{"type":"flex","flexWrap":"wrap"}} -->
	<div class="wp-block-group nk-rail-info__date">
		<!-- wp:paragraph -->
		<p>Diperbarui</p>
		<!-- /wp:paragraph -->
		<!-- wp:post-date {"displayType":"modified","format":"j M Y"} /-->
	</div>
	<!-- /wp:group -->
	<!-- wp:paragraph -->
	<p>Ada pertanyaan soal halaman ini? Tim Nostek siap bantu.</p>
	<!-- /wp:paragraph -->
	<!-- wp:list {"className":"nk-rail-info__links"} -->
	<ul class="wp-block-list nk-rail-info__links">
		<!-- wp:list-item -->
		<li><a href="<?php echo esc_url( home_url( '/support/request/' ) ); ?>">Request Support</a></li>
		<!-- /wp:list-item -->
		<!-- wp:list-item -->
		<li><a href="<?php echo esc_url( home_url( '/support/' ) ); ?>">Pusat bantuan</a></li>
		<!-- /wp:list-item -->
		<!-- wp:list-item -->
		<li><a href="<?php echo esc_url( home_url( '/journal/' ) ); ?>">Baca Journal</a></li>
		<!-- /wp:list-item -->
	</ul>
	<!-- /wp:list -->
</div>
<!-- /wp:group -->
<!-- wp:group {"className":"nk-rail-cta","layout":{"type":"default"}} -->
<div class="wp-block-group nk-rail-cta">
	<!-- wp:paragraph {"className":"nk-rail__title"} -->
	<p class="nk-rail__title">Nostek Services</p>
	<!-- /wp:paragraph -->
	<!-- wp:paragraph -->
	<p>Butuh bantuan jaringan, IT managed services, foto atau video untuk bisnis? Tim Nostek siap bantu.</p>
	<!-- /wp:paragraph -->
	<!-- wp:buttons -->
	<div class="wp-block-buttons">
		<!-- wp:button {"className":"is-style-offset"} -->
		<div class="wp-block-button is-style-offset"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/services/' ) ); ?>">Lihat layanan</a></div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
