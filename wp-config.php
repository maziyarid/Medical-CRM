<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'drbastaninejad_md' );

/** Database username */
define( 'DB_USER', 'drbastaninejad_md' );

/** Database password */
define( 'DB_PASSWORD', 'UK0R^)zg,2eB2s(w' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'T`M!Hw+b]01h1mQbYmS;Kk-0}vuV)OA.PB_lFk^u`nmXPWSaWp~|_t.|Fl?c nB.');
define('SECURE_AUTH_KEY',  ' rz/BTs6&fPX1]cQ83n@s+jC(Rr([0|lYj,;sB9= pPEi:K/w #v<AX-y1 rV5M@');
define('LOGGED_IN_KEY',    'P-PYc[K,C{Z}y2j0PtXEyXC/BZg|<>s}~)Z>9Y9FCJ,k^EYRQ@HJmtvo|uj7pPTS');
define('NONCE_KEY',        '`)ME-:-,/kk}SbUF<ak?|WaVK7w*H=)!F(Tt`H&Y7FO`.n~uUPd)V#57P;7VjwPT');
define('AUTH_SALT',        '>#VG/K=^=1rF,e_G0U&;EFZ*b*urG)bge$.hbgrl9-CJN~/+~|)/jo%^bjD9c6B?');
define('SECURE_AUTH_SALT', '.H=2Zw](eiUe&Pg,ckejw=GtrCzy/@t;Ct%>Pr^6;gem6k$!U=XM]eGw-)MD<A>o');
define('LOGGED_IN_SALT',   '{QI)b4Z/;_EZN!P`*E8a<r(B+/~+> UTRQ-<^ N-wFN=FWlCL}ac$;owT{&ggHWZ');
define('NONCE_SALT',       'EHp|+#*W-iL|tmO5`5+~.I)3#/7Co>eX=ti#tPq94l) d~(sA:/M/dq5R4HM`|yy');

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
/* define( 'WP_DEBUG', false ); /*
// wp-config.php (temporary) */
define('WP_DEBUG', false);
define('WP_DEBUG_LOG', false);
define('WP_DEBUG_DISPLAY', false);
/* Add any custom values between this line and the "stop editing" line. */

/**
 * Bridge secret — must match dashboard .env WORDPRESS_BRIDGE_SECRET
 * Used when WordPress booking/contact posts to the CRM API.
 */
define( 'DRB_WORDPRESS_BRIDGE_SECRET', 'M_A_Z_I_Y_A_R' );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
