<?php
/**
 * Settings → Nostek: site values that must not live in git (GA4 Measurement
 * ID, Meta Pixel ID, brand links). Admins only. A constant in wp-config.php
 * wins over the stored value: NOSTEK_GA4_ID, NOSTEK_META_PIXEL_ID.
 *
 * @package NostekCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const NOSTEK_CORE_SETTINGS = 'nostek_core_settings';

/**
 * @param string $key ga4_id | meta_pixel_id | meta_pixel_enabled | org_name | logo_url | same_as.
 * @return string
 */
function nostek_core_setting( $key ) {
	$constants = array(
		'ga4_id'        => 'NOSTEK_GA4_ID',
		'meta_pixel_id' => 'NOSTEK_META_PIXEL_ID',
	);
	if ( isset( $constants[ $key ] ) && defined( $constants[ $key ] ) ) {
		return (string) constant( $constants[ $key ] );
	}
	$all = get_option( NOSTEK_CORE_SETTINGS, array() );
	return is_array( $all ) && isset( $all[ $key ] ) ? (string) $all[ $key ] : '';
}

add_action( 'admin_init', 'nostek_core_register_settings' );
function nostek_core_register_settings() {
	register_setting(
		'nostek_core',
		NOSTEK_CORE_SETTINGS,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'nostek_core_sanitize_settings',
			'default'           => array(),
			'show_in_rest'      => false,
		)
	);
}

/**
 * Invalid IDs are dropped with a notice rather than stored.
 *
 * @param mixed $input Raw form values.
 * @return array
 */
function nostek_core_sanitize_settings( $input ) {
	$input = is_array( $input ) ? $input : array();
	$out   = array(
		'ga4_id'             => strtoupper( trim( (string) ( $input['ga4_id'] ?? '' ) ) ),
		'meta_pixel_id'      => trim( (string) ( $input['meta_pixel_id'] ?? '' ) ),
		'meta_pixel_enabled' => empty( $input['meta_pixel_enabled'] ) ? '' : '1',
		'org_name'           => sanitize_text_field( (string) ( $input['org_name'] ?? '' ) ),
		'logo_url'           => esc_url_raw( trim( (string) ( $input['logo_url'] ?? '' ) ) ),
		'same_as'            => implode( "\n", array_filter( array_map( 'esc_url_raw', preg_split( '/\s+/', (string) ( $input['same_as'] ?? '' ) ) ) ) ),
	);
	if ( '' !== $out['ga4_id'] && ! preg_match( '/^G-[A-Z0-9]{4,20}$/', $out['ga4_id'] ) ) {
		nostek_core_settings_error( 'ga4', __( 'GA4 Measurement ID must look like G-XXXXXXXXXX. Not saved.', 'nostek-core' ) );
		$out['ga4_id'] = '';
	}
	if ( '' !== $out['meta_pixel_id'] && ! preg_match( '/^\d{6,20}$/', $out['meta_pixel_id'] ) ) {
		nostek_core_settings_error( 'pixel', __( 'Meta Pixel ID must be digits only. Not saved.', 'nostek-core' ) );
		$out['meta_pixel_id'] = '';
	}
	return $out;
}

/**
 * @param string $code    Error code.
 * @param string $message Message shown in wp-admin.
 */
function nostek_core_settings_error( $code, $message ) {
	if ( function_exists( 'add_settings_error' ) ) {
		add_settings_error( NOSTEK_CORE_SETTINGS, $code, $message );
	}
}

add_action( 'admin_menu', 'nostek_core_settings_menu' );
function nostek_core_settings_menu() {
	add_options_page( 'Nostek', 'Nostek', 'manage_options', 'nostek-core', 'nostek_core_settings_page' );
}

function nostek_core_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$name   = NOSTEK_CORE_SETTINGS;
	$value  = static function ( $key ) {
		return nostek_core_setting( $key );
	};
	$locked = static function ( $constant ) {
		return defined( $constant ) ? ' readonly title="' . esc_attr( "Set by $constant in wp-config.php" ) . '"' : '';
	};
	$live   = nostek_core_is_production();
	?>
	<div class="wrap">
		<h1>Nostek</h1>
		<p>
			<?php
			echo $live
				? esc_html__( 'This is the live site: analytics load once a visitor accepts the cookie banner.', 'nostek-core' )
				: esc_html__( 'This is not the live site: analytics never load here and every page is noindex.', 'nostek-core' );
			?>
		</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'nostek_core' ); ?>
			<h2><?php esc_html_e( 'Analytics', 'nostek-core' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="nostek-ga4">GA4 Measurement ID</label></th>
					<td><input id="nostek-ga4" class="regular-text code" name="<?php echo esc_attr( $name ); ?>[ga4_id]" value="<?php echo esc_attr( $value( 'ga4_id' ) ); ?>" placeholder="G-XXXXXXXXXX"<?php echo $locked( 'NOSTEK_GA4_ID' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
					<p class="description"><?php esc_html_e( 'Empty = no Google Analytics and no cookie banner.', 'nostek-core' ); ?></p></td>
				</tr>
				<tr>
					<th scope="row"><label for="nostek-pixel">Meta Pixel ID</label></th>
					<td><input id="nostek-pixel" class="regular-text code" name="<?php echo esc_attr( $name ); ?>[meta_pixel_id]" value="<?php echo esc_attr( $value( 'meta_pixel_id' ) ); ?>"<?php echo $locked( 'NOSTEK_META_PIXEL_ID' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
					<p><label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[meta_pixel_enabled]" value="1" <?php checked( '1', $value( 'meta_pixel_enabled' ) ); ?>> <?php esc_html_e( 'Load the Meta Pixel (only after the owner approved it)', 'nostek-core' ); ?></label></p></td>
				</tr>
			</table>
			<h2><?php esc_html_e( 'Brand (search results and link previews)', 'nostek-core' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="nostek-org">Organization name</label></th>
					<td><input id="nostek-org" class="regular-text" name="<?php echo esc_attr( $name ); ?>[org_name]" value="<?php echo esc_attr( $value( 'org_name' ) ); ?>" placeholder="Nostek"></td>
				</tr>
				<tr>
					<th scope="row"><label for="nostek-logo">Logo URL</label></th>
					<td><input id="nostek-logo" class="regular-text code" name="<?php echo esc_attr( $name ); ?>[logo_url]" value="<?php echo esc_attr( $value( 'logo_url' ) ); ?>">
					<p class="description"><?php esc_html_e( 'Square PNG/WebP from the Media Library, at least 512 px. Empty = the Site Icon.', 'nostek-core' ); ?></p></td>
				</tr>
				<tr>
					<th scope="row"><label for="nostek-sameas">Social profiles</label></th>
					<td><textarea id="nostek-sameas" class="large-text code" rows="4" name="<?php echo esc_attr( $name ); ?>[same_as]"><?php echo esc_textarea( $value( 'same_as' ) ); ?></textarea>
					<p class="description"><?php esc_html_e( 'One URL per line, e.g. https://www.instagram.com/nostek.id/', 'nostek-core' ); ?></p></td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
