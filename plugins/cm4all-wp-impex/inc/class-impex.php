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

  const DB_SNAPSHOTS_TABLENAME = 'impex_snapshots';

  protected function __construct()
  {
    global $wpdb;
    $_db_chunks_tablename = $wpdb->prefix . self::DB_SNAPSHOTS_TABLENAME;
    $this->_export = new class($_db_chunks_tablename) extends ImpexExport
    {
    };

    $this->_import = new class($_db_chunks_tablename) extends ImpexImport
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

    global $wpdb;

    if ($installed_version === false) {
      // plugin was newly installed
      $charset_collate = $wpdb->get_charset_collate();

      $_db_chunks_tablename = "{$wpdb->prefix}" . Impex::DB_SNAPSHOTS_TABLENAME;

      $sql = "CREATE TABLE {$_db_chunks_tablename} (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        snapshot_id CHAR(36) NOT NULL,
        position mediumint(9) NOT NULL,
        slice json NOT NULL,
        PRIMARY KEY  (id)
      ) $charset_collate;";

      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      \dbDelta($sql);
    } else if ($installed_version !== Impex::VERSION) {
      // new plugin version is now installed, try to upgrade
      /*
      $sql = "CREATE TABLE {$this->_db_chunks_tablename} (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        name tinytext NOT NULL,
        text text NOT NULL,
        url varchar(100) DEFAULT '' NOT NULL,
        PRIMARY KEY  (id)
      );";
  
      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      dbDelta($sql);
      */
    }

    $successful = $this->__install_data($installed_version);

    if ($installed_version === false) {
      \add_option('impex_version', Impex::VERSION);
    } else if ($installed_version !== Impex::VERSION) {
      \update_option("impex_version", Impex::VERSION);
    }

    return $successful;
  }

  protected function __install_data(string|bool $installed_version): bool
  {
    /*
    global $wpdb;

    $welcome_name = 'Mr. WordPress';
    $welcome_text = 'Congratulations, you just completed the installation!';

    $wpdb->insert(
      $this->_db_chunks_tablename,
      [
        'time' => current_time('mysql'),
        'name' => $welcome_name,
        'text' => $welcome_text,
      ]
    );
    */

    return true;
  }
}

Impex::getInstance();
