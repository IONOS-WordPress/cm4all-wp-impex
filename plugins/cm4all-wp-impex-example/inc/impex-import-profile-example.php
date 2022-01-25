<?php

namespace cm4all\wp\impex\example;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit();
}

use cm4all\wp\impex\Impex;
use cm4all\wp\impex\ImpexImport;

require_once(ABSPATH . 'wp-admin/includes/plugin.php');

$profile = Impex::getInstance()->Import->addProfile('impex-import-profile-example');
$profile->setDescription('Import everything example with event listener');

$profile->addTask('main', Impex::getInstance()->Import->getProvider('all')->name);

$profile->events(ImpexImport::EVENT_IMPORT_END)->addListener(
  'reset ninja forms mainentance mode',
  fn () => method_exists('WPN_Helper', 'set_forms_maintenance_mode') && \WPN_Helper::set_forms_maintenance_mode(0)
);
