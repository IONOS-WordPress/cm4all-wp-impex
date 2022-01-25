<?php

namespace cm4all\wp\impex\tests\phpunit;

use cm4all\wp\impex\Impex;
use cm4all\wp\impex\WpOptionsExporter;

use function cm4all\wp\impex\__registerWpOptionsExportProvider;

class TestImpexExportExtensionWpOptions extends ImpexUnitTestcase
{
  function testWpOptionsExporterProvider(): void
  {
    $Export = Impex::getInstance()->Export;

    $this->assertEmpty(iterator_to_array($Export->getProviders()), 'there should be no export providers registered');

    $provider = __registerWpOptionsExportProvider();

    $exportProviders = iterator_to_array($Export->getProviders());
    $this->assertEquals(1, count($exportProviders), 'there should be exactly one export provider registered');

    $this->assertEquals($provider, $exportProviders[0], WpOptionsExporter::PROVIDER_NAME . ' should be the registered provider');

    $this->assertEquals($provider->name, $exportProviders[0]->name, WpOptionsExporter::PROVIDER_NAME . ' should be the name of the provider');
  }

  function testWpOptionsExporter(): void
  {


    $provider = __registerWpOptionsExportProvider();
    $exportGenerator = call_user_func($provider->callback, [
      WpOptionsExporter::OPTION_SELECTOR => 'siteurl',
    ], $this->createImpexExportTransformationContextMock(),);
    $this->assertInstanceOf(\Generator::class, $exportGenerator, 'callback result should be a Generator');

    // test returned slice
    $slices = iterator_to_array(call_user_func($provider->callback, [
      WpOptionsExporter::OPTION_SELECTOR => 'siteurl',
    ], $this->createImpexExportTransformationContextMock(),));
    $this->assertCount(1, $slices, 'a single slice should be exported');
    $slice = $slices[0];
    $this->assertEquals(WpOptionsExporter::SLICE_TAG, $slice[Impex::SLICE_TAG]);
    $this->assertEquals(WpOptionsExporter::SLICE_META_ENTITY_WP_OPTIONS, $slice[Impex::SLICE_META][Impex::SLICE_META_ENTITY]);
    $this->assertSame(['siteurl' => get_option('siteurl')], $slice[Impex::SLICE_DATA]);

    // test no slice returned if no wp-option matched
    $slices = iterator_to_array(call_user_func($provider->callback, [
      WpOptionsExporter::OPTION_SELECTOR => 'not-existing-option',
    ], $this->createImpexExportTransformationContextMock(),));
    $this->assertCount(0, $slices, 'no slice should be exported');

    // test return multiple wp-options
    $slices = iterator_to_array(call_user_func($provider->callback, [
      WpOptionsExporter::OPTION_SELECTOR => ['siteurl', 'site_icon'],
    ], $this->createImpexExportTransformationContextMock(),));
    $this->assertCount(1, $slices, 'one slice should be exported');
    $this->assertSame(['siteurl' => get_option('siteurl'), 'site_icon' => get_option('site_icon')], $slices[0][Impex::SLICE_DATA]);

    // test return wp-options in multiple slices
    $slices = iterator_to_array(call_user_func($provider->callback, [
      WpOptionsExporter::OPTION_SELECTOR => ['siteurl', 'site_icon'],
      WpOptionsExporter::OPTION_SLICE_MAX_ITEMS => 1,
    ], $this->createImpexExportTransformationContextMock(),));
    $this->assertCount(2, $slices, '2 slices should be exported');
    $this->assertCount(1, $slices[0][Impex::SLICE_DATA], 'first slice should contain a single wp-option');
    $this->assertCount(1, $slices[1][Impex::SLICE_DATA], 'second slice should contain a single wp-option');
    $this->assertSame(['siteurl' => get_option('siteurl'), 'site_icon' => get_option('site_icon')], array_merge($slices[0][Impex::SLICE_DATA], $slices[1][Impex::SLICE_DATA]));

    // test return multiple wp-options
    $slices = iterator_to_array(call_user_func($provider->callback, [
      WpOptionsExporter::OPTION_SELECTOR => ['site*'],
    ], $this->createImpexExportTransformationContextMock(),));
    $this->assertCount(1, $slices, 'one slice should be exported');
    $this->assertSame(['siteurl' => get_option('siteurl'), 'site_icon' => get_option('site_icon')], $slices[0][Impex::SLICE_DATA]);
  }
}
