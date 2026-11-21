<?php
/**
 * GA4 (and a Meta Pixel slot) behind a consent banner in Indonesian (WEB-43).
 *
 * Categories: Wajib (always on, no choice), Analitik (GA4) and Pemasaran
 * (Meta Pixel); a category is offered only when its tool is set up in
 * Settings → Nostek. Nothing from Google or Meta loads unless: the site is
 * live, an ID is set, the visitor is not a logged-in editor, and the visitor
 * allowed that category. Buttons: Terima semua / Tolak / Atur (per-category
 * switches, all off until the visitor turns one on).
 *
 * Google Consent Mode v2 (basic): every consent type starts "denied"; gtag.js
 * loads only after Analitik is allowed, then gets a consent update. Ad types
 * follow Pemasaran.
 *
 * The choice is kept in the first-party cookie nostek_consent for 180 days:
 * "2.a<0|1|x>.m<0|1|x>", x = category not offered when the visitor chose, so
 * the banner asks again once it is (old values: granted = Analitik only,
 * denied = none).
 * Any element with data-nostek-consent-open shows the banner again; other
 * scripts read window.nostekConsent.get() or listen for "nostek:consent".
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

/**
 * Categories the banner offers on this site, besides Wajib.
 *
 * @return array<string, array{label: string, text: string}>
 */
function nostek_core_consent_categories() {
	$ids  = nostek_core_analytics_ids();
	$cats = array();
	if ( '' !== $ids['ga4'] ) {
		$cats['a'] = array(
			'label' => 'Analitik',
			'text'  => 'Google Analytics menghitung kunjungan: halaman yang dibaca, lama membaca, dan dari mana pembaca datang. Kami memakainya untuk menentukan topik yang perlu ditulis.',
		);
	}
	if ( '' !== $ids['pixel'] ) {
		$cats['m'] = array(
			'label' => 'Pemasaran',
			'text'  => 'Meta Pixel mengukur hasil iklan kami di Facebook dan Instagram, dan dapat menampilkan iklan Nostek kepada Anda di sana.',
		);
	}
	return $cats;
}

/**
 * Cookie Policy page (Legal), else the Privacy Policy page, else ''.
 */
function nostek_core_cookie_policy_url() {
	$page = get_page_by_path( 'legal/cookie-policy' );
	if ( $page && 'publish' === $page->post_status ) {
		return (string) get_permalink( $page );
	}
	return (string) get_privacy_policy_url();
}

