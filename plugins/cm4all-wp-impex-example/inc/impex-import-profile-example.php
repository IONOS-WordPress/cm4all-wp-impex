<?php

namespace cm4all\wp\impex\example;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit();
}

use cm4all\wp\impex\Impex;
use cm4all\wp\impex\ImpexImport;
use cm4all\wp\impex\ImpexTransformationContext;

require_once(ABSPATH . 'wp-admin/includes/plugin.php');

$profile = Impex::getInstance()->Import->addProfile('impex-import-profile-example');
$profile->setDescription('Import everything example with event listener');

$profile->addTask('main', Impex::getInstance()->Import->getProvider('all')->name);

$profile->events(ImpexImport::EVENT_IMPORT_END)->addListener(
  'reset ninja forms mainentance mode',
  function (ImpexTransformationContext $transformationContext, array $imported) {
    if(method_exists('WPN_Helper', 'set_forms_maintenance_mode')) {
      \WPN_Helper::set_forms_maintenance_mode(0);
    }

    // remap old import ids to new ids in option "theme_mods_trinity-core"
    if(in_array('theme_mods_trinity-core', $imported['options'])) {
      $theme_mods_trinity_core = \get_option('theme_mods_trinity-core');
      // remap custom logo if set
      $custom_logo = $theme_mods_trinity_core['custom_logo'];
      if($custom_logo!==-1 && ($imported['posts'][$custom_logo] ?? false)) {
        $theme_mods_trinity_core['custom_logo'] = $imported['posts'][$custom_logo];
      }
      // remap custom_css_post_id if set
      $custom_css_post_id = $theme_mods_trinity_core['custom_css_post_id'];
      if($custom_css_post_id!==-1 && ($imported['posts'][$custom_css_post_id] ?? false)) {
        $theme_mods_trinity_core['custom_css_post_id'] = $imported['posts'][$custom_css_post_id];
      }
      // remap nav_menu_locations if set
      $nav_menu_locations = $theme_mods_trinity_core['nav_menu_locations'] ?: [];
      foreach ($nav_menu_locations as $nav_menu_name => $old_nav_menu_term_id) {
        if (array_key_exists($old_nav_menu_term_id, $imported['terms'])) {
          $nav_menu_locations[$nav_menu_name] = $imported['terms'][$old_nav_menu_term_id];
        }
      }
      // re-assign modified nav_menu_locations
      $theme_mods_trinity_core['nav_menu_locations'] = $nav_menu_locations;

      \update_option('theme_mods_trinity-core', $theme_mods_trinity_core);
    }
  },
);
