<?php
/**
 * Author identity (WEB-33): job title, social profiles and a Nostek logo
 * avatar for team agents, shown in the author box under each article, on
 * the author page and in the Article schema (E-E-A-T).
 *
 * Fields live on Users > Profile ("Profil penulis Nostek"); no JSON, no
 * code. Agent accounts (role Nostek Agent / Nostek Publisher) use the
 * Nostek logo as avatar and "Nostek Team" as job title until set.
 *
 * @package NostekCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Social profile fields: meta key suffix => label. Website uses core user_url.
 *
 * @return array<string, string>
 */
function nostek_core_author_networks() {
	return array(
		'linkedin'  => 'LinkedIn',
		'instagram' => 'Instagram',
		'x'         => 'X',
		'youtube'   => 'YouTube',
		'facebook'  => 'Facebook',
	);
}

function nostek_core_is_agent_user( $user ) {
	return $user instanceof WP_User && array_intersect( array( 'nostek_agent', 'nostek_publisher' ), (array) $user->roles );
}

function nostek_core_uses_brand_avatar( $user ) {
	if ( ! $user instanceof WP_User ) {
		return false;
	}
	$flag = get_user_meta( $user->ID, 'nostek_brand_avatar', true );
	if ( '' === $flag ) {
		return (bool) nostek_core_is_agent_user( $user );
	}
	return '1' === $flag;
}

function nostek_core_author_job_title( $user ) {
	$title = trim( (string) get_user_meta( $user->ID, 'nostek_job_title', true ) );
	if ( '' === $title && nostek_core_is_agent_user( $user ) ) {
		$title = __( 'Nostek Team', 'nostek-core' );
	}
	return $title;
}

/**
 * Profile links of a user, website first.
 *
 * @param WP_User $user User.
 * @return array<string, string> icon key => url
 */
function nostek_core_author_links( $user ) {
	$links = array();
	if ( $user->user_url ) {
		$links['website'] = $user->user_url;
	}
	foreach ( array_keys( nostek_core_author_networks() ) as $key ) {
		$url = (string) get_user_meta( $user->ID, 'nostek_social_' . $key, true );
		if ( $url ) {
			$links[ $key ] = $url;
		}
	}
	return $links;
}

function nostek_core_brand_avatar_url() {
	$url = nostek_core_setting( 'logo_url' );
	if ( ! $url ) {
		$url = get_site_icon_url( 256 );
	}
	if ( ! $url && file_exists( get_theme_file_path( 'assets/img/brand/apple-touch-icon.png' ) ) ) {
		$url = get_theme_file_uri( 'assets/img/brand/apple-touch-icon.png' );
	}
	return $url;
}

add_filter( 'pre_get_avatar_data', 'nostek_core_brand_avatar', 10, 2 );
/**
 * Nostek logo as avatar for team agents (and anyone who ticks the box).
 *
 * @param array $args        Avatar args.
 * @param mixed $id_or_email User id, WP_User, WP_Post, WP_Comment or email.
 * @return array
 */
function nostek_core_brand_avatar( $args, $id_or_email ) {
	$user = null;
	if ( is_numeric( $id_or_email ) ) {
		$user = get_userdata( (int) $id_or_email );
	} elseif ( $id_or_email instanceof WP_User ) {
		$user = $id_or_email;
	} elseif ( $id_or_email instanceof WP_Post ) {
		$user = get_userdata( (int) $id_or_email->post_author );
	} elseif ( $id_or_email instanceof WP_Comment && $id_or_email->user_id ) {
		$user = get_userdata( (int) $id_or_email->user_id );
	}
	if ( $user && nostek_core_uses_brand_avatar( $user ) ) {
		$url = nostek_core_brand_avatar_url();
		if ( $url ) {
			$args['url']          = $url;
			$args['found_avatar'] = true;
		}
	}
	return $args;
}

