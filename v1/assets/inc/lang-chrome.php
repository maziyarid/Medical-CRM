<?php
/**
 * Professional language dropdown (flags + animation). No translation plugin.
 */
defined( 'ABSPATH' ) || exit;

/**
 * Flag + native label map.
 * Flags use emoji (no image assets required). Works on modern OS/browsers.
 */
function drb_lang_meta(): array {
	return array(
		'fa' => array( 'label' => 'فارسی', 'flag' => '🇮🇷', 'short' => 'FA' ),
		'en' => array( 'label' => 'English', 'flag' => '🇬🇧', 'short' => 'EN' ),
		'ar' => array( 'label' => 'العربية', 'flag' => '🇸🇦', 'short' => 'AR' ),
		'tr' => array( 'label' => 'Türkçe', 'flag' => '🇹🇷', 'short' => 'TR' ),
		'ru' => array( 'label' => 'Русский', 'flag' => '🇷🇺', 'short' => 'RU' ),
		'fr' => array( 'label' => 'Français', 'flag' => '🇫🇷', 'short' => 'FR' ),
		'de' => array( 'label' => 'Deutsch', 'flag' => '🇩🇪', 'short' => 'DE' ),
		'es' => array( 'label' => 'Español', 'flag' => '🇪🇸', 'short' => 'ES' ),
	);
}

/**
 * Professional dropdown markup (replaces plain link list for chrome).
 */
function drb_language_dropdown_html(): string {
	if ( ! function_exists( 'drb_detect_lang' ) || ! function_exists( 'drb_lang_url' ) ) {
		return '';
	}
	$current = drb_detect_lang();
	$meta    = drb_lang_meta();
	$cur     = $meta[ $current ] ?? array( 'label' => strtoupper( $current ), 'flag' => '🌐', 'short' => strtoupper( $current ) );
	$label   = function_exists( '__t' ) ? __t( 'lang_switcher_label' ) : 'Language';

	ob_start();
	?>
	<div class="drb-langdd" data-drb-langdd>
		<button type="button"
			class="drb-langdd__btn"
			aria-haspopup="listbox"
			aria-expanded="false"
			aria-label="<?php echo esc_attr( $label ); ?>">
			<span class="drb-langdd__flag" aria-hidden="true"><?php echo esc_html( $cur['flag'] ); ?></span>
			<span class="drb-langdd__code"><?php echo esc_html( $cur['short'] ); ?></span>
			<span class="drb-langdd__name"><?php echo esc_html( $cur['label'] ); ?></span>
			<span class="drb-langdd__chev" aria-hidden="true">
				<svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M2.5 4.5L6 8l3.5-3.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</span>
		</button>
		<ul class="drb-langdd__menu" role="listbox" hidden>
			<?php foreach ( $meta as $code => $row ) :
				$url    = function_exists( 'drb_lang_url' ) ? drb_lang_url( $code ) : home_url( '/' );
				$active = ( $code === $current );
				?>
				<li role="option" class="drb-langdd__item<?php echo $active ? ' is-active' : ''; ?>" <?php echo $active ? 'aria-selected="true"' : 'aria-selected="false"'; ?>>
					<a href="<?php echo esc_url( $url ); ?>" hreflang="<?php echo esc_attr( $code ); ?>" lang="<?php echo esc_attr( $code ); ?>">
						<span class="drb-langdd__flag" aria-hidden="true"><?php echo esc_html( $row['flag'] ); ?></span>
						<span class="drb-langdd__name"><?php echo esc_html( $row['label'] ); ?></span>
						<?php if ( $active ) : ?><span class="drb-langdd__check" aria-hidden="true">✓</span><?php endif; ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php
	return (string) ob_get_clean();
}

function drb_lang_switcher_assets(): void {
	if ( is_admin() ) {
		return;
	}
	$path = DRB_THEME_DIR . '/assets/css/lang-switcher.css';
	$ver  = file_exists( $path ) ? (string) filemtime( $path ) : ( defined( 'DRB_THEME_VERSION' ) ? DRB_THEME_VERSION : '1' );
	if ( file_exists( $path ) ) {
		wp_enqueue_style( 'drb-lang-switcher', DRB_THEME_URI . '/assets/css/lang-switcher.css', array(), $ver );
	}
	$js = DRB_THEME_DIR . '/assets/js/lang-switcher.js';
	$jsv = file_exists( $js ) ? (string) filemtime( $js ) : $ver;
	if ( file_exists( $js ) ) {
		wp_enqueue_script( 'drb-lang-switcher', DRB_THEME_URI . '/assets/js/lang-switcher.js', array(), $jsv, true );
	}
}
add_action( 'wp_enqueue_scripts', 'drb_lang_switcher_assets', 30 );

function drb_render_lang_bar(): void {
	if ( is_admin() ) {
		return;
	}
	static $printed = false;
	if ( $printed ) {
		return;
	}
	$printed = true;
	$dir = ( function_exists( 'drb_is_rtl' ) && drb_is_rtl() ) ? 'rtl' : 'ltr';
	echo '<div id="drb-lang-bar" class="drb-lang-bar" dir="' . esc_attr( $dir ) . '">';
	echo '<div class="drb-lang-bar__inner">';
	echo drb_language_dropdown_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '</div></div>';
}
add_action( 'wp_body_open', 'drb_render_lang_bar', 5 );
add_action( 'wp_footer', 'drb_render_lang_bar', 1 );
add_action(
	'wp_footer',
	static function () {
		if ( is_admin() ) {
			return;
		}
		echo '<script>(function(){var b=document.getElementById("drb-lang-bar");if(!b||!document.body){return;}document.body.insertBefore(b,document.body.firstChild);})();</script>';
	},
	2
);

function drb_print_i18n_bootstrap(): void {
	if ( is_admin() || ! function_exists( 'drb_detect_lang' ) ) {
		return;
	}
	$lang = drb_detect_lang();
	$pack = function_exists( 'drb_load_pack' ) ? drb_load_pack( $lang ) : array();
	$payload = array(
		'lang'     => $lang,
		'dir'      => ( function_exists( 'drb_is_rtl' ) && drb_is_rtl( $lang ) ) ? 'rtl' : 'ltr',
		'htmlLang' => function_exists( 'drb_html_lang' ) ? drb_html_lang( $lang ) : $lang,
		'strings'  => is_array( $pack ) ? $pack : array(),
	);
	echo '<script>window.__DRB_I18N__=' . wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . ';</script>' . "\n";
}
add_action( 'wp_head', 'drb_print_i18n_bootstrap', 1 );

function drb_force_html_dir_lang_js(): void {
	if ( is_admin() || ! function_exists( 'drb_detect_lang' ) ) {
		return;
	}
	$lang = esc_js( function_exists( 'drb_html_lang' ) ? drb_html_lang() : 'fa-IR' );
	$dir  = esc_js( ( function_exists( 'drb_is_rtl' ) && drb_is_rtl() ) ? 'rtl' : 'ltr' );
	echo '<script>(function(){var h=document.documentElement;if(h){h.setAttribute("lang","' . $lang . '");h.setAttribute("dir","' . $dir . '");}})();</script>' . "\n";
}
add_action( 'wp_head', 'drb_force_html_dir_lang_js', 0 );
