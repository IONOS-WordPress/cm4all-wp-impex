<?php

namespace cm4all\wp\impex\tests\phpunit;

use cm4all\wp\impex\AttachmentsExporter;
use cm4all\wp\impex\Impex;

use function cm4all\wp\impex\__registerAttachmentsExportProvider;

class TestImpexExportExtensionAttachments extends ImpexUnitTestcase
{
  function tearDown()
  {
    parent::tearDown();

    // in case of broken phpunit calls old uploads may exist within $ignored_files (populated in setUp)
    // to ensure these will be cleaned up properly we force to forget about these intermediate file uploads
    self::$ignore_files = [];

    // remove all uploads
    $this->remove_added_uploads();
  }

  function testAttachmentsExporter(): void
  {
    $this->assertEmpty(iterator_to_array(Impex::getInstance()->Export->getProviders()), 'there should be no export providers registered');

    $provider = __registerAttachmentsExportProvider();

    $exportProviders = iterator_to_array(Impex::getInstance()->Export->getProviders());
    $this->assertEquals(1, count($exportProviders), 'there should be exactly one export provider registered');

    $this->assertEquals($provider, $exportProviders[0], AttachmentsExporter::PROVIDER_NAME . ' should be the registered provider');

    $this->assertEquals($provider->name, $exportProviders[0]->name, AttachmentsExporter::PROVIDER_NAME . ' should be the name of the provider');

    $upload_names = ['angeln.jpg', 'wood.jpg', 'zdf-hitparade.jpg'];

    $created_attachments_ids = array_map(function (string $upload_name) {
      $post = $this->factory->post->create_and_get();
      return $this->factory->attachment->create_upload_object(__DIR__ . '/fixtures/uploads/images/' . $upload_name, $post->ID);
    }, $upload_names);

    $attachments = \get_posts(['post_type' => 'attachment', 'numberposts' => -1, 'post_status' => null, 'post_parent' => null]);
    $this->assertEquals(count($created_attachments_ids), count($attachments), 'count of attachments matches count of uploaded files');

    $this->assertSameSets($upload_names, \wp_list_pluck($attachments, 'post_title'), 'upload file names match created attachment titles');

    $exportGenerator = call_user_func($provider->callback, [], $this->createImpexExportTransformationContextMock(),);
    $this->assertInstanceOf(\Generator::class, $exportGenerator, 'callback result should be a Generator');

    // test returned slice
    $slices = iterator_to_array(call_user_func($provider->callback, [], $this->createImpexExportTransformationContextMock(),));
    $this->assertEquals(count($upload_names), count($slices), 'there should be exactly the same amount of slices as attachments');

    // ensure generated slice data tag is declared as resource
    $this->assertSameSets(array_fill(0, count($slices), AttachmentsExporter::SLICE_TAG), \wp_list_pluck($slices, Impex::SLICE_TAG), 'attachment tag should be attachment slice tag');

    // ensure generated slice data type is declared as resource
    $this->assertSameSets(array_fill(0, count($slices), Impex::SLICE_TYPE_RESOURCE), \wp_list_pluck($slices, Impex::SLICE_TYPE), 'attachment type should be resource');

    /*
    // ensure generated slices data is a true php ressource
    $this->assertSameSets(array_fill(0, count($slices), ['is_resource' => true, 'resource_type' => 'stream']), array_map(function ($slice) {
      $slice_data = $slice[Impex::SLICE_DATA];
      return [
        'is_resource' => \is_resource($slice_data),
        'resource_type' => \get_resource_type($slice_data)
      ];
    }, $slices), 'attachment type should be resource');
    */

    // ensure generated slices match uploaded files
    $this->assertSameSets($upload_names, array_map(function ($slice) {
      return $slice[Impex::SLICE_META]['data']['post_title'];
    }, $slices), 'upload file names match created attachment titles');
  }
}
