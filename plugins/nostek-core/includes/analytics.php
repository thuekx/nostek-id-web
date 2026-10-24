<?php
/**
 * GA4 (and a Meta Pixel slot) behind a consent banner in Indonesian.
 *
 * Nothing loads unless: the site is live, an ID is set in Settings → Nostek,
 * the visitor is not a logged-in editor, and the visitor pressed "Terima".
 * The choice is kept in the first-party cookie nostek_consent for 180 days;
 * any element with data-nostek-consent-open shows the banner again.
 *
 * @package NostekCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return array{ga4: string, pixel: string}
 */
function nostek_core_analytics_ids() {
	$ga4   = nostek_core_setting( 'ga4_id' );
	$pixel = nostek_core_setting( 'meta_pixel_enabled' ) ? nostek_core_setting( 'meta_pixel_id' ) : '';
	return array(
		'ga4'   => preg_match( '/^G-[A-Z0-9]{4,20}$/', $ga4 ) ? $ga4 : '',
		'pixel' => preg_match( '/^\d{6,20}$/', $pixel ) ? $pixel : '',
	);
}

function nostek_core_analytics_active() {
	if ( ! nostek_core_is_production() || is_admin() || current_user_can( 'edit_posts' ) ) {
		return false;
	}
	$ids = nostek_core_analytics_ids();
	return '' !== $ids['ga4'] || '' !== $ids['pixel'];
}

add_filter( 'body_class', 'nostek_core_consent_body_class' );
/**
 * Lets the theme show its "Pengaturan cookie" link only where it does something.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function nostek_core_consent_body_class( $classes ) {
	if ( nostek_core_analytics_active() ) {
		$classes[] = 'has-nostek-consent';
	}
	return $classes;
}

add_action( 'wp_footer', 'nostek_core_consent_banner', 50 );
function nostek_core_consent_banner() {
	if ( ! nostek_core_analytics_active() ) {
		return;
	}
	$ids     = nostek_core_analytics_ids();
	$privacy = get_privacy_policy_url();
	?>
<div class="nostek-consent" role="dialog" aria-live="polite" aria-label="Persetujuan cookie" hidden>
	<p class="nostek-consent__text">Kami memakai cookie analitik untuk memahami artikel mana yang dibaca dan dari mana pembaca datang. Data ini tidak dijual.<?php if ( $privacy ) : ?> <a href="<?php echo esc_url( $privacy ); ?>">Kebijakan privasi</a><?php endif; ?></p>
	<p class="nostek-consent__actions">
		<button type="button" class="nostek-consent__btn nostek-consent__btn--accept" data-nostek-consent="granted">Terima</button>
		<button type="button" class="nostek-consent__btn" data-nostek-consent="denied">Tolak</button>
	</p>
</div>
<script>
(function () {
	var ids = <?php echo wp_json_encode( $ids ); ?>;
	var box = document.querySelector('.nostek-consent');
	var loaded = false;

	function choice() {
		var m = document.cookie.match(/(?:^|;\s*)nostek_consent=(granted|denied)/);
		return m ? m[1] : '';
	}
	function remember(value) {
		document.cookie = 'nostek_consent=' + value + ';path=/;max-age=15552000;SameSite=Lax' + (location.protocol === 'https:' ? ';Secure' : '');
	}
	// Withdrawn consent: drop the cookies GA4 and the Pixel set earlier.
	function forget() {
		var host = location.hostname.replace(/^www\./, '');
		document.cookie.split(';').forEach(function (c) {
			var name = c.split('=')[0].trim();
			if (/^(_ga|_gid|_gat|_fbp|_fbc)/.test(name)) {
				['', ';domain=' + host, ';domain=.' + host].forEach(function (d) {
					document.cookie = name + '=;path=/;max-age=0' + d;
				});
			}
		});
	}
	function load() {
		if (loaded) { return; }
		loaded = true;
		if (ids.ga4) {
			window.dataLayer = window.dataLayer || [];
			window.gtag = function () { window.dataLayer.push(arguments); };
			gtag('consent', 'default', { analytics_storage: 'granted', ad_storage: 'denied', ad_user_data: 'denied', ad_personalization: 'denied' });
			gtag('js', new Date());
			gtag('config', ids.ga4);
			var s = document.createElement('script');
			s.async = true;
			s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(ids.ga4);
			document.head.appendChild(s);
		}
		if (ids.pixel) {
			!function (f, b, e, v, n, t, s) { if (f.fbq) { return; } n = f.fbq = function () { n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments); }; if (!f._fbq) { f._fbq = n; } n.push = n; n.loaded = !0; n.version = '2.0'; n.queue = []; t = b.createElement(e); t.async = !0; t.src = v; s = b.getElementsByTagName(e)[0]; s.parentNode.insertBefore(t, s); }(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
			fbq('init', ids.pixel);
			fbq('track', 'PageView');
		}
	}

	document.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-nostek-consent]');
		if (btn) {
			remember(btn.getAttribute('data-nostek-consent'));
			box.hidden = true;
			if (choice() === 'granted') { load(); } else { forget(); }
			return;
		}
		if (e.target.closest('[data-nostek-consent-open]')) {
			e.preventDefault();
			box.hidden = false;
		}
	});

	if (choice() === 'granted') { load(); } else if (choice() === '') { box.hidden = false; }
})();
</script>
	<?php
}
