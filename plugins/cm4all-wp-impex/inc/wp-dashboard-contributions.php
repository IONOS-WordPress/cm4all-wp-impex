<?php

namespace cm4all\wp\impex\wp_admin\dashboard;

use function cm4all\wp\impex\enqueueClientAssets;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit();
}

require_once __DIR__ . '/wp-wrapper-functions.php';

const TITLE = 'Impex';
// slug cannot be defined as const since constants need to be available at compile time
// => https://stackoverflow.com/questions/51000541/php-constant-expression-contains-invalid-operations
define('IMPEX_SCREEN_PAGE_SLUG', str_replace("\\", '_', __NAMESPACE__));
// ATTENTION : the '_page' suffix is important !
// otherwise storeing the value via filter 'set-screen-option' will fail
const SCREEN_OPTION_VERBOSE = IMPEX_SCREEN_PAGE_SLUG . '_' . 'verbose_page';

\add_action(
  "admin_menu",
  function () {
    $submenu_page_hook_suffix = \add_submenu_page('tools.php', TITLE, TITLE, 'manage_options', IMPEX_SCREEN_PAGE_SLUG, function () {
      require_once ABSPATH . 'wp-admin/admin-header.php';

      echo sprintf('<div class="wrap" id="%s"></div>', IMPEX_SCREEN_PAGE_SLUG);

      require_once ABSPATH . 'wp-admin/admin-footer.php';
    }, 0);

    \add_action(
      'load-' . $submenu_page_hook_suffix,
      function () {
        \get_current_screen()->add_help_tab(
          [
            'id'      => 'help-overview',
            'title'   => __('Overview', 'cm4all-wp-impex'),
            'content' => '
          <p>
          ImpEx provides extensible Import and Export capabilities across plugins / themes by providing a hook mechanism to 3rd-party plugins and themes.
          </p>
          <p>
          ImpEx supports a pluggable provider interface to export / import custom data. ImpEx export and import can be customized ab applying filters.
          </p>
          <p>
          By default a self contained, streamable directory containing JSON files and media blobs gets exported and vice versa imported.
          </p>
        ',
          ]
        );

        \get_current_screen()->add_help_tab(
          [
            'id'      => 'help-export',
            'title'   => __('Export', 'cm4all-wp-impex'),
            'content' =>
            '<p>' . __('Export will create a snapshot of the data provided by the ImpEx export profile.<p/><p>The snapshot can be downloaded to a directory on your local machine.') . '</p>'

          ]
        );

        \get_current_screen()->add_help_tab(
          [
            'id'      => 'help-import',
            'title'   => __('Import', 'cm4all-wp-impex'),
            'content' => '<p>' . __('Import allows you to upload a (previously created) ImpEx snapshot and import it to your WordPress instance.') . '</p>'
          ]
        );

        \get_current_screen()->set_help_sidebar(
          sprintf(
            '<p><strong>%s</strong></p><p>%s</p><p>%s</p>',
            __('For more information:', 'cm4all-wp-impex'),
            __('<a href="https://ionos-wordpress.github.io/cm4all-wp-impex/">Documentation on Impex</a>', 'cm4all-wp-impex'),
            __('<a href="https://github.com/IONOS-WordPress/cm4all-wp-impex/issues">Support</a>', 'cm4all-wp-impex')
          )
        );

        $IN_FOOTER = true;
        $IMPEX_CLIENT_HANDLE = enqueueClientAssets($IN_FOOTER);
        \cm4all\wp\impex\wp_enqueue_script(
          IMPEX_SCREEN_PAGE_SLUG,
          'build/wp.impex.dashboard.js',
          [$IMPEX_CLIENT_HANDLE, 'wp-element', 'wp-api-fetch', 'wp-url', 'wp-i18n', 'wp-components', 'wp-data', 'wp-core-data'],
          $IN_FOOTER
        );
        \wp_set_script_translations(
          IMPEX_SCREEN_PAGE_SLUG,
          $IMPEX_CLIENT_HANDLE,
          plugin_dir_path(__DIR__) . 'languages'
        );

        \cm4all\wp\impex\wp_enqueue_style(
          IMPEX_SCREEN_PAGE_SLUG,
          'build/wp.impex.dashboard.css',
          [$IMPEX_CLIENT_HANDLE, 'wp-components']
        );

        /*
          prevent loading wp admin forms.css since it breaks gutenberg component styles
          wp_register_style doesn't overwrite exiting style registrations so that we need to
          - remove the original style
          - add a dummy style handle for 'forms'
        */
        \wp_deregister_style('forms');
        \wp_register_style('forms', '');
      },
    );
  },
);
