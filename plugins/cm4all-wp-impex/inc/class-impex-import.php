<?php

namespace cm4all\wp\impex;

use WPN_Helper;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit();
}

require_once __DIR__ . '/class-impex-part.php';
require_once __DIR__ . '/class-impex-import-provider.php';
require_once __DIR__ . '/class-impex-import-profile.php';
require_once __DIR__ . '/class-impex-import-runtime-exception.php';
abstract class ImpexImport extends ImpexPart
{
  const DB_CHUNKS_TABLENAME = 'impex_import_chunks';
  const WP_OPTION_IMPORTS = 'impex_imports';

  const EVENT_IMPORT_END = 'cm4all_wp_import_end';

  protected string $_db_chunks_tablename;

  public function __construct()
  {
    parent::__construct();

    global $wpdb;
    $this->_db_chunks_tablename = $wpdb->prefix . self::DB_CHUNKS_TABLENAME;
  }

  protected function _createProvider(string $name, callable $cb): ImpexImportProvider
  {
    return new class($name, $cb) extends ImpexImportProvider
    {
      public function __construct($name, $cb)
      {
        parent::__construct($name, $cb);
      }
    };
  }

  protected function _createProfile(string $name, ImpexPart $context): ImpexImportProfile
  {
    return new class($name, $context) extends ImpexImportProfile
    {
      public function __construct($name, $context)
      {
        parent::__construct($name, $context);
      }
    };
  }

  public function  __install(string|bool $installed_version): bool
  {
    global $wpdb;

    if ($installed_version === false) {
      // plugin was newly installed
      $charset_collate = $wpdb->get_charset_collate();

      $sql = "CREATE TABLE {$this->_db_chunks_tablename} (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        import_id CHAR(36) NOT NULL,
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

    return $this->__install_data($installed_version);
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

  function _upsert_slice(string $import_id, int $position, array $slice): bool
  {
    $json = json_encode($slice);
    if ($json === false) {
      throw new ImpexExportRuntimeException(sprintf('failed to encode slice to json : %s(=%s)', json_last_error(), json_last_error_msg()));
    }

    /** @var wpdb */
    global $wpdb;

    $data = [
      'position' => $position,
      'import_id' => $import_id,
      'slice' => $json,
    ];

    $existing_id = $wpdb->get_var(
      $wpdb->prepare("SELECT DISTINCT id from {$this->_db_chunks_tablename} WHERE import_id=%s and position=%d", $import_id, $position)
    );

    if ($existing_id !== null) {
      $data['id'] = $existing_id;
    }

    return $wpdb->replace(
      $this->_db_chunks_tablename,
      $data,
    );
  }

  function create(ImpexImportProfile $profile, array $options = [], string $name = '',  string $description = ''): ImpexImportTransformationContext
  {
    $transformationContext = new ImpexImportTransformationContext(
      profile_name: $profile->name,
      name: $name,
      description: $description,
      options: $options,
    );

    $imports = \get_option(self::WP_OPTION_IMPORTS, []);

    $imports[] = $transformationContext->jsonSerialize();

    \update_option(self::WP_OPTION_IMPORTS, $imports);

    return $transformationContext;
  }

  /**
   * @return Generator|array[]
   */
  function get_slices(string $import_id, int $limit = PHP_INT_MAX, int $offset = 0): \Generator
  {
    global $wpdb;

    $rows = $wpdb->get_results(
      $wpdb->prepare("SELECT * from {$this->_db_chunks_tablename} WHERE import_id=%s ORDER BY position LIMIT %d OFFSET %d", $import_id, $limit, $offset)
    );
    foreach ($rows as $row) {
      yield json_decode($row->slice, JSON_OBJECT_AS_ARRAY);
    }
  }

  /**
   * @TODO: makes it sense to rename this function to aggregate or reduce ? 
   * 
   * @return array[] return uncomsumed slices
   */
  function consume(ImpexImportTransformationContext $transformationContext, int $limit = PHP_INT_MAX, int $offset = 0): array
  {
    $uncomsumed_slices = [];

    $options = $transformationContext->options;
    $profile = $transformationContext->profile;

    foreach ($this->get_slices($transformationContext->id, $limit, $offset) as $slice) {
      $consumed = false;

      foreach ($profile->getTasks() as $task) {
        if ($task->disabled) {
          continue;
        }

        $_options = self::_computeOptions($task, $options);

        $consumed = call_user_func($task->provider->callback, $slice, $_options, $transformationContext);

        if ($consumed) {
          break;
        }
      }

      if (!$consumed) {
        array_push($uncomsumed_slices, $slice);
      }
    }

    $profile->events(self::EVENT_IMPORT_END)($transformationContext, [
      'uncomsumed_slices' => &$uncomsumed_slices,
      'limit' => $limit,
      'offset' => $offset,
    ]);

    return $uncomsumed_slices;
  }

  function update(string $import_id, array $data): array|bool
  {
    $imports = \get_option(self::WP_OPTION_IMPORTS, []);
    foreach ($imports as &$import) {
      if ($import['id'] === $import_id) {
        foreach ($data as $key => $value) {
          // prevent updating 'id', 'options', 'profile', 'user', 'created'
          if (array_search($key, ['id', 'options', 'profile', 'user', 'created']) === false) {
            if ($value === null) {
              unset($import[$key]);
            } else {
              $import[$key] = $value;
            }
          }
        }

        \update_option(self::WP_OPTION_IMPORTS, $imports);

        return $import;
      }
    };

    return false;
  }

  function remove(string $import_id): bool|array
  {
    $imports = \get_option(self::WP_OPTION_IMPORTS, []);
    foreach ($imports as $index => $import) {
      if ($import['id'] === $import_id) {
        $transformationContext = ImpexImportTransformationContext::fromJson($import);

        global $wpdb;
        global $wp_filesystem;

        \WP_Filesystem();

        // remove matching export table rows
        $rowsDeleted = $wpdb->delete($this->_db_chunks_tablename, ['import_id' => $import_id,]);
        if ($rowsDeleted === false) {
          throw new ImpexExportRuntimeException(sprintf('failed to delete jsonized slices from database : %s', $wpdb->last_error));
        }

        // remove export specific uploads directory
        if ($wp_filesystem->exists($transformationContext->path)) {
          $wp_filesystem->rmdir($transformationContext->path, true);
        }

        $removedItems = array_splice($imports, $index, 1);
        \update_option(self::WP_OPTION_IMPORTS, $imports);

        return $removedItems[0];
      }
    };

    return false;
  }
}
