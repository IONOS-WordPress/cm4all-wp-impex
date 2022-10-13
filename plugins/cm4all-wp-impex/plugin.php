<?php

/**
 * Plugin Name: cm4all-wp-impex
 * Plugin URI: https://github.com/IONOS-WordPress/cm4all-wp-impex
 * Description: ImpEx contributes extendable Import / Export functionality to WordPress
 * Version: 1.5.1
 * Tags: import, export, migration
 * Requires PHP: 8.0
 * Requires at least: 5.7
 * Tested up to: 6.0.2
 * Author: Lars Gersmann, CM4all
 * Author URI: https://cm4all.com
 * Domain Path: /languages
 **/

namespace cm4all\wp\impex;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit();
}

require_once __DIR__ . '/inc/class-impex.php';
require_once __DIR__ . '/inc/impex-export-extension-content.php';
require_once __DIR__ . '/inc/impex-import-extension-content.php';
require_once __DIR__ . '/inc/impex-export-extension-attachments.php';
require_once __DIR__ . '/inc/impex-import-extension-attachment.php';
require_once __DIR__ . '/inc/impex-export-extension-db-tables.php';
require_once __DIR__ . '/inc/impex-import-extension-db-table.php';
require_once __DIR__ . '/inc/impex-export-extension-wp-options.php';
require_once __DIR__ . '/inc/impex-import-extension-wp-options.php';
require_once __DIR__ . '/inc/class-impex-export-profile-rest-controller.php';
require_once __DIR__ . '/inc/class-impex-import-profile-rest-controller.php';
require_once __DIR__ . '/inc/class-impex-export-rest-controller.php';
require_once __DIR__ . '/inc/class-impex-import-rest-controller.php';

require_once __DIR__ . '/inc/wp-dashboard-contributions.php';

/**
 * db table creation/upgrade mechanism:
 * @see https://codex.wordpress.org/Creating_Tables_with_Plugins
 */

\register_activation_hook(__FILE__, function () {
  // ensure required PHP function fnmatch() exists
  if (!function_exists('fnmatch')) {
    \wp_die('<h3>Plugin activation aborted</h3><p>The <strong>cm4all-wp-impex</strong> plugin requires PHP function <a href="https://www.php.net/manual/en/function.fnmatch.php" target="_blank"><code>fnmatch</code></a> to be available.</p>', 'Plugin Activation Error', ['response' => 500, 'back_link' => TRUE]);
  }

  Impex::getInstance()->__install();
});

\register_deactivation_hook(__FILE__, function () {
  Impex::getInstance()->__uninstall();
});

// disable wordpress update notifications in the cloud to suppress php errors in the cloud
// @TODO: remove when its deployed in the cm4all-wordpress plugin
if (str_ends_with($_SERVER['SERVER_NAME'] ?? '', '.s-cm4all.cloud')) {
  \add_filter('pre_site_transient_update_core', '\__return_null');
}

\add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
  array_unshift(
    $links,
    sprintf(
      '<a href="%s">%s</a>',
      \admin_url('tools.php?page=cm4all_wp_impex_wp_admin_dashboard'),
      \__('Settings', 'cm4all-wp-impex')
    ),
    sprintf(
      '<a target="_blank" href="%s">%s</a>',
      'https://github.com/IONOS-WordPress/cm4all-wp-impex/issues',
      \__('Support', 'cm4all-wp-impex')
    ),
  );

  return $links;
});

\add_action(
  'plugins_loaded',
  function () {
    if (\get_option('impex_version') !== Impex::VERSION) {
      Impex::getInstance()->__install();
    }

    \do_action(Impex::WP_ACTION_REGISTER_PROVIDERS);
    \do_action(Impex::WP_ACTION_REGISTER_PROFILES);
  },
);

