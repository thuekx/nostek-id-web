<?php
/**
 * Title: Header tools (search + dark mode)
 * Slug: nostek/header-tools
 * Categories: nostek
 * Inserter: no
 * Description: Search toggle and the light/dark switch shown at the right of the header.
 */
?>
<!-- wp:group {"className":"nk-tools","layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group nk-tools">
	<!-- wp:search {"label":"<?php echo esc_attr_x( 'Cari', 'search label', 'nostek' ); ?>","showLabel":false,"placeholder":"<?php echo esc_attr_x( 'Cari artikel…', 'search placeholder', 'nostek' ); ?>","buttonText":"<?php echo esc_attr_x( 'Cari', 'search button', 'nostek' ); ?>","buttonPosition":"button-only","buttonUseIcon":true,"isSearchFieldHidden":true} /-->
	<!-- wp:html -->
	<button type="button" class="nk-mode" aria-pressed="false" aria-label="<?php echo esc_attr_x( 'Mode gelap', 'dark mode toggle', 'nostek' ); ?>"><svg class="nk-mode__moon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M20.5 14.6A8.5 8.5 0 0 1 9.4 3.5a8.5 8.5 0 1 0 11.1 11.1z"/></svg><svg class="nk-mode__sun" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4.5" fill="currentColor"/><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M12 1.5v2.5M12 20v2.5M1.5 12H4M20 12h2.5M4.6 4.6l1.8 1.8M17.6 17.6l1.8 1.8M4.6 19.4l1.8-1.8M17.6 6.4l1.8-1.8"/></svg></button>
	<!-- /wp:html -->
</div>
<!-- /wp:group -->
