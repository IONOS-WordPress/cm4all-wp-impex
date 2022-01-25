<?php

namespace cm4all\wp\impex\tests\phpunit;

use cm4all\wp\impex\Impex;
use cm4all\wp\impex\ContentExporter;

use function cm4all\wp\impex\__registerContentExportProvider;

class TestImpexExportExtensionContent extends ImpexUnitTestcase
{
  function testWordpressExportExporter(): void
  {
    $Export = Impex::getInstance()->Export;

    $this->assertEmpty(iterator_to_array($Export->getProviders()), 'there should be no export providers registered');

    $provider = __registerContentExportProvider();

    $exportProviders = iterator_to_array($Export->getProviders());
    $this->assertEquals(1, count($exportProviders), 'there should be exactly one export provider registered');

    $this->assertEquals($provider, $exportProviders[0], ContentExporter::PROVIDER_NAME . ' should be the registered provider');

    $this->assertEquals($provider->name, $exportProviders[0]->name, ContentExporter::PROVIDER_NAME . ' should be the name of the provider');

    $user_id = self::factory()->user->create(array('role' => 'admin'));
    \wp_set_current_user($user_id);

    // create a post
    // see https://wpdevelopment.courses/articles/wp-unittest-factory-documentation/#h-posts
    $post_id = self::factory()->post->create();

    // test generator instance returned
    $exportGenerator = call_user_func($provider->callback, [], $this->createImpexExportTransformationContextMock(),);
    $this->assertInstanceOf(\Generator::class, $exportGenerator, 'callback result should be a Generator');


    // test returned slice
    $slices = iterator_to_array($exportGenerator);

    $this->assertEquals(1, count($slices), 'there should be exactly one generated slice');

    $this->assertEquals(ContentExporter::SLICE_TAG, $slices[0][Impex::SLICE_TAG], "slice meta entity should be '{constant(ContentExporter::SLICE_META_ENTITY_WXR)}'");

    $meta = $slices[0][Impex::SLICE_META];
    $this->assertEquals(ContentExporter::SLICE_META_ENTITY_CONTENT, $meta[Impex::SLICE_META_ENTITY], "slice meta entity should be 'constant(ContentExporter::SLICE_META_ENTITY)'");

    $data = $slices[0][Impex::SLICE_DATA];
    $this->assertArrayHasKey('posts', $data);
    $this->assertIsArray($data['posts']);
    $this->assertCount(1, $data['posts']);
    $this->assertArrayHasKey('authors', $data);
    $this->assertIsArray($data['authors']);
    $this->assertCount(1, $data['authors']);
    $this->assertArrayHasKey('terms', $data);
    $this->assertIsArray($data['terms']);
    $this->assertArrayHasKey('tags', $data);
    $this->assertIsArray($data['tags']);
    $this->assertArrayHasKey('categories', $data);
    $this->assertIsArray($data['categories']);
  }
}
