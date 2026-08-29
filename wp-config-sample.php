<?php
/**
 * WordPress configuration template.
 *
 * Copy this file to wp-config.php and replace every placeholder before
 * deploying. Keep wp-config.php outside Git and server-managed.
 */

/** Database name */
define( 'DB_NAME', 'REPLACE_WITH_DATABASE_NAME' );

/** Database username */
define( 'DB_USER', 'REPLACE_WITH_DATABASE_USER' );

/** Database password */
define( 'DB_PASSWORD', 'REPLACE_WITH_DATABASE_PASSWORD' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset */
define( 'DB_CHARSET', 'utf8mb4' );

/** Database collate type. Do not change this if in doubt. */
define( 'DB_COLLATE', '' );

/** WordPress authentication keys and salts. Replace each value with a unique random string. */
define( 'AUTH_KEY',         'REPLACE_WITH_RANDOM_AUTH_KEY' );
define( 'SECURE_AUTH_KEY',  'REPLACE_WITH_RANDOM_SECURE_AUTH_KEY' );
define( 'LOGGED_IN_KEY',    'REPLACE_WITH_RANDOM_LOGGED_IN_KEY' );
define( 'NONCE_KEY',        'REPLACE_WITH_RANDOM_NONCE_KEY' );
define( 'AUTH_SALT',        'REPLACE_WITH_RANDOM_AUTH_SALT' );
define( 'SECURE_AUTH_SALT', 'REPLACE_WITH_RANDOM_SECURE_AUTH_SALT' );
define( 'LOGGED_IN_SALT',   'REPLACE_WITH_RANDOM_LOGGED_IN_SALT' );
define( 'NONCE_SALT',       'REPLACE_WITH_RANDOM_NONCE_SALT' );

/** WordPress-to-dashboard bridge secret. Use the same value in the dashboard .env. */
define( 'DRB_WORDPRESS_BRIDGE_SECRET', 'REPLACE_WITH_WORDPRESS_BRIDGE_SECRET' );

$table_prefix = 'wp_';

define( 'WP_DEBUG', false );

/* Add custom values above this line. */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

require_once ABSPATH . 'wp-settings.php';
