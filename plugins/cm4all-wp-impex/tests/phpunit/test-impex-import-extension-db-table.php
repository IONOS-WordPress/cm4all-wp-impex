<?php

namespace cm4all\wp\impex\tests\phpunit;

use cm4all\wp\impex\DbTablesExporter;
use cm4all\wp\impex\DbTableImporter;
use cm4all\wp\impex\Impex;
use cm4all\wp\impex\ImpexExportTransformationContext;
use cm4all\wp\impex\ImpexImportTransformationContext;

use function cm4all\wp\impex\__registerDbTablesExportProvider;
use function cm4all\wp\impex\__registerDbTableImportProvider;

class TestImpexImportExtensionDbTable extends ImpexUnitTestcase
{
  function setUp()
  {
    parent::setUp();

    // create file contents using `make wp-env-mysql-export ARGS='wp_cmplz_cookiebanners wp_cmplz_cookies wp_cmplz_services'`
    \importSQL(__DIR__ . '/fixtures/adapter/db/cmplz-plugin.sql');
    // system('mysql --user=' . DB_USER . ' --password=' . DB_PASSWORD . ' ' . DB_NAME . ' < ' . __DIR__ . '/fixtures/adapter/db/cmplz-plugin.sql');
  }

  function tearDown()
  {
    parent::tearDown();

    // drop tables created by '/fixtures/adapter/db/cmplz-plugin.sql'
    global $wpdb;

    $table_names = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}cmplz\_%'");
    if ($table_names !== false) {
      foreach ($table_names as $table_name) {
        $wpdb->query("DROP TABLE $table_name");
      }
    }
  }

  function testDbTableImporterProvider(): void
  {
    $Import = Impex::getInstance()->Import;

    $this->assertEmpty(iterator_to_array($Import->getProviders()), 'there should be no import providers registered');

    $provider = __registerDbTableImportProvider();

    $importProviders = iterator_to_array($Import->getProviders());
    $this->assertEquals(1, count($importProviders), 'there should be exactly one import provider registered');

    $this->assertEquals($provider, $importProviders[0], DbTableImporter::PROVIDER_NAME . ' should be the registered provider');

    $this->assertEquals($provider->name, $importProviders[0]->name, DbTableImporter::PROVIDER_NAME . 'should be the name of the provider');
  }

  function testDbImporter(): void
  {
    global $wpdb;

    $exportProvider = __registerDbTablesExportProvider();
    $importProvider = __registerDbTableImportProvider();

    \wp_set_current_user(self::factory()->user->create(['role' => 'administrator', 'user_login' => 'me']));

    // export slices
    $slices = iterator_to_array(call_user_func(
      $exportProvider->callback,
      [
        DbTablesExporter::OPTION_SELECTOR => 'cmplz_cookies',
        DbTablesExporter::OPTION_SLICE_MAX_ITEMS => DbTablesExporter::OPTION_SLICE_MAX_ITEMS_DEFAULT,
      ],
      new ImpexExportTransformationContext(Impex::getInstance()->Export->addProfile('foo')->name),
    ));

    $table_rows_beforeImport = $wpdb->get_results("SELECT * from {$wpdb->prefix}cmplz_cookies");

    // reset name to '' to be able to detect if our import has overwritten the current data
    $wpdb->query("UPDATE {$wpdb->prefix}cmplz_cookies set name=''");

    // import exported data
    $importProfile = Impex::getInstance()->Import->addProfile('foo');
    foreach ($slices as $slice) {
      $this->assertTrue((call_user_func(
        $importProvider->callback,
        $slice,
        [],
        new ImpexImportTransformationContext($importProfile->name),
      )), DbTableImporter::PROVIDER_NAME . ' should be accept all slices yielded by DbExporter');
    }

    $table_rows_afterImport = $wpdb->get_results("SELECT * from {$wpdb->prefix}cmplz_cookies");

    $this->assertEqualSets($table_rows_beforeImport, $table_rows_afterImport, 'ensure exported table rows match imported table rows');
  }

  function testImport_OPTION_COPY_DATA_FROM_TABLE_WITH_PREFIX(): void
  {
    global $wpdb;

    // drop (previously created by this function) table if exists
    $wpdb->query("DROP TABLE IF EXISTS live_cmplz_cookies");

    // register importer/exporter
    $exportProvider = __registerDbTablesExportProvider();
    $importProvider = __registerDbTableImportProvider();

    \wp_set_current_user(self::factory()->user->create(['role' => 'administrator', 'user_login' => 'you']));

    // export slices
    $slices = iterator_to_array(call_user_func(
      $exportProvider->callback,
      [
        DbTablesExporter::OPTION_SELECTOR => 'cmplz_cookies',
        // dont export table row data
        DbTablesExporter::OPTION_SLICE_MAX_ITEMS => 0,
      ],
      new ImpexExportTransformationContext(Impex::getInstance()->Export->addProfile('foo')->name),
    ));

    $this->assertCount(1, $slices, 'one slice (just the table slice but no table row data) should be exported');

    $slice = $slices[0];

    $IMPORTER_OPTIONS = [
      // Impex::OPTION_LOG => function(string $message, mixed $context = null) { ... }
      DbTableImporter::OPTION_COPY_DATA_FROM_TABLE_WITH_PREFIX => $wpdb->prefix,
      DbTableImporter::OPTION_SELECTORPREFIX => 'live_',
    ];

    $target_table = "{$IMPORTER_OPTIONS[DbTableImporter::OPTION_SELECTORPREFIX]}cmplz_cookies";
    $suppress_errors = $wpdb->suppress_errors();
    $this->assertNull($wpdb->get_var("SELECT COUNT(*) FROM $target_table"), "ensure $target_table does'nt exist");
    $wpdb->suppress_errors($suppress_errors);

    $this->assertTrue((call_user_func(
      $importProvider->callback,
      $slice,
      $IMPORTER_OPTIONS,
      new ImpexImportTransformationContext(Impex::getInstance()->Import->addProfile('foo')->name),
    )), DbTableImporter::PROVIDER_NAME . ' should accept slice yielded by DbExporter');

    // @NOTE: echo output will be swallowed by phpunit except commandline parameter --debug is provided
    $source_table = "{$wpdb->prefix}cmplz_cookies";

    /*
      this is a bit quirky ... wp unit tests apply a query filter to wpdb which transforms CREATE/DROP table statements
      into CREATE/DROP TEMPORARY table statements (https://wordpress.stackexchange.com/questions/220275/wordpress-unit-testing-cannot-create-tables)

      therefore tables created in wp unit tests are not visible using SHOW TABLES LIKE statements.
      that's why we "test" the existence of a table using SELECT COUNT (=> which returns null / error in case the temporary table doesnt exists)
    */
    $this->assertNotNull($wpdb->get_var("SELECT COUNT(*) FROM $source_table"), "ensure $source_table exists");

    $this->assertNotNull($wpdb->get_var("SELECT COUNT(*) FROM $target_table"), "ensure $target_table exists");
    $this->assertEquals(
      $wpdb->get_var("SELECT COUNT(*) FROM $source_table"),
      $wpdb->get_var("SELECT COUNT(*) FROM $target_table"),
      "ensure $target_table contains same amount of rows as source table"
    );

    /*
    foreach ($impex->export($validationResult, $exportOptions) as $slice) {
      echo json_encode($slice, JSON_PRETTY_PRINT);
    }
    */
  }
}
