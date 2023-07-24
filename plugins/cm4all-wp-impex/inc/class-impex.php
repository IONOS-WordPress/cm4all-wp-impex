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

  const CRON_JOB_CLEANUP_NAME = 'cm4all_wp_impex_cron_job_cleanup';

  const CRON_JOB_CLEANUP_INTERVAL_NAME = 'cm4all_wp_impex_cron_job_interval';

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

  public function __uninstall(): void
  {
    $timestamp = \wp_next_scheduled( self::CRON_JOB_CLEANUP_NAME );
    \wp_unschedule_event( $timestamp, self::CRON_JOB_CLEANUP_NAME );
  }

  public function __install(): bool
  {
    if(!\wp_next_scheduled(self::CRON_JOB_CLEANUP_NAME)) {
      \wp_schedule_event(time(), self::CRON_JOB_CLEANUP_INTERVAL_NAME, self::CRON_JOB_CLEANUP_NAME);
    }

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

/*
  to debug the cleanup cronjob
  - add the following snippet to the head of plugin.php
    if ( ! defined( 'WP_CRON_LOCK_TIMEOUT' ) ) {
      define( 'WP_CRON_LOCK_TIMEOUT', 10 );
    }
  - replace 'interval' => WEEK_IN_SECONDS, with 'interval' => WP_CRON_LOCK_TIMEOUT, (see below)
*/
\add_filter( 'cron_schedules', function ( $schedules ) {
  $schedules[Impex::CRON_JOB_CLEANUP_INTERVAL_NAME] = array(
      'interval' => WEEK_IN_SECONDS,
      'display' => Impex::CRON_JOB_CLEANUP_INTERVAL_NAME
  );
  return $schedules;
});

/**
 * cron triggered action to cleanup deprecated import/export snapshots
 *
 * such snapshots may be a result of a failed or aborted import or export operations
 */
\add_action(Impex::CRON_JOB_CLEANUP_NAME, function() {
  $exports = \get_option(ImpexExport::WP_OPTION_EXPORTS, []);
  foreach ($exports as $export) {
    if(str_starts_with($export['name'], 'transient-')) {
      $created = strtotime($export['created']);
      $current = time();

      $hoursSinceCreation = ($current - $created)/60/60;
      // if the snapshot was created more than 36 hours ago
      if($hoursSinceCreation > 36) {
        Impex::getInstance()->Export->remove($export['id']);
      }
    }
  }

  $imports = \get_option(ImpexImport::WP_OPTION_IMPORTS, []);
  foreach ($imports as $import) {
    if(str_starts_with($import['name'], 'transient-')) {
      $created = strtotime($import['created']);
      $current = time();

      $hoursSinceCreation = ($current - $created)/60/60;
      // if the snapshot was created more than 36 hours ago
      if($hoursSinceCreation > 36) {
        Impex::getInstance()->Import->remove($import['id']);
      }
    }
  }
});
