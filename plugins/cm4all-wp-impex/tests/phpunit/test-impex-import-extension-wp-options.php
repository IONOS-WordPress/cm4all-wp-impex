<?php

namespace cm4all\wp\impex\tests\phpunit;

use cm4all\wp\impex\Impex;
use cm4all\wp\impex\ImpexExport;
use cm4all\wp\impex\ImpexExportProfile;
use cm4all\wp\impex\ImpexExportProvider;
use cm4all\wp\impex\ImpexExportTransformationContext;
use cm4all\wp\impex\ImpexImportTransformationContext;
use cm4all\wp\impex\ImpexPart;
use cm4all\wp\impex\ImpexRuntimeException;
use cm4all\wp\impex\WordpressExporter;
use cm4all\wp\impex\WpOptionsExporter;
use cm4all\wp\impex\WpOptionsImporter;

use function cm4all\wp\impex\__registerWpOptionsExportProvider;
use function cm4all\wp\impex\__registerWpOptionsImportProvider;

class TestImpexImportExtensionWpOptions extends ImpexUnitTestcase
{
  function testWpOptionsImportProvider(): void
  {
    $Import = Impex::getInstance()->Import;

    $this->assertEmpty(iterator_to_array($Import->getProviders()), 'there should be no import providers registered');

    $provider = __registerWpOptionsImportProvider();

    $importProviders = iterator_to_array($Import->getProviders());
    $this->assertEquals(1, count($importProviders), 'there should be exactly one import provider registered');

    $this->assertEquals($provider, $importProviders[0], WpOptionsImporter::PROVIDER_NAME . ' should be the registered provider');

    $this->assertEquals($provider->name, $importProviders[0]->name, WpOptionsImporter::PROVIDER_NAME . ' should be the name of the provider');
  }

  function testWpOptionsImport(): void
  {
    \wp_set_current_user(self::factory()->user->create(['role' => 'administrator', 'user_login' => 'me']));

    $exportProvider = __registerWpOptionsExportProvider();
    $importProvider = __registerWpOptionsImportProvider();

    // export slices
    $slices = iterator_to_array(call_user_func(
      $exportProvider->callback,
      [
        WpOptionsExporter::OPTION_SELECTOR => 'site*',
      ],
      new ImpexExportTransformationContext(Impex::getInstance()->Export->addProfile('foo')->name),
    ));

    $this->assertCount(1, $slices, 'one slice should have been exported');
    $this->assertCount(2, $slices[0][Impex::SLICE_DATA], '2 wp option settings should have been exported');

    // change exported wp option settings within wordpress to something different to check if the later on import had an effect
    foreach ($slices[0][Impex::SLICE_DATA] as $wpOptionName => $wpOptionValue) {
      update_option($wpOptionName, 'xxx');
    }

    // import exported wp options
    $this->assertTrue((call_user_func(
      $importProvider->callback,
      $slices[0],
      [],
      new ImpexImportTransformationContext(Impex::getInstance()->Import->addProfile('foo')->name),
    )), 'WpOptions Import should accept slice yielded by WpOptions Export');

    $this->assertSame($slices[0][Impex::SLICE_DATA], ['siteurl' => get_option('siteurl'), 'site_icon' => get_option('site_icon')]);
  }

  /**
   * @dataProvider complexValueImportExportProvider
   */
  function testComplexValueImportExport($ARRAY_OPTION_NAME, $EXPECTED_ARRAY_OPTION_VALUE, $WP_OPTIONS_EXPORTER_OPTION_SELECTOR): void
  {
    \wp_set_current_user(self::factory()->user->create(['role' => 'administrator', 'user_login' => 'me']));

    $exportProvider = __registerWpOptionsExportProvider();
    $importProvider = __registerWpOptionsImportProvider();

    \add_option($ARRAY_OPTION_NAME, $EXPECTED_ARRAY_OPTION_VALUE);

    // export slices
    $slices = iterator_to_array(call_user_func(
      $exportProvider->callback,
      [
        WpOptionsExporter::OPTION_SELECTOR => $WP_OPTIONS_EXPORTER_OPTION_SELECTOR,
      ],
      new ImpexExportTransformationContext(Impex::getInstance()->Export->addProfile('foo')->name),
    ));

    $this->assertCount(1, $slices, 'one slice should have been exported');
    $this->assertCount(1, $slices[0][Impex::SLICE_DATA], '1 wp option setting should have been exported');

    \delete_option($ARRAY_OPTION_NAME);

    $this->assertFalse(\get_option($ARRAY_OPTION_NAME), 'option should not exist anymore');

    // import exported wp options
    $this->assertTrue((call_user_func(
      $importProvider->callback,
      $slices[0],
      [],
      new ImpexImportTransformationContext(Impex::getInstance()->Import->addProfile('foo')->name),
    )), 'WpOptions Import should accept slice yielded by WpOptions Export');

    $this->assertEqualsCanonicalizing($EXPECTED_ARRAY_OPTION_VALUE, \get_option($ARRAY_OPTION_NAME));
  }

  function complexValueImportExportProvider(): array
  {
    return [
      'wp option with complex array value'  => ['xxx-array-option', ['a' => 'b', 'c' => ['d' => 'e']], 'xxx-array*'],
      'wp option with object value'  => ['xxx-object-option', (object)['a' => 'b', 'c' => 'd'], 'xxx-object*'],
      'wp option with complex object value'  => ['xxx-complex-object-option', (object)['a' => 'b', 'c' => (object)['d' => 'e']], 'xxx-complex-object*'],
    ];
  }
}
