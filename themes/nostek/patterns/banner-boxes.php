<?php
/**
 * Title: Banner boxes (3)
 * Slug: nostek/banner-boxes
 * Categories: nostek
 * Description: Three double-framed boxes with a colored link strip.
 */
$nostek_boxes = array(
	array( 'Baca Journal Terbaru', 'News &amp; Insights', '/journal/', 'teal' ),
	array( 'Butuh Solusi IT?', 'Nostek Services', '/services/', 'red' ),
	array( 'Gadget Pilihan', 'Lihat Market', '/market/', 'teal' ),
);
?>
<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|40"}}}} -->
<div class="wp-block-columns alignwide">
<?php foreach ( $nostek_boxes as $nostek_box ) : ?>
	<!-- wp:column -->
	<div class="wp-block-column">
		<!-- wp:group {"className":"is-style-frame","style":{"dimensions":{"minHeight":"100%"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch","verticalAlignment":"space-between"}} -->
		<div class="wp-block-group is-style-frame" style="min-height:100%">
			<!-- wp:heading {"textAlign":"center","level":3,"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}}},"fontSize":"xx-large"} -->
			<h3 class="wp-block-heading has-text-align-center has-xx-large-font-size" style="padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)"><?php echo esc_html( $nostek_box[0] ); ?></h3>
			<!-- /wp:heading -->
			<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"stretch"}} -->
			<div class="wp-block-buttons">
				<!-- wp:button {"backgroundColor":"<?php echo esc_attr( $nostek_box[3] ); ?>","width":100} -->
				<div class="wp-block-button has-custom-width wp-block-button__width-100"><a class="wp-block-button__link has-<?php echo esc_attr( $nostek_box[3] ); ?>-background-color has-background wp-element-button" href="<?php echo esc_url( $nostek_box[2] ); ?>"><?php echo wp_kses_post( $nostek_box[1] ); ?></a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:column -->
<?php endforeach; ?>
</div>
<!-- /wp:columns -->