function enqueueClientAssets(bool $in_footer): string
{
  static $CLIENT_ASSET_HANDLE;

  if (!is_string($CLIENT_ASSET_HANDLE)) {
    $CLIENT_ASSET_HANDLE = str_replace('\\', '-', __NAMESPACE__);
    \add_action(
      Impex::WP_ACTION_ENQUEUE_IMPEX_PROVIDER_SCRIPT,
      function ($client_asset_handle, $in_footer) {
        \cm4all\wp\impex\wp_enqueue_script(
          $client_asset_handle,
          'dist/wp.impex.js',
          [],
          $in_footer
        );

        $DEBUG_HANDLE = $client_asset_handle . '-debug';
        \cm4all\wp\impex\wp_enqueue_script(
          $DEBUG_HANDLE,
          'dist/wp.impex.debug.js',
          [$client_asset_handle],
          $in_footer
        );

        $STORE_HANDLE = $client_asset_handle . '-store';
        \cm4all\wp\impex\wp_enqueue_script(
          $STORE_HANDLE,
          'dist/wp.impex.store.js',
          [$client_asset_handle, $DEBUG_HANDLE, 'wp-api-fetch', 'wp-data', 'wp-hooks'],
          $in_footer
        );

        // prefetch initial impex data 
        $discoveryRequest = new \WP_REST_Request('GET', '/');
        $discoveryResponse = \rest_do_request($discoveryRequest);

        $exportProfilesRequest = new \WP_REST_Request('GET', ImpexRestController::BASE_URI . ImpexExportProfileRESTController::REST_BASE);
        $exportProfilesResponse = \rest_do_request($exportProfilesRequest);

        $exportsRequest = new \WP_REST_Request('GET', ImpexRestController::BASE_URI . ImpexExportRESTController::REST_BASE);
        $exportsResponse = \rest_do_request($exportsRequest);

        $importProfilesRequest = new \WP_REST_Request('GET', ImpexRestController::BASE_URI . ImpexImportProfileRESTController::REST_BASE);
        $importProfilesResponse = \rest_do_request($importProfilesRequest);

        $importsRequest = new \WP_REST_Request('GET', ImpexRestController::BASE_URI . ImpexImportRESTController::REST_BASE);
        $importsResponse = \rest_do_request($importsRequest);

        $currentUserRequest = new \WP_REST_Request('GET', '/wp/v2/users/me');
        $currentUserResponse = \rest_do_request($currentUserRequest);

        \wp_add_inline_script(
          $STORE_HANDLE,
          sprintf(
            'wp.apiFetch.use( wp.apiFetch.createPreloadingMiddleware( %s ) );',
            \wp_json_encode([
              $discoveryRequest->get_route() => [
                'body' => $discoveryResponse->data,
                'headers' => $discoveryResponse->headers,
              ],
              $exportProfilesRequest->get_route() => [
                'body' => $exportProfilesResponse->data,
                'headers' => $exportProfilesResponse->headers,
              ],
              $exportsRequest->get_route() => [
                'body' => $exportsResponse->data,
                'headers' => $exportsResponse->headers,
              ],
              $importProfilesRequest->get_route() => [
                'body' => $importProfilesResponse->data,
                'headers' => $importProfilesResponse->headers,
              ],
              $importsRequest->get_route() => [
                'body' => $importsResponse->data,
                'headers' => $importsResponse->headers,
              ],
              $currentUserRequest->get_route() => [
                'body' => $currentUserResponse->data,
                'headers' => $currentUserResponse->headers,
              ],
            ])
          )
            // add store initialization code
            . sprintf("\nwp.impex.store.default(%s);", json_encode([
              'namespace' => ImpexRestController::NAMESPACE,
              'base_uri' => ImpexRestController::BASE_URI,
              'site_url' => get_site_url(),
            ])),
          'after'
        );
      },
      10,
      2,
    );

    // register dummy style 
    wp_register_style(
      $CLIENT_ASSET_HANDLE,
      'dist/wp.impex.css'
    );
  }

  \do_action(Impex::WP_ACTION_ENQUEUE_IMPEX_PROVIDER_SCRIPT, $CLIENT_ASSET_HANDLE, $in_footer);
  \do_action(Impex::WP_ACTION_ENQUEUE_IMPEX_PROVIDER_STYLE,  $CLIENT_ASSET_HANDLE);

  return $CLIENT_ASSET_HANDLE;
}

\add_action(
  'init',
  fn () => \load_plugin_textdomain('cm4all-wp-impex', false, basename(__DIR__) . '/languages/'),
);

\add_action(Impex::WP_ACTION_REGISTER_PROFILES, function () {
  require_once __DIR__ . '/profiles/export-profile-base.php';
  require_once __DIR__ . '/profiles/import-profile-all.php';
}, 0);
