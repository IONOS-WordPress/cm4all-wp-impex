<?php

namespace cm4all\wp\impex;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit();
}

require_once ABSPATH . '/wp-admin/includes/import.php';

require_once __DIR__ . '/interface-impex-named-item.php';
require_once __DIR__ . '/class-impex.php';

use cm4all\wp\impex\Impex;

function __DbTableImportProviderCallback(array $slice, array $options, ImpexImportTransformationContext $transformationContext): bool
{
  global $wpdb;

  if ($slice[Impex::SLICE_TAG] === DbTablesExporter::SLICE_TAG) {
    if ($slice[Impex::SLICE_VERSION] !== DbTablesExporter::VERSION) {
      throw new ImpexImportRuntimeException(sprintf('Dont know how to import slice(tag="%s", version="%s") : unsupported version. current version is "%s"', DbTablesExporter::SLICE_TAG, $slice[Impex::SLICE_VERSION], DbTablesExporter::VERSION));
    }

    // @TODO: assume same database server/connection for now
    $target_wpdb = $wpdb;

    $option_db_tableprefix = $options[DbTableImporter::OPTION_SELECTORPREFIX] ?? $wpdb->prefix;

    /**
     * @TODO: how to handle report
     * @var callable $log
     */
    $log = function (string $message, mixed $context = null) use ($options) {
      if (isset($options[Impex::OPTION_LOG])) {
        call_user_func($options[Impex::OPTION_LOG], $message, $context);
      }
    };

    $slice_meta = $slice[Impex::SLICE_META];
    $target_table_name = $option_db_tableprefix . $slice_meta['name'];

    switch ($slice_meta[Impex::SLICE_META_ENTITY]) {
      case DbTablesExporter::SLICE_META_ENTITY_TABLE: {
          $option_db_truncate = $options[DbTableImporter::OPTION_TRUNCATE] ?? DbTableImporter::OPTION_TRUNCATE_DEFAULT;
          $option_db_overwrite_definition = $options[DbTableImporter::OPTION_OVERWRITE_DEFINITION] ?? DbTableImporter::OPTION_OVERWRITE_DEFINITION_DEFAULT;

          $ddl = $slice[Impex::SLICE_DATA];

          if ($target_wpdb->query('START TRANSACTION') !== false) {
            try {
              // @TODO: should we disable keys before and reenable them afterwards ? 'ALTER TABLE ' . $target_table_name . ' ENABLE KEYS'

              // (optional) drop table
              if ($option_db_overwrite_definition) {
                $queryRetval = $target_wpdb->query("DROP TABLE IF EXISTS $target_table_name");
                if ($queryRetval === false) {
                  throw new RollbackSignal();
                }

                $log(sprintf('%s : %s', $target_wpdb->last_query, $queryRetval));
              }

              // create table if not exists
              $queryRetval = $target_wpdb->query(strtr($ddl, ['%prefix%' => $option_db_tableprefix]));
              if ($queryRetval === false) {
                throw new RollbackSignal();
              }

              $log(sprintf('%s : %s', $target_wpdb->last_query, $queryRetval));

              // (optional) truncate data
              if (!$option_db_overwrite_definition && $option_db_truncate) {
                $queryRetval = $target_wpdb->query($target_wpdb->prepare('TRUNCATE TABLE %s', $target_table_name));
                if ($queryRetval === false) {
                  throw new RollbackSignal();
                }

                $log(sprintf('%s : %s', $target_wpdb->last_query, $queryRetval));
              }

              /* @TODO: check later if the snippet below makes sense in our case
                // we could copy the values "in place" using sql

                // use CREATE TABLE ... LIKE ... to keep keys, defaults, ...
                $ret = $target_wpdb->query(
                  'CREATE TABLE ' .
                    $task->arguments->target_table .
                    ' LIKE ' .
                    $task->arguments->source_table
                );
                */

              // should we copy data in place from table to table ?
              if (isset($options[DbTableImporter::OPTION_COPY_DATA_FROM_TABLE_WITH_PREFIX])) {
                $queryRetval = $target_wpdb->query(strtr(
                  'INSERT INTO %target_table% SELECT * FROM %source_table%',
                  [
                    '%target_table%' => $target_table_name,
                    '%source_table%' => $options[DbTableImporter::OPTION_COPY_DATA_FROM_TABLE_WITH_PREFIX] . $slice_meta['name'],
                  ]
                ));
                if ($queryRetval === false) {
                  throw new RollbackSignal();
                }

                $log(sprintf('%s : %s', $target_wpdb->last_query, $queryRetval));
              }

              $target_wpdb->query('COMMIT');

              return true;
            } catch (RollbackSignal $signal) {
              $target_wpdb->query('ROLLBACK');
              throw $signal;
            }
          }

          return false;
        }
      case DbTablesExporter::SLICE_META_ENTITY_ROWS: {
          // if data where already copied from table to table inside the database
          // we can skip proceeding the rows
          if (!isset($options[DbTableImporter::OPTION_COPY_DATA_FROM_TABLE_WITH_PREFIX])) {

            $option_db_overwrite_data = $options[DbTableImporter::OPTION_OVERWRITE_DATA] ?? DbTableImporter::OPTION_OVERWRITE_DATA_DEFAULT;

            $rows = $slice[Impex::SLICE_DATA];

            // TODO: implement optimized in-db-copy procedure when special option provided
            if (count($rows) > 0) {
              $table_column_names = implode('`, `', array_keys((array)$rows[0]));

              /*
                see https://thispointer.com/insert-into-a-mysql-table-or-update-if-exists/#three

                > REPLACE works similar to INSERT. The difference is: If the new row to be inserted has the same value
                of the PRIMARY KEY or the UNIQUE index as the existing row, in that case, the old row gets deleted first
                before inserting the new one.

                REPLACE INTO customer_data(customer_id, customer_name, customer_place) VALUES(2, "Hevika","Atlanta");
              */
              $placeholders = str_repeat('%s, ', count(array_keys((array)$rows[0])) - 1) . '%s';
              $dml = strtr('%insert_or_replace% INTO `%name%`(`%column_names%`) VALUES(%values_placeholder%)', [
                '%insert_or_replace%' => $option_db_overwrite_data ? 'REPLACE' : 'INSERT',
                '%name%' => $target_table_name,
                '%column_names%' => $table_column_names,
                '%values_placeholder%' => $placeholders,
              ]);

              foreach ($rows as $row) {
                // @TODO: consider using https://developer.wordpress.org/reference/classes/wpdb/#replace-row
                // instead of populating a sql statement manually - it seems much shorter and easier to read
                $preparedStatement = $target_wpdb->prepare($dml, (array)$row);

                $queryRetval = $target_wpdb->query($preparedStatement);
                if ($queryRetval === false) {
                  $log(sprintf('%s : %s', $target_wpdb->last_query, $queryRetval));
                  throw new RollbackSignal();
                }
              }
            }
          }
          return true;
        }
      default: {
          throw new ImpexImportRuntimeException(sprintf('dont now how to handle slice meta entity : %s', $slice_meta['entity']), $slice);
        }
    }

    return true;
  }

  return false;
}