add_action( 'show_user_profile', 'nostek_core_author_fields' );
add_action( 'edit_user_profile', 'nostek_core_author_fields' );
function nostek_core_author_fields( $user ) {
	$flag = get_user_meta( $user->ID, 'nostek_brand_avatar', true );
	?>
	<h2><?php esc_html_e( 'Profil penulis Nostek', 'nostek-core' ); ?></h2>
	<p><?php esc_html_e( 'Tampil di kotak "Tentang Penulis" di bawah artikel, di halaman penulis dan di data untuk Google. Isi Biographical Info di atas untuk bio singkat, dan Website untuk situs pribadi.', 'nostek-core' ); ?></p>
	<table class="form-table" role="presentation">
		<tr>
			<th><label for="nostek_job_title"><?php esc_html_e( 'Jabatan', 'nostek-core' ); ?></label></th>
			<td>
				<input type="text" class="regular-text" id="nostek_job_title" name="nostek_job_title" value="<?php echo esc_attr( (string) get_user_meta( $user->ID, 'nostek_job_title', true ) ); ?>" />
				<p class="description"><?php esc_html_e( 'Contoh: Technical Writer Specialist | Nostek Team, atau Founder & Editor-in-Chief, Nostek.', 'nostek-core' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Avatar', 'nostek-core' ); ?></th>
			<td>
				<select name="nostek_brand_avatar" id="nostek_brand_avatar">
					<option value="" <?php selected( $flag, '' ); ?>><?php esc_html_e( 'Otomatis (agen tim = logo Nostek, lainnya = Gravatar)', 'nostek-core' ); ?></option>
					<option value="1" <?php selected( $flag, '1' ); ?>><?php esc_html_e( 'Logo Nostek', 'nostek-core' ); ?></option>
					<option value="0" <?php selected( $flag, '0' ); ?>><?php esc_html_e( 'Foto Gravatar pribadi', 'nostek-core' ); ?></option>
				</select>
			</td>
		</tr>
		<?php foreach ( nostek_core_author_networks() as $key => $label ) : ?>
		<tr>
			<th><label for="nostek_social_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td><input type="url" class="regular-text" id="nostek_social_<?php echo esc_attr( $key ); ?>" name="nostek_social_<?php echo esc_attr( $key ); ?>" placeholder="https://" value="<?php echo esc_attr( (string) get_user_meta( $user->ID, 'nostek_social_' . $key, true ) ); ?>" /></td>
		</tr>
		<?php endforeach; ?>
	</table>
	<?php
}

add_action( 'personal_options_update', 'nostek_core_save_author_fields' );
add_action( 'edit_user_profile_update', 'nostek_core_save_author_fields' );
function nostek_core_save_author_fields( $user_id ) {
	// Core checked the profile form nonce before these hooks run.
	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return;
	}
	// phpcs:disable WordPress.Security.NonceVerification.Missing
	if ( isset( $_POST['nostek_job_title'] ) ) {
		update_user_meta( $user_id, 'nostek_job_title', sanitize_text_field( wp_unslash( $_POST['nostek_job_title'] ) ) );
	}
	if ( isset( $_POST['nostek_brand_avatar'] ) ) {
		$flag = sanitize_key( wp_unslash( $_POST['nostek_brand_avatar'] ) );
		update_user_meta( $user_id, 'nostek_brand_avatar', in_array( $flag, array( '1', '0' ), true ) ? $flag : '' );
	}
	foreach ( array_keys( nostek_core_author_networks() ) as $key ) {
		if ( isset( $_POST[ 'nostek_social_' . $key ] ) ) {
			update_user_meta( $user_id, 'nostek_social_' . $key, esc_url_raw( wp_unslash( $_POST[ 'nostek_social_' . $key ] ) ) );
		}
	}
	// phpcs:enable
}

/**
 * Person schema for an author: job title, profile links, avatar, employer.
 *
 * @param int $user_id User id.
 * @return array
 */
