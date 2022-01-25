<?php

namespace cm4all\wp\impex;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit();
}

require_once __DIR__ . '/trait-impex-singleton.php';
require_once __DIR__ . '/class-impex-part.php';
require_once __DIR__ . '/class-impex-export.php';
require_once __DIR__ . '/class-impex-import.php';
require_once __DIR__ . '/class-impex-import-provider.php';
require_once __DIR__ . '/class-impex-export-provider.php';
require_once __DIR__ . '/class-impex-import-profile.php';
require_once __DIR__ . '/class-impex-export-profile.php';
require_once __DIR__ . '/interface-impex-rest-controller.php';
/**
 * @property-read ImpexExport $Export
 * @property-read ImpexImport $Import
 */
class Impex
{
  const WP_ACTION_REGISTER_PROVIDERS = 'cm4all_wp_impex_register_providers';
  const WP_ACTION_REGISTER_PROFILES = 'cm4all_wp_impex_register_profiles';

  const WP_ACTION_ENQUEUE_IMPEX_PROVIDER_SCRIPT = 'cm4all_wp_impex_enqueue_script';
  const WP_ACTION_ENQUEUE_IMPEX_PROVIDER_STYLE = 'cm4all_wp_impex_enqueue_style';

  const VERSION = '1.0.0';

  const SLICE_DATA = 'data';

  const SLICE_META = 'meta';

  const SLICE_VERSION = 'version';

  const SLICE_META_ENTITY = 'entity';

  const SLICE_TAG = 'tag';

  const SLICE_DATE = 'date';

  const SLICE_TYPE = 'type';

  const SLICE_TYPE_PHP = 'php';

  const SLICE_TYPE_RESOURCE = 'resource';

  const OPTION_LOG = 'option-verbose';

  const OPTION_LOG_DEFAULT = null;

  use ImpexSingleton;

  protected $_export = null;
  protected $_import = null;

  protected function __construct()
  {
    $this->_export = new class extends ImpexExport
    {
    };

    $this->_import = new class extends ImpexImport
    {
    };
  }

  public function __get($property)
  {
    return match ($property) {
      'Export' => $this->_export,
      'Import' => $this->_import,
      default => throw new ImpexRuntimeException(sprintf('abort getting invalid property "%s"', $property)),
    };
  }

  public function __install(): bool
  {
    /* @var string */
    $installed_version = \get_option('impex_version');

    $successful = $this->Export->__install($installed_version) && $this->Import->__install($installed_version);

    if ($installed_version === false) {
      \add_option('impex_version', Impex::VERSION);
    } else if ($installed_version !== Impex::VERSION) {
      \update_option("impex_version", Impex::VERSION);
    }

    return $successful;
  }
}

Impex::getInstance();
