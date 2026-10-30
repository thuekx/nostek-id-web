<?php
/**
 * Title: Brand lockup (N mark + NOSTEK ID)
 * Slug: nostek/brand
 * Categories: nostek
 * Inserter: no
 * Description: Retro horizontal logo used in the header and footer. The wordmark is live text so it follows dark mode.
 */
?>
<!-- wp:html -->
<p class="nk-brand"><a class="nk-brand__link" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><img class="nk-brand__mark" src="<?php echo esc_url( get_theme_file_uri( 'assets/img/brand/nostek-mark.svg' ) ); ?>" alt="" width="44" height="48"><span class="nk-brand__word">Nostek <span>ID</span></span></a></p>
<!-- /wp:html -->
