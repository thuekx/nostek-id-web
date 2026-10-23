<?php
/**
 * Title: Info boxes
 * Slug: nostek/info-boxes
 * Categories: nostek
 * Description: Compact info, success, error and warning notes.
 */
$nostek_notes = array(
	array( 'info', 'Information', 'Artikel ini diperbarui untuk versi terbaru.' ),
	array( 'info-success', 'Success', 'Pesan terkirim, tim kami segera membalas.' ),
	array( 'info-error', 'Error', 'Form belum lengkap: isi email dan nomor WhatsApp.' ),
	array( 'info-warning', 'Warning', 'Langkah ini mengubah konfigurasi server, backup dulu.' ),
);
foreach ( $nostek_notes as $nostek_note ) :
	?>
<!-- wp:group {"className":"is-style-<?php echo esc_attr( $nostek_note[0] ); ?>","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-<?php echo esc_attr( $nostek_note[0] ); ?>">
	<!-- wp:paragraph -->
	<p><?php echo esc_html( $nostek_note[1] ); ?></p>
	<!-- /wp:paragraph -->
	<!-- wp:paragraph -->
	<p><?php echo esc_html( $nostek_note[2] ); ?></p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<?php endforeach; ?>