class RollbackSignal extends ImpexImportRuntimeException
{
  function __construct($msg = "")
  {
    global $wpdb;

    if ($msg === "" && $wpdb->last_error) {
      parent::__construct("{$wpdb->last_query} : $wpdb->last_error");
    }
  }
}

interface DbTableImporter
{
  const OPTION_TRUNCATE = 'db-table-import-option-truncate';
  const OPTION_TRUNCATE_DEFAULT = true;

  const OPTION_OVERWRITE_DATA = 'db-table-import-option-overwrite_data';
  const OPTION_OVERWRITE_DATA_DEFAULT = true;

  const OPTION_COPY_DATA_FROM_TABLE_WITH_PREFIX = 'db-table-import-option-copy-data-from-table-with-prefix';

  const OPTION_OVERWRITE_DEFINITION = 'db-table-import-option-overwrite-definition';
  const OPTION_OVERWRITE_DEFINITION_DEFAULT = true;

  const OPTION_SELECTORPREFIX = 'db-table-import-option-tableprefix';

  const PROVIDER_NAME = self::class;
}

function __registerDbTableImportProvider()
{
  $provider = Impex::getInstance()->Import->addProvider(DbTableImporter::PROVIDER_NAME, __NAMESPACE__ . '\__DbTableImportProviderCallback');

  global $wpdb;
  $db_version = $wpdb->get_row('SHOW VARIABLES LIKE "version_comment"', ARRAY_A)['Value'] ?? 'MySQL';

  if (stripos($db_version, 'mariadb') !== false) {
    // we need to replace legacy utf8 encodings with just utf8 and the matching collation in create table statements when importing tables from a mysqldb export to mariadb
    // see https://tecadmin.net/resolved-unknown-collation-utf8mb4_0900_ai_ci/
    \add_filter('query', function (string $sql) {
      $sql = str_replace('utf8mb4_0900_ai_ci', 'utf8_general_ci', $sql);
      $sql = str_replace('utf8mb4', 'utf8', $sql);

      return $sql;
    });
  }

  return $provider;
}

\add_action(
  Impex::WP_ACTION_REGISTER_PROVIDERS,
  __NAMESPACE__ . '\__registerDbTableImportProvider',
);
