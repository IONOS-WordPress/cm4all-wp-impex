<?php

namespace cm4all\wp\impex;

use RuntimeException;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit();
}

require_once __DIR__ . '/class-impex.php';
require_once __DIR__ . '/interface-impex-rest-controller.php';
require_once __DIR__ . '/class-impex-profile-rest-controller.php';

class ImpexImportProfileRESTController extends ImpexProfileRESTController
{
  const REST_BASE = '/import/profile';

  public function __construct()
  {
    parent::__construct('Import', self::REST_BASE);
  }
}

\add_action(
  'rest_api_init',
  function () {
    $controller = new ImpexImportProfileRESTController();
    $controller->register_routes();
  },
);
