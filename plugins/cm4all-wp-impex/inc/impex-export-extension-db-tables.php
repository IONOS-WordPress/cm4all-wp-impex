<?php

/**
 * caveat : wildcard selectors will work only for regular (non-temporary) tables. 
 * you can workaround this by providing the explizit table/view names instead of a wildcard
 */

namespace cm4all\wp\impex;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit();
}

require_once ABSPATH . '/wp-admin/includes/export.php';

require_once __DIR__ . '/interface-impex-named-item.php';
require_once __DIR__ . '/class-impex.php';

use cm4all\wp\impex\Impex;

function __DbTablesExportProviderCallback(array $options, ImpexExportTransformationContext $transformationContext): \Generator
{
  global $wpdb;
  static $db_table_names = null;

  $selectors = $options[DbTablesExporter::OPTION_SELECTOR] ?? null;

  // ensure selector is valid
  if (!(is_array($selectors) || is_string($selectors))) {
    throw new ImpexExportRuntimeException(sprintf('dont know how to handle export option DbTablesExporter::OPTION_SELECTOR(=%s)', json_encode($selectors)));
  }

  // normalize selector to type array
  if (is_string($selectors)) {
    $selectors = [$selectors];
  }

  foreach ($selectors as $selector) {
    if (str_contains($selector, '[') || str_contains($selector, '*') || str_contains($selector, '?')) {
      $db_table_names ??= $wpdb->get_col('SHOW TABLES') ?? [];

      foreach ($db_table_names as $db_table_name) {
        if (fnmatch($wpdb->prefix . $selector, $db_table_name)) {
          yield from __dbTableProvider(array_merge($options, [DbTablesExporter::OPTION_SELECTOR => substr($db_table_name, strlen($wpdb->prefix))]));
        }
      }
    } else {
      yield from __dbTableProvider(array_merge($options, [DbTablesExporter::OPTION_SELECTOR => $selector]));
    }
  }
}

function __dbTableProvider(array $options): \Generator
{
  $table = $options[DbTablesExporter::OPTION_SELECTOR];

  global $wpdb;

  // see https://stackoverflow.com/questions/4294507/how-to-dump-mysql-table-structure-without-data-with-a-sql-query/12448816
  $table_ddl = $wpdb->get_var("SHOW CREATE TABLE {$wpdb->prefix}$table", 1,);
  // normalize table name
  $table_ddl = str_replace($wpdb->prefix, '%prefix%', $table_ddl,);
  // inject table create failover 
  $table_ddl = str_replace('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS', $table_ddl,);

  // yield tabel ddl chunk
  yield [
    Impex::SLICE_TAG => DbTablesExporter::SLICE_TAG,
    Impex::SLICE_VERSION => DbTablesExporter::VERSION,
    Impex::SLICE_META => [
      'name' => $table,
      'entity' => DbTablesExporter::SLICE_META_ENTITY_TABLE,
      'options' => $options,
    ],
    Impex::SLICE_DATA => $table_ddl,
  ];

  // yield a slice for each db rows chunk
  $chunk_max_items = $options[DbTablesExporter::OPTION_SLICE_MAX_ITEMS] ?? DbTablesExporter::OPTION_SLICE_MAX_ITEMS_DEFAULT;
  if ($chunk_max_items > 0) {
    $rows = $wpdb->get_results("SELECT * from {$wpdb->prefix}$table");
    $chunk_count = ceil(count($rows) / $chunk_max_items);

    for ($chunk = 0; $chunk < $chunk_count; $chunk++) {
      // index of first item in this chunk
      $chunk_ofs = $chunk * $chunk_max_items;
      // amount of items to yield in this chunk
      $chunk_item_count = min($chunk_max_items, count($rows) - $chunk_ofs);

      yield [
        Impex::SLICE_TAG => DbTablesExporter::SLICE_TAG,
        Impex::SLICE_VERSION => DbTablesExporter::VERSION,
        Impex::SLICE_META => [
          'name' => $table,
          'entity' => DbTablesExporter::SLICE_META_ENTITY_ROWS,
        ],
        Impex::SLICE_DATA => array_slice($rows, $chunk_ofs, $chunk_item_count)
      ];
    }
  }
}

/**
 * @TODO: convert to enum if enums once are available in PHP
 */
interface DbTablesExporter
{
  const SLICE_TAG = 'db-table';
  const SLICE_META_ENTITY_TABLE = 'db-table-entity-table';
  const SLICE_META_ENTITY_ROWS = 'db-table-entity-rows';

  const OPTION_SELECTOR = 'db-tables-export-option-selector';

  const OPTION_SLICE_MAX_ITEMS = 'db-tables-export-option-chunk-max-items';
  const OPTION_SLICE_MAX_ITEMS_DEFAULT = 50;

  const PROVIDER_NAME = self::class;

  const VERSION = '1.0.0';
}

function __registerDbTablesExportProvider()
{
  $provider = Impex::getInstance()->Export->addProvider(DbTablesExporter::PROVIDER_NAME, __NAMESPACE__ . '\__DbTablesExportProviderCallback');
  return $provider;
}

\add_action(
  hook_name: Impex::WP_ACTION_REGISTER_PROVIDERS,
  callback: __NAMESPACE__ . '\__registerDbTablesExportProvider',
);
