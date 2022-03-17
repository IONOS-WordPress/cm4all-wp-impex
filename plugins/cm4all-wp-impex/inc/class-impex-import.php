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
  const WP_OPTION_IMPORTS = 'impex_imports';

  const EVENT_IMPORT_END = 'cm4all_wp_import_end';

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

  function _upsert_slice(string $snapshot_id, int $position, array $slice): bool
  {
    $json = json_encode($slice);
    if ($json === false) {
      throw new ImpexExportRuntimeException(sprintf('failed to encode slice to json : %s(=%s)', json_last_error(), json_last_error_msg()));
    }

    /** @var wpdb */
    global $wpdb;

    $data = [
      'position' => $position,
      'snapshot_id' => $snapshot_id,
      'slice' => $json,
    ];

    $existing_id = $wpdb->get_var(
      $wpdb->prepare("SELECT DISTINCT id from {$this->_db_chunks_tablename} WHERE snapshot_id=%s and position=%d", $snapshot_id, $position)
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
  function get_slices(string $snapshot_id, int $limit = PHP_INT_MAX, int $offset = 0): \Generator
  {
    global $wpdb;

    $rows = $wpdb->get_results(
      $wpdb->prepare("SELECT * from {$this->_db_chunks_tablename} WHERE snapshot_id=%s ORDER BY position LIMIT %d OFFSET %d", $snapshot_id, $limit, $offset)
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
    $unconsumed_slices = [];

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
        $unconsumed_slices[] = $slice;
      }
    }

    $profile->events(self::EVENT_IMPORT_END)($transformationContext, [
      'unconsumed_slices' => &$unconsumed_slices,
      'limit' => $limit,
      'offset' => $offset,
    ]);

    return $unconsumed_slices;
  }

  function update(string $snapshot_id, array $data): array|bool
  {
    $imports = \get_option(self::WP_OPTION_IMPORTS, []);
    foreach ($imports as &$import) {
      if ($import['id'] === $snapshot_id) {
        foreach ($data as $key => $value) {
          // prevent updating 'id', 'options', 'profile', 'user', 'created'
          if (!in_array($key, ['id', 'options', 'profile', 'user', 'created'])) {
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

  function remove(string $snapshot_id): bool|array
  {
    $imports = \get_option(self::WP_OPTION_IMPORTS, []);
    foreach ($imports as $index => $import) {
      if ($import['id'] === $snapshot_id) {
        $transformationContext = ImpexImportTransformationContext::fromJson($import);

        global $wpdb;
        global $wp_filesystem;

        \WP_Filesystem();

        // remove matching export table rows
        $rowsDeleted = $wpdb->delete($this->_db_chunks_tablename, ['snapshot_id' => $snapshot_id,]);
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
