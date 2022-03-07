<?php

namespace cm4all\wp\impex;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit();
}

require_once __DIR__ . '/class-impex-part.php';
require_once __DIR__ . '/class-impex-transformation-context.php';
require_once __DIR__ . '/class-impex-export-provider.php';
require_once __DIR__ . '/class-impex-export-profile.php';
require_once __DIR__ . '/class-impex-export-runtime-exception.php';

abstract class ImpexExport extends ImpexPart
{
  const WP_OPTION_EXPORTS = 'impex_exports';
  const WP_FILTER_SLICE_SERIALIZE = 'impex_export_filter_serialize';
  const WP_FILTER_SLICE_DESERIALIZE = 'impex_export_filter_deserialize';

  protected function _createProvider(string $name, callable $cb): ImpexExportProvider
  {
    return new class($name, $cb) extends ImpexExportProvider
    {
      public function __construct($name, $cb)
      {
        parent::__construct($name, $cb);
      }
    };
  }

  protected function _createProfile(string $name, ImpexPart $context): ImpexExportProfile
  {
    return new class($name, $context) extends ImpexExportProfile
    {
      public function __construct($name, $context)
      {
        parent::__construct($name, $context);
      }
    };
  }

  function extract(ImpexExportTransformationContext $transformationContext): \Generator
  {
    foreach ($transformationContext->profile->getTasks() as $task) {
      if ($task->disabled) {
        continue;
      }

      $_options = self::_computeOptions($task, $transformationContext->options);

      foreach (call_user_func($task->provider->callback, $_options, $transformationContext) as $slice) {
        // ensure Impex::SLICE_TYPE is set
        $slice[Impex::SLICE_TYPE] ??= Impex::SLICE_TYPE_PHP;
        $slice[Impex::SLICE_DATE] ??= gmdate('Y-m-d H:i:s +0000');

        yield $slice;
      }
    }
  }

  function save(ImpexExportProfile $profile, array $options = [],  string $name = '', string $description = ''): ImpexExportTransformationContext
  {
    $transformationContext = new ImpexExportTransformationContext(
      profile_name: $profile->name,
      name: $name,
      description: $description,
      options: $options,
    );

    global $wpdb;
    foreach ($this->extract($transformationContext) as $position => $slice) {
      $slice = \apply_filters(self::WP_FILTER_SLICE_SERIALIZE, $slice, $transformationContext);

      $json = json_encode($slice);
      if ($json === false) {
        throw new ImpexExportRuntimeException(sprintf('failed to encode slice to json : %s(=%s)', json_last_error(), json_last_error_msg()));
      }

      $success = $wpdb->insert(
        $this->_db_chunks_tablename,
        [
          'position' => $position,
          'snapshot_id' => $transformationContext->id,
          'slice' => $json,
        ]
      );

      if ($success === false) {
        throw new ImpexExportRuntimeException(sprintf('failed to insert jsonized slice(=%s) to database : %s', $json, $wpdb->last_error));
      }
    }

    $exports = \get_option(self::WP_OPTION_EXPORTS, []);

    $exports[] = $transformationContext->jsonSerialize();

    \update_option(self::WP_OPTION_EXPORTS, $exports);

    return $transformationContext;
  }

  function update(string $snapshot_id, array $data): array|bool
  {
    $exports = \get_option(self::WP_OPTION_EXPORTS, []);
    foreach ($exports as &$export) {
      if ($export['id'] === $snapshot_id) {
        foreach ($data as $key => $value) {
          // prevent updating 'id', 'options', 'profile', 'user', 'created'
          if (!in_array($key, ['id', 'options', 'profile', 'user', 'created'])) {
            if ($value === null) {
              unset($export[$key]);
            } else {
              $export[$key] = $value;
            }
          }
        }

        \update_option(self::WP_OPTION_EXPORTS, $exports);

        return $export;
      }
    };

    return false;
  }

  function remove(string $snapshot_id): bool|array
  {
    /* @var array<array> */
    $exports = \get_option(self::WP_OPTION_EXPORTS, []);
    foreach ($exports as $index => $export) {
      if ($export['id'] === $snapshot_id) {
        $transformationContext = ImpexExportTransformationContext::fromJson($export);

        global $wpdb;
        global $wp_filesystem;

        \WP_Filesystem();

        // remove matching export table rows
        $rowsDeleted = $wpdb->delete($this->_db_chunks_tablename, ['snapshot_id' => $transformationContext->id,]);
        if ($rowsDeleted === false) {
          throw new ImpexExportRuntimeException(sprintf('failed to delete jsonized slices from database : %s', $wpdb->last_error));
        }

        // remove export specific uploads directory
        if ($wp_filesystem->exists($transformationContext->path)) {
          $wp_filesystem->rmdir($transformationContext->path, true);
        }

        $removedItems = array_splice($exports, $index, 1);
        \update_option(self::WP_OPTION_EXPORTS, $exports);

        return $removedItems[0];
      }
    };

    return false;
  }
}
