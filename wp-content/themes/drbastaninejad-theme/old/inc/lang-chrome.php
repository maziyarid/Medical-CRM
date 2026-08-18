<?php
/**
 * Language chrome for the Dr. Bastaninejad theme.
 *
 * Provides the professional language dropdown markup, its CSS/JS assets, the
 * i18n bootstrap global (window.__DRB_I18N__) and the html lang/dir shim.
 *
 * The dropdown itself is rendered inside the primary navigation by header.php
 * (drb_language_dropdown_html). This file deliberately does NOT auto-hook a
 * second top language bar — doing so would produce a duplicate switcher.
 *
 * Templates that want a standalone bar may call drb_render_lang_bar() directly.
 *
 * @package DrBastaninejad_Theme
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'drb_lang_meta' ) ) {
	/**
	 * Flag, native label and short code for each supported language.
	 * Flags are emoji so no image assets are required.
	 *
	 * @return array<string, array{label:string,flag:string,short:string}>
	 */
	function drb_lang_meta(): array {
		return array(
			'fa' => array( 'label' => 'فارسی',   'flag' => '🇮🇷', 'short' => 'FA' ),
			'en' => array( 'label' => 'English', 'flag' => '🇬🇧', 'short' => 'EN' ),
			'ar' => array( 'label' => 'العربية',  'flag' => '🇸🇦', 'short' => 'AR' ),
			'tr' => array( 'label' => 'Türkçe',   'flag' => '🇹🇷', 'short' => 'TR' ),
			'ru' => array( 'label' => 'Русский', 'flag' => '🇷🇺', 'short' => 'RU' ),
			'fr' => array( 'label' => 'Français','flag' => '🇫🇷', 'short' => 'FR' ),
			'de' => array( 'label' => 'Deutsch', 'flag' => '🇩🇪', 'short' => 'DE' ),
			'es' => array( 'label' => 'Español', 'flag' => '🇪🇸', 'short' => 'ES' ),
		);
	}
}

if ( ! function_exists( 'drb_language_dropdown_html' ) ) {
	/**
	 * Render the accessible language dropdown.
	 * Depends on inc/i18n.php (drb_detect_lang, drb_lang_url) which is loaded
	 * before this file in functions.php.
	 *
	 * @return string HTML markup, escaped. Empty string when i18n helpers absent.
	 */
	function drb_language_dropdown_html(): string {
		if ( ! function_exists( 'drb_detect_lang' ) || ! function_exists( 'drb_lang_url' ) ) {
			return '';
		}

		$current = drb_detect_lang();
		$meta    = drb_lang_meta();
		$cur     = $meta[ $current ] ?? array(
			'label' => strtoupper( $current ),
			'flag'  => '🌐',
			'short' => strtoupper( $current ),
		);
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
			<ul class="drb-langdd__menu" role="listbox" aria-hidden="true">
				<?php foreach ( $meta as $code => $row ) : ?>
					<?php
					$url    = drb_lang_url( $code );
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
}

if ( ! function_exists( 'drb_lang_switcher_assets' ) ) {
	/**
	 * Enqueue the dropdown stylesheet and behaviour script when present.
	 */
	function drb_lang_switcher_assets(): void {
		if ( is_admin() ) {
			return;
		}
		$path = DRB_THEME_DIR . '/assets/css/lang-switcher.css';
		$ver  = file_exists( $path ) ? (string) filemtime( $path ) : ( defined( 'DRB_THEME_VERSION' ) ? DRB_THEME_VERSION : '1' );
		if ( file_exists( $path ) ) {
			wp_enqueue_style( 'drb-lang-switcher', DRB_THEME_URI . '/assets/css/lang-switcher.css', array(), $ver );
		}
		$layout = DRB_THEME_DIR . '/assets/css/localization-layout.css';
		if ( file_exists( $layout ) ) {
			wp_enqueue_style( 'drb-localization-layout', DRB_THEME_URI . '/assets/css/localization-layout.css', array( 'drb-theme' ), (string) filemtime( $layout ) );
		}
		$js   = DRB_THEME_DIR . '/assets/js/lang-switcher.js';
		$jsv  = file_exists( $js ) ? (string) filemtime( $js ) : $ver;
		if ( file_exists( $js ) ) {
			wp_enqueue_script( 'drb-lang-switcher', DRB_THEME_URI . '/assets/js/lang-switcher.js', array(), $jsv, true );
		}
		if ( function_exists( 'drb_detect_lang' ) && 'fa' !== drb_detect_lang() ) {
			$booking_intl = DRB_THEME_DIR . '/assets/js/booking-international.js';
			if ( is_readable( $booking_intl ) ) {
				wp_enqueue_script( 'drb-booking-international', DRB_THEME_URI . '/assets/js/booking-international.js', array( 'drb-i18n-runtime' ), (string) filemtime( $booking_intl ), true );
			}
		}
	}
}
add_action( 'wp_enqueue_scripts', 'drb_lang_switcher_assets', 30 );

if ( ! function_exists( 'drb_render_lang_bar' ) ) {
	/**
	 * Optional standalone language bar template tag.
	 * Not auto-hooked: header.php already embeds the dropdown in the primary
	 * navigation, so an auto-rendered bar would duplicate it. Call this directly
	 * from a custom template only when a standalone bar is desired.
	 */
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
}

if ( ! function_exists( 'drb_print_i18n_bootstrap' ) ) {
	/**
	 * Emit window.__DRB_I18N__ with the current language, direction, html lang
	 * and translation pack, for the React app and inline scripts.
	 */
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
			'phrases'  => function_exists( 'drb_load_react_phrase_pack' ) ? drb_load_react_phrase_pack( $lang ) : array(),
		);
		echo '<script>window.__DRB_I18N__=' . wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . ';</script>' . "\n";
	}
}
add_action( 'wp_head', 'drb_print_i18n_bootstrap', 1 );

if ( ! function_exists( 'drb_force_html_dir_lang_js' ) ) {
	/**
	 * Ensure the html element always carries the correct lang and dir attributes
	 * even on native-template pages that do not render the React bootstrap.
	 */
	function drb_force_html_dir_lang_js(): void {
		if ( is_admin() || ! function_exists( 'drb_detect_lang' ) ) {
			return;
		}
		$lang = esc_js( function_exists( 'drb_html_lang' ) ? drb_html_lang() : 'fa-IR' );
		$dir  = esc_js( ( function_exists( 'drb_is_rtl' ) && drb_is_rtl() ) ? 'rtl' : 'ltr' );
		echo '<script>(function(){var h=document.documentElement;if(h){h.setAttribute("lang","' . $lang . '");h.setAttribute("dir","' . $dir . '");}})();</script>' . "\n";
	}
}
add_action( 'wp_head', 'drb_force_html_dir_lang_js', 0 );
