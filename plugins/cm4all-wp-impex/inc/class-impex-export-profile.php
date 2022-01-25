<?php

namespace cm4all\wp\impex;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit();
}

require_once __DIR__ . '/interface-impex-named-item.php';
require_once __DIR__ . '/class-impex-profile.php';

abstract class ImpexExportProfile extends ImpexProfile
{
}
