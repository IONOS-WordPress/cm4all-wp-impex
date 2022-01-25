<?php
# bootstrap.php

require_once(dirname(__DIR__) . '/../vendor/autoload.php');


// Determine the tests directory (from a WP dev checkout).
// Try the WP_TESTS_DIR environment variable first.
$__wp_tests_dir = getenv('WP_TESTS_DIR');

// Next, try the WP_PHPUNIT composer package.
if (!$__wp_tests_dir) {
  $__wp_tests_dir = getenv('WP_PHPUNIT__DIR');
}

require_once $__wp_tests_dir . '/includes/functions.php';

\tests_add_filter('muplugins_loaded', function () {
  require_once dirname(__FILE__) . '/../../plugin.php';
  require_once ABSPATH . 'wp-content/plugins/gutenberg/gutenberg.php';
});

/**
 * Adds a wp_die handler for use during tests.
 *
 * If bootstrap.php triggers wp_die, it will not cause the script to fail. This
 * means that tests will look like they passed even though they should have
 * failed. So we throw an exception if WordPress dies during test setup. This
 * way the failure is observable.
 *
 * @param string|WP_Error $message The error message.
 *
 * @throws Exception When a `wp_die()` occurs.
 */
function fail_if_died($message)
{
  if (\is_wp_error($message)) {
    $message = $message->get_error_message();
  }

  throw new Exception('WordPress died: ' . $message);
}
tests_add_filter('wp_die_handler', 'fail_if_died');

$GLOBALS['wp_tests_options'] = [
  'gutenberg-experiments' => [
    'gutenberg-widget-experiments' => '1',
    'gutenberg-full-site-editing'  => 1,
  ],
  /*
  'active_plugins' => [
    'cm4all-wp-impex-example/plugin.php',
    'cm4all-wp-impex/plugin.php',
    'gutenberg/gutenberg.php',
  ],
  */
];

// Start up the WP testing environment.
require $__wp_tests_dir . '/includes/bootstrap.php';

// Use existing behavior for wp_die during actual test execution.
\remove_filter('wp_die_handler', 'fail_if_died');

/**
 * Loads an SQL stream into the WordPress database one command at a time.
 *
 * @params $sqlfile The file containing the mysql-dump data.
 * @return boolean Returns true, if SQL was imported successfully.
 * @throws Exception
 */
function importSQL($sqlfile)
{
  //load WPDB global
  global $wpdb;

  // Temporary variable, used to store current query
  $templine = '';
  // Read in entire file
  $lines = file($sqlfile);
  // Loop through each line
  foreach ($lines as $line) {
    // Skip it if it's a comment
    if (substr($line, 0, 2) == '/*' || substr($line, 0, 2) == '--' || $line == '')
      continue;

    // Add this line to the current segment
    $templine .= $line;
    // If it has a semicolon at the end, it's the end of the query
    if (substr(trim($line), -1, 1) == ';') {
      // Perform the query
      if ($wpdb->query($templine) === false) {
        print('Error performing query \'' . $templine . '\': ' . $wpdb->last_error . "\'");
        print('');
      }
      // Reset temp variable to empty
      $templine = '';
    }
  }
}

require_once __DIR__ . '/impex-unittestcase.php';
require_once __DIR__ . '/impex-rest-unittestcase.php';
