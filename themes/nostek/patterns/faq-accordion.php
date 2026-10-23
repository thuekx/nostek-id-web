<?php
/**
 * Title: FAQ accordion
 * Slug: nostek/faq-accordion
 * Categories: nostek
 */
$nostek_faq = array(
	array( 'Apa itu Nostek Certified?', 'Label untuk produk Market yang sudah melalui pengecekan fisik, fungsi, dan grading oleh tim Nostek.' ),
	array( 'Bagaimana cara Sell to Nostek?', 'Isi form singkat, kirim foto produk, lalu tim kami memberi penawaran harga.' ),
	array( 'Apakah bisa konsultasi Services dulu?', 'Bisa. Hubungi kami lewat halaman Services untuk menjadwalkan konsultasi.' ),
);
foreach ( $nostek_faq as $nostek_item ) :
	?>
<!-- wp:details {"className":"is-style-accordion"} -->
<details class="wp-block-details is-style-accordion"><summary><?php echo esc_html( $nostek_item[0] ); ?></summary>
	<!-- wp:paragraph -->
	<p><?php echo esc_html( $nostek_item[1] ); ?></p>
	<!-- /wp:paragraph -->
</details>
<!-- /wp:details -->
<?php endforeach; ?>
