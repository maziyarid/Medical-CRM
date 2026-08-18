<?php
/**
 * Deprecated compatibility shim.
 *
 * Language chrome is maintained only in /inc/lang-chrome.php. Keeping this
 * path as a shim prevents legacy direct includes from loading a stale second
 * implementation or redeclaring the same functions.
 */
defined( 'ABSPATH' ) || exit;

$drb_canonical_lang_chrome = dirname( __DIR__, 2 ) . '/inc/lang-chrome.php';
if ( ! function_exists( 'drb_language_dropdown_html' ) && is_readable( $drb_canonical_lang_chrome ) ) {
	require_once $drb_canonical_lang_chrome;
}
unset( $drb_canonical_lang_chrome );