add_action( 'wp_footer', 'nostek_core_consent_banner', 50 );
function nostek_core_consent_banner() {
	if ( ! nostek_core_analytics_active() ) {
		return;
	}
	$ids    = nostek_core_analytics_ids();
	$cats   = nostek_core_consent_categories();
	$policy = nostek_core_cookie_policy_url();
	$uses   = isset( $cats['m'] ) ? 'cookie analitik dan pemasaran untuk memahami artikel mana yang dibaca dan mengukur iklan kami' : 'cookie analitik untuk memahami artikel mana yang dibaca dan dari mana pembaca datang';
	?>
<div class="nostek-consent" role="dialog" aria-labelledby="nostek-consent-title" hidden>
	<p class="nostek-consent__label" id="nostek-consent-title">Cookie</p>
	<p class="nostek-consent__text">Kami memakai cookie wajib agar situs berfungsi. Dengan izin Anda, kami juga memakai <?php echo esc_html( $uses ); ?>. Data ini tidak dijual.<?php if ( $policy ) : ?> <a href="<?php echo esc_url( $policy ); ?>">Kebijakan cookie</a><?php endif; ?></p>
	<div class="nostek-consent__prefs" id="nostek-consent-prefs" hidden>
		<div class="nostek-consent__cat">
			<input type="checkbox" id="nostek-consent-e" checked disabled>
			<label for="nostek-consent-e"><strong>Wajib</strong> <span class="nostek-consent__always">Selalu aktif</span></label>
			<p>Masuk akun, keranjang belanja, mode gelap, dan pilihan cookie ini. Tanpa cookie ini situs tidak bisa berjalan.</p>
		</div>
		<?php foreach ( $cats as $key => $cat ) : ?>
		<div class="nostek-consent__cat">
			<input type="checkbox" id="nostek-consent-<?php echo esc_attr( $key ); ?>" data-nostek-consent-cat="<?php echo esc_attr( $key ); ?>">
			<label for="nostek-consent-<?php echo esc_attr( $key ); ?>"><strong><?php echo esc_html( $cat['label'] ); ?></strong></label>
			<p><?php echo esc_html( $cat['text'] ); ?></p>
		</div>
		<?php endforeach; ?>
	</div>
	<p class="nostek-consent__actions">
		<button type="button" class="nostek-consent__btn nostek-consent__btn--accept" data-nostek-consent="all">Terima semua</button>
		<button type="button" class="nostek-consent__btn" data-nostek-consent="none">Tolak</button>
		<button type="button" class="nostek-consent__btn nostek-consent__btn--link" data-nostek-consent="prefs" aria-controls="nostek-consent-prefs" aria-expanded="false">Atur</button>
		<button type="button" class="nostek-consent__btn nostek-consent__btn--accept" data-nostek-consent="save" hidden>Simpan pilihan</button>
	</p>
</div>
<script>
(function () {
	var ids = <?php echo wp_json_encode( $ids ); ?>;
	var box = document.querySelector('.nostek-consent');
	var prefs = box.querySelector('.nostek-consent__prefs');
	var loaded = { a: false, m: false };

	// Consent Mode v2 defaults: everything denied until the visitor chooses.
	window.dataLayer = window.dataLayer || [];
	window.gtag = window.gtag || function () { window.dataLayer.push(arguments); };
	gtag('consent', 'default', { analytics_storage: 'denied', ad_storage: 'denied', ad_user_data: 'denied', ad_personalization: 'denied', functionality_storage: 'granted', security_storage: 'granted', wait_for_update: 500 });

	function read() {
		var m = document.cookie.match(/(?:^|;\s*)nostek_consent=([^;]+)/);
		var v = m ? decodeURIComponent(m[1]) : '';
		var n = v.match(/^2\.a([01x])\.m([01x])$/);
		var c = n ? { a: n[1] === '1', m: n[2] === '1', ask: (ids.ga4 && n[1] === 'x') || (ids.pixel && n[2] === 'x') }
			: v === 'granted' ? { a: true, m: false, ask: !!ids.pixel }
			: v === 'denied' ? { a: false, m: false, ask: !!ids.pixel } : null;
		return c;
	}
	function write(c) {
		// x = category not offered on this site when the visitor chose; asked again once it is.
		document.cookie = 'nostek_consent=2.a' + (ids.ga4 ? (c.a ? 1 : 0) : 'x') + '.m' + (ids.pixel ? (c.m ? 1 : 0) : 'x') + ';path=/;max-age=15552000;SameSite=Lax' + (location.protocol === 'https:' ? ';Secure' : '');
	}
	// Withdrawn consent: drop the cookies GA4 (a) or the Pixel (m) set earlier.
	function forget(re) {
		var host = location.hostname.replace(/^www\./, '');
		document.cookie.split(';').forEach(function (c) {
			var name = c.split('=')[0].trim();
			if (re.test(name)) {
				['', ';domain=' + host, ';domain=.' + host].forEach(function (d) {
					document.cookie = name + '=;path=/;max-age=0' + d;
				});
			}
		});
	}
	function apply(c) {
		var ad = c.m ? 'granted' : 'denied';
		gtag('consent', 'update', { analytics_storage: c.a ? 'granted' : 'denied', ad_storage: ad, ad_user_data: ad, ad_personalization: ad });
		if (ids.ga4 && c.a && !loaded.a) {
			loaded.a = true;
			gtag('js', new Date());
			gtag('config', ids.ga4);
			var s = document.createElement('script');
			s.async = true;
			s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(ids.ga4);
			document.head.appendChild(s);
		}
		if (!c.a) { forget(/^(_ga|_gid|_gat)/); }
		if (ids.pixel && c.m && !loaded.m) {
			loaded.m = true;
			!function (f, b, e, v, n, t, s) { if (f.fbq) { return; } n = f.fbq = function () { n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments); }; if (!f._fbq) { f._fbq = n; } n.push = n; n.loaded = !0; n.version = '2.0'; n.queue = []; t = b.createElement(e); t.async = !0; t.src = v; s = b.getElementsByTagName(e)[0]; s.parentNode.insertBefore(t, s); }(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
			fbq('init', ids.pixel);
			fbq('track', 'PageView');
		}
		if (!c.m) {
			if (window.fbq) { fbq('consent', 'revoke'); }
			forget(/^(_fbp|_fbc)/);
		}
		status(c);
		document.dispatchEvent(new CustomEvent('nostek:consent', { detail: c }));
	}
	function status(c) {
		var t = c ? 'Pilihan Anda saat ini: Analitik ' + (c.a ? 'aktif' : 'nonaktif') + (ids.pixel ? ', Pemasaran ' + (c.m ? 'aktif' : 'nonaktif') : '') + '.' : 'Anda belum memilih. Cookie analitik dan pemasaran tetap mati sampai Anda mengizinkannya.';
		document.querySelectorAll('[data-nostek-consent-status]').forEach(function (el) { el.textContent = t; });
	}
	function choose(c) {
		write(c);
		box.hidden = true;
		apply(c);
	}
	function open(withPrefs, focus) {
		var c = read() || { a: false, m: false };
		box.querySelectorAll('[data-nostek-consent-cat]').forEach(function (cb) { cb.checked = !!c[cb.getAttribute('data-nostek-consent-cat')]; });
		showPrefs(withPrefs);
		box.hidden = false;
		if (focus) { box.querySelector(withPrefs ? '[data-nostek-consent-cat]' : '.nostek-consent__btn').focus(); }
	}
	function showPrefs(on) {
		prefs.hidden = !on;
		box.classList.toggle('is-prefs', on);
		box.querySelector('[data-nostek-consent="prefs"]').hidden = on;
		box.querySelector('[data-nostek-consent="prefs"]').setAttribute('aria-expanded', on ? 'true' : 'false');
		box.querySelector('[data-nostek-consent="save"]').hidden = !on;
	}

	document.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-nostek-consent]');
		if (btn && box.contains(btn)) {
			var act = btn.getAttribute('data-nostek-consent');
			if (act === 'prefs') { showPrefs(true); prefs.querySelector('[data-nostek-consent-cat]') && prefs.querySelector('[data-nostek-consent-cat]').focus(); return; }
			if (act === 'all') { choose({ a: !!ids.ga4, m: !!ids.pixel }); return; }
			if (act === 'none') { choose({ a: false, m: false }); return; }
			var c = { a: false, m: false };
			box.querySelectorAll('[data-nostek-consent-cat]').forEach(function (cb) { c[cb.getAttribute('data-nostek-consent-cat')] = cb.checked; });
			choose(c);
			return;
		}
		if (e.target.closest('[data-nostek-consent-open]')) {
			e.preventDefault();
			open(read() !== null, true);
		}
	});

	window.nostekConsent = { get: read, open: open };
	var start = read();
	if (start) { apply(start); }
	if (!start) { status(null); open(false, false); } else if (start.ask) { open(true, false); }
})();
</script>
	<?php
}

