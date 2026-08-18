<?php
/**
 * Dr. Bastaninejad theme bootstrap.
 * Every include is guarded so a partial upload or a missing file
 * can never produce a fatal / white screen.
 */
defined( 'ABSPATH' ) || exit;

if ( ! defined( 'DRB_THEME_VERSION' ) ) {
	define( 'DRB_THEME_VERSION', '4.6.0' );
}
if ( ! defined( 'DRB_THEME_DIR' ) ) {
	define( 'DRB_THEME_DIR', get_template_directory() );
}
if ( ! defined( 'DRB_THEME_URI' ) ) {
	define( 'DRB_THEME_URI', get_template_directory_uri() );
}

/**
 * Safely include a theme file only if it exists and is readable.
 *
 * @param string $relative      Path relative to the theme root.
 * @param bool   $log_if_missing Whether to write a notice to the error log.
 * @return bool                 True if the file was loaded.
 */
function drb_require_inc( $relative, $log_if_missing = false ) {
	$path = DRB_THEME_DIR . '/' . ltrim( $relative, '/' );

	if ( is_readable( $path ) ) {
		require_once $path;
		return true;
	}

	if ( $log_if_missing && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( sprintf( '[DRB THEME] Optional include missing (skipped): %s', $relative ) );
	}

	return false;
}

/**
 * Core theme modules.
 * Order matters for a few of these, so keep setup/content-types first.
 * A missing file is skipped quietly instead of fataling.
 */
$drb_modules = array(
	'inc/setup.php',
	'inc/content-types.php',
	'inc/meta-boxes.php',
	'inc/options.php',
	'inc/content-policy.php',
	'inc/source-importer.php',
	'inc/rank-math.php',
	'inc/forms.php',
	'inc/booking-admin.php',
	'inc/seo.php',
	'inc/tracking.php',
	'inc/admin-dashboard.php',
	'inc/case-management.php',
	'inc/react-app.php',
	'inc/i18n.php',
	'inc/localized-content.php',
	'inc/lang-chrome.php', // language bar — safe if missing
);

foreach ( $drb_modules as $drb_module ) {
	drb_require_inc( $drb_module, true );
}
unset( $drb_module );
