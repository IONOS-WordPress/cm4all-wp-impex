<?php

namespace cm4all\wp\impex\tests\phpunit;

use cm4all\wp\impex\DbTablesExporter;
use cm4all\wp\impex\Impex;

use function cm4all\wp\impex\__registerDbTablesExportProvider;

class TestImpexExportExtensionDbTable extends ImpexUnitTestcase
{
  function testDbTablesExporterProvider(): void
  {
    $Export = Impex::getInstance()->Export;

    $this->assertEmpty(iterator_to_array($Export->getProviders()), 'there should be no export providers registered');

    $provider = __registerDbTablesExportProvider();

    $exportProviders = iterator_to_array($Export->getProviders());
    $this->assertEquals(1, count($exportProviders), 'there should be exactly one export provider registered');

    $this->assertEquals($provider, $exportProviders[0], DbTablesExporter::PROVIDER_NAME . 'should be the registered provider');

    $this->assertEquals($provider->name, $exportProviders[0]->name, DbTablesExporter::PROVIDER_NAME . 'should be the name of the provider');
  }

  function testDbExporter(): void
  {
    global $wpdb;

    // create file contents using `make wp-env-mysql-export ARGS='wp_cmplz_cookiebanners wp_cmplz_cookies wp_cmplz_services'`
    \importSQL(__DIR__ . '/fixtures/adapter/db/cmplz-plugin.sql');
    // system('mysql --user=' . DB_USER . ' --password=' . DB_PASSWORD . ' ' . DB_NAME . ' < ' . __DIR__ . '/fixtures/adapter/db/cmplz-plugin.sql');

    /*
      this is a bit quirky ... wp unit tests apply a query filter to wpdb which transforms CREATE/DROP table statements
      into CREATE/DROP TEMPORARY table statements (https://wordpress.stackexchange.com/questions/220275/wordpress-unit-testing-cannot-create-tables)

      therefore tables created in wp unit tests are not visible using SHOW TABLES LIKE statements.
      that's why we "test" the existence of a table using SELECT COUNT (=> which returns null / error in case the temporary table doesnt exists)
    */
    $source_table = "{$wpdb->prefix}cmplz_cookies";
    $source_table_rowCount = $wpdb->get_var("SELECT COUNT(*) FROM $source_table");
    $this->assertNotNull($source_table_rowCount, "ensure $source_table exists");



    $provider = __registerDbTablesExportProvider();
    $exportGenerator = call_user_func($provider->callback, [
      DbTablesExporter::OPTION_SELECTOR => 'cmplz_cookies',
      // => export no data
      DbTablesExporter::OPTION_SLICE_MAX_ITEMS => 0,
    ], $this->createImpexExportTransformationContextMock(),);
    $this->assertInstanceOf(\Generator::class, $exportGenerator, 'callback result should be a Generator');

    // test returned slice
    $slices = iterator_to_array(call_user_func($provider->callback, [
      DbTablesExporter::OPTION_SELECTOR => 'cmplz_cookies',
      // => export no data
      DbTablesExporter::OPTION_SLICE_MAX_ITEMS => 0,
    ], $this->createImpexExportTransformationContextMock(),));

    // @NOTE: echo output will be swallowed by phpunit except commandline parameter --debug is provided
    $this->assertCount(1, $slices, 'a single slice should be exported');

    $chunk_max_items = 10;
    $slices = iterator_to_array(call_user_func($provider->callback, [
      DbTablesExporter::OPTION_SELECTOR => 'cmplz_cookies',
      // => export no data
      DbTablesExporter::OPTION_SLICE_MAX_ITEMS => $chunk_max_items,
    ], $this->createImpexExportTransformationContextMock(),));

    // ensure expected rows slices were yielded
    $this->assertEquals(ceil($source_table_rowCount / $chunk_max_items) + 1, count($slices), 'ceil(source_table_rowCount / $chunk_max_items)+1 slices should be exported');

    $this->assertEquals(DbTablesExporter::SLICE_META_ENTITY_TABLE, $slices[0][Impex::SLICE_META][Impex::SLICE_META_ENTITY], 'first slice should have meta/entity type rows');

    foreach (array_slice($slices, 1) as $rows_slice) {
      $this->assertEquals(DbTablesExporter::SLICE_META_ENTITY_ROWS, $rows_slice[Impex::SLICE_META][Impex::SLICE_META_ENTITY], 'following slices should have meta/entity type rows');
    }
  }

  function testDbExporterOptionSelectorWildcard(): void
  {


    $provider = __registerDbTablesExportProvider();

    // test returned slice
    $slices = iterator_to_array(
      iterator: call_user_func($provider->callback, [
        DbTablesExporter::OPTION_SELECTOR => 'comment*',
        // => export no data
        DbTablesExporter::OPTION_SLICE_MAX_ITEMS => 0,
      ], $this->createImpexExportTransformationContextMock(),),
      preserve_keys: false,
    ); // see https://www.php.net/manual/en/language.generators.syntax.php for use_keys/preserve_keys === false

    $this->assertCount(2, $slices, '2 slices (for tables "wp_comments" and "wp_commentmeta") should be exported');
  }

  function testDbExporterOptionSelectorMatchesNothing(): void
  {


    global $wpdb;

    $provider = __registerDbTablesExportProvider();

    // test yielded slices
    $slices = iterator_to_array(call_user_func($provider->callback, [
      DbTablesExporter::OPTION_SELECTOR => 'no-table-is-matching*',
      // => export no data
      DbTablesExporter::OPTION_SLICE_MAX_ITEMS => 0,
    ], $this->createImpexExportTransformationContextMock(),), false); // see https://www.php.net/manual/en/language.generators.syntax.php for second parameter === false

    $this->assertEmpty($slices, 'generators can yield nothing');
  }
}