add_action( 'init', 'nostek_core_register_cookie_settings_block' );
/**
 * nostek/cookie-settings: the visitor's current choice and a button that
 * opens the banner again (for the Cookie Policy page).
 */
function nostek_core_register_cookie_settings_block() {
	register_block_type(
		'nostek/cookie-settings',
		array(
			'api_version'     => 3,
			'title'           => __( 'Nostek Cookie Settings', 'nostek-core' ),
			'category'        => 'theme',
			'editor_script'   => 'nostek-core-blocks',
			'render_callback' => 'nostek_core_render_cookie_settings',
			'supports'        => array( 'html' => false ),
		)
	);
}

function nostek_core_render_cookie_settings() {
	if ( ! nostek_core_analytics_active() ) {
		$text = nostek_core_is_production() && current_user_can( 'edit_posts' )
			? 'Pengunjung melihat tombol pengaturan cookie di sini. Anda masuk sebagai editor, jadi analitik tidak berjalan untuk Anda.'
			: 'Saat ini situs ini hanya memakai cookie wajib, jadi tidak ada yang perlu Anda atur.';
		return '<div class="nostek-cookie-settings"><p>' . esc_html( $text ) . '</p></div>';
	}
	return '<div class="nostek-cookie-settings"><p data-nostek-consent-status></p>'
		. '<p><a class="nostek-cookie-settings__btn" href="#cookie" data-nostek-consent-open>Ubah pilihan cookie</a></p></div>';
}
