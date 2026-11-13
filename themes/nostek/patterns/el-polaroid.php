<?php
/**
 * Title: Gambar polaroid
 * Slug: nostek/el-polaroid
 * Categories: nostek-elements
 * Keywords: gambar, foto, polaroid, image
 * Description: Foto berbingkai polaroid dengan selotip dan keterangan tulisan tangan. Klik "Replace" untuk mengganti foto.
 */
$nostek_img = get_theme_file_uri( 'assets/img/brand/' );
?>
<!-- wp:image {"sizeSlug":"full","className":"is-style-polaroid"} -->
<figure class="wp-block-image size-full is-style-polaroid"><img src="<?php echo esc_url( $nostek_img . 'hero-wide.webp' ); ?>" alt=""/><figcaption class="wp-element-caption">Ruang server, 1987 vibes</figcaption></figure>
<!-- /wp:image -->