function nostek_core_author_schema( $user_id ) {
	$user   = get_userdata( $user_id );
	$person = array(
		'@type' => 'Person',
		'name'  => $user ? $user->display_name : '',
		'url'   => get_author_posts_url( $user_id ),
	);
	if ( ! $user ) {
		return $person;
	}
	$title = nostek_core_author_job_title( $user );
	if ( $title ) {
		$person['jobTitle'] = $title;
	}
	$links = array_values( nostek_core_author_links( $user ) );
	if ( $links ) {
		$person['sameAs'] = $links;
	}
	$avatar = get_avatar_url( $user_id, array( 'size' => 256 ) );
	if ( $avatar ) {
		$person['image'] = $avatar;
	}
	$person['worksFor'] = array( '@id' => home_url( '/#organization' ) );
	return $person;
}

/**
 * Author box: on a single post = that post's author, on an author page = that author.
 */
function nostek_core_render_author_box() {
	$on_author = is_author();
	if ( is_singular( 'post' ) ) {
		$user = get_userdata( (int) get_queried_object()->post_author );
	} elseif ( $on_author ) {
		$user = get_queried_object();
	} else {
		return '';
	}
	if ( ! $user instanceof WP_User ) {
		return '';
	}

	$name  = esc_html( $user->display_name );
	$url   = esc_url( get_author_posts_url( $user->ID ) );
	$title = nostek_core_author_job_title( $user );
	$bio   = trim( (string) get_the_author_meta( 'description', $user->ID ) );

	$social = '';
	foreach ( nostek_core_author_links( $user ) as $key => $href ) {
		$label   = 'website' === $key ? __( 'Website', 'nostek-core' ) : nostek_core_author_networks()[ $key ];
		$social .= sprintf(
			'<li><a class="nk-author-box__social" href="%1$s" target="_blank" rel="noopener me" aria-label="%2$s" title="%2$s">%3$s</a></li>',
			esc_url( $href ),
			/* translators: 1: author name, 2: network name. */
			esc_attr( sprintf( __( '%1$s di %2$s', 'nostek-core' ), $user->display_name, $label ) ),
			nostek_core_icon( $key )
		);
	}

	$html  = $on_author ? '' : '<p class="nk-rail__title">' . esc_html__( 'Tentang Penulis', 'nostek-core' ) . '</p>';
	$html .= '<div class="nk-author-box__main">';
	$html .= get_avatar( $user->ID, 88, '', $user->display_name, array( 'class' => 'nk-author-box__avatar' ) );
	$html .= '<div class="nk-author-box__text">';
	// On the author page the title band already prints the name as h1.
	$html .= $on_author
		? '<p class="nk-author-box__name">' . $name . '</p>'
		: '<p class="nk-author-box__name"><a href="' . $url . '" rel="author">' . $name . '</a></p>';
	if ( $title ) {
		$html .= '<p class="nk-author-box__role">' . esc_html( $title ) . '</p>';
	}
	if ( $bio ) {
		$html .= '<p class="nk-author-box__bio">' . esc_html( $bio ) . '</p>';
	}
	if ( $social ) {
		$html .= '<ul class="nk-author-box__links">' . $social . '</ul>';
	}
	if ( ! $on_author ) {
		/* translators: %s: author name. */
		$html .= '<a class="nk-author-box__more" href="' . $url . '">' . esc_html( sprintf( __( 'Semua tulisan %s', 'nostek-core' ), $user->display_name ) ) . ' &rarr;</a>';
	}
	$html .= '</div></div>';

	$wrapper = get_block_wrapper_attributes( array( 'class' => 'nk-author-box' ) );
	return sprintf( '<section %s>%s</section>', $wrapper, $html );
}

add_action( 'init', 'nostek_core_register_author_block' );
function nostek_core_register_author_block() {
	register_block_type(
		'nostek/author-box',
		array(
			'api_version'     => 3,
			'title'           => __( 'Nostek Author Box', 'nostek-core' ),
			'category'        => 'theme',
			'editor_script'   => 'nostek-core-blocks',
			'render_callback' => 'nostek_core_render_author_box',
			'supports'        => array( 'html' => false ),
		)
	);
}
