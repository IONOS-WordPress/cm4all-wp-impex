<?php

/* Path to the WordPress codebase you'd like to test. Add a forward slash in the end. */
define( 'ABSPATH', '/var/www/html/' );

/* for some reason we need to map the environment variables (provided by <wp-env-home>/.../docker.compose.yml) to its pendants without WORDPRESS_ prefix */
define( 'DB_NAME', getenv('WORDPRESS_DB_NAME'));
define( 'DB_USER', getenv('WORDPRESS_DB_USER'));
define( 'DB_PASSWORD', getenv('WORDPRESS_DB_PASSWORD'));
define( 'DB_HOST', getenv('WORDPRESS_DB_HOST'));
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

/*
 * Path to the theme to test with.
 *
 * The 'default' theme is symlinked from test/phpunit/data/themedir1/default into
 * the themes directory of the WordPress installation defined above.
 */
// define( 'WP_DEFAULT_THEME', 'default' );

// Test with WordPress debug mode (default).
define( 'WP_DEBUG', true );

// This is configurable by setting the WP_PHPUNIT__TABLE_PREFIX environment variable.
// If set, this will take precedence over what is set in your tests config file.
// If not, and the $table_prefix variable is not set in your tests config file then it will use wptests_ as a fallback.
$table_prefix = 'wp_';   // Only numbers, letters, and underscores please!

#define( 'WPLANG', '' );

/* minimal set of constants required by wp php unit */
define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Test Blog' );

define( 'WP_PHP_BINARY', 'php' );
