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
  hook_name: "admin_menu",
  callback: function () {
    $submenu_page_hook_suffix = \add_submenu_page('tools.php', TITLE, TITLE, 'manage_options', IMPEX_SCREEN_PAGE_SLUG, function () {
      require_once ABSPATH . 'wp-admin/admin-header.php';

      echo sprintf('<div class="wrap" id="%s"></div>', IMPEX_SCREEN_PAGE_SLUG);

      require_once ABSPATH . 'wp-admin/admin-footer.php';
    }, 0);

    \add_action(
      hook_name: 'load-' . $submenu_page_hook_suffix,
      callback: function () {
        \get_current_screen()->add_help_tab(
          [
            'id'      => 'help-overview',
            'title'   => __('Overview', 'cm4all-wp-impex'),
            'content' => '
          <p>
          Impex provides extensible Import and Export capabilities across plugins / themes by providing a hook mechanism to 3rd-party plugins and themes.
          </p>
          <p>
          Impex supports a pluggable provider interface to export / import custom data. Impex export and import can be customized ab applying filters.
          </p>
          <p>
          By default a self contained, streamable <a href="https://cbor.io/">CBOR</a> archive gets exported (also including media) and vice versa imported.
          </p>
        ',
          ]
        );

        \get_current_screen()->add_help_tab(
          [
            'id'      => 'help-export',
            'title'   => __('Export', 'cm4all-wp-impex'),
            'content' => '
          TODO
        '
          ]
        );

        \get_current_screen()->add_help_tab(
          [
            'id'      => 'help-import',
            'title'   => __('Import', 'cm4all-wp-impex'),
            'content' => '
          TODO
        '
          ]
        );

        \get_current_screen()->set_help_sidebar(
          sprintf(
            '<p><strong>%s</strong></p><p>%s</p><p>%s</p>',
            __('For more information:', 'cm4all-wp-impex'),
            __('<a href="https://wordpress.org/support/article/tools-screen/">Documentation on Impex</a>', 'cm4all-wp-impex'),
            __('<a href="https://wordpress.org/support/">Support</a>', 'cm4all-wp-impex')
          )
        );

        $IN_FOOTER = true;
        $IMPEX_CLIENT_HANDLE = enqueueClientAssets($IN_FOOTER);
        \cm4all\wp\impex\wp_enqueue_script(
          handle: IMPEX_SCREEN_PAGE_SLUG,
          pluginRelativePath: 'dist/wp.impex.dashboard.js',
          deps: [$IMPEX_CLIENT_HANDLE, 'wp-element', 'wp-api-fetch', 'wp-url', 'wp-i18n', 'wp-components', 'wp-data', 'wp-core-data'],
          in_footer: $IN_FOOTER
        );
        \wp_set_script_translations(
          handle: IMPEX_SCREEN_PAGE_SLUG,
          domain: $IMPEX_CLIENT_HANDLE,
          path: plugin_dir_path(__DIR__) . 'languages'
        );

        \cm4all\wp\impex\wp_enqueue_style(
          handle: IMPEX_SCREEN_PAGE_SLUG,
          pluginRelativePath: 'dist/wp.impex.dashboard.css',
          deps: [$IMPEX_CLIENT_HANDLE, 'wp-components']
        );

        /* 
      prevent loading wp admin forms.css since it breaks gutenberg component styles
      wp_register_style doesnt overwrite exiting style registrations so that we need to 
      - remove the original style 
      - add a dummy style handle for 'forms'
    */
        \wp_deregister_style(handle: 'forms');
        \wp_register_style(handle: 'forms', src: '');
      },
    );
  },
);
