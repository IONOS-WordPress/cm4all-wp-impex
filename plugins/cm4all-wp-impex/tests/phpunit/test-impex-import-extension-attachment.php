<?php

namespace cm4all\wp\impex\tests\phpunit;

use cm4all\wp\impex\AttachmentImporter;
use cm4all\wp\impex\AttachmentsExporter;
use cm4all\wp\impex\Impex;
use cm4all\wp\impex\ImpexExportTransformationContext;
use cm4all\wp\impex\ImpexImportTransformationContext;

use function cm4all\wp\impex\__registerAttachmentImportProvider;

class TestImpexImportExtensionAttachment extends ImpexUnitTestcase
{
  const IMAGE = 'zdf-hitparade.jpg';

  function tearDown()
  {
    parent::tearDown();

    // in case of broken phpunit calls old uploads may exist within $ignored_files (populated in setUp)
    // to ensure these will be cleaned up properly we force to forget about these intermediate file uploads
    self::$ignore_files = [];

    // remove all uploads
    $this->remove_added_uploads();
  }

  function setUp()
  {
    parent::setUp();

    global $wpdb;
    // crude but effective: make sure there's no residual data in the main tables
    foreach (array('posts', 'postmeta', 'comments', 'terms', 'term_taxonomy', 'term_relationships', 'users', 'usermeta') as $table) {
      // phpcs:ignore WordPress.DB.PreparedSQL
      $wpdb->query("DELETE FROM {$wpdb->$table}");
    }

    /*
    * @TODO: remove when testcase is working !
    */
    // in case of broken phpunit calls old uploads may exist within $ignored_files (populated in setUp)
    // to ensure these will be cleaned up properly we force to forget about these intermediate file uploads
    self::$ignore_files = [];

    // remove all uploads
    $this->remove_added_uploads();

    // prevent wordpress creating various image derivates
    \add_filter('intermediate_image_sizes_advanced', function ($sizes, $metadata) {
      return [];
    }, 10, 2);
  }

  static function __createSlice(string $image, ImpexImportTransformationContext $transformationContext): array
  {
    // copy image to export directory as if it were produced by a real export
    copy(
      __DIR__ . '/fixtures/uploads/images/' . $image,
      $transformationContext->path . '/' .  $image,
    );

    return [
      Impex::SLICE_TAG => AttachmentsExporter::SLICE_TAG,
      Impex::SLICE_VERSION => AttachmentsExporter::VERSION,
      Impex::SLICE_TYPE => Impex::SLICE_TYPE_RESOURCE,
      Impex::SLICE_META => [
        'name' => $image,
        Impex::SLICE_META_ENTITY => AttachmentsExporter::SLICE_META_ENTITY_ATTACHMENT,
        'options' => [],
        'data' =>  [
          'ID' => 6,
          'post_author' => "0",
          'post_date' => "2021-06-11 10:36:07",
          'post_date_gmt' => "2021-06-11 10:36:07",
          'post_content' => "",
          'post_title' => $image,
          'post_excerpt' => "",
          'post_status' => "inherit",
          'comment_status' => "open",
          'ping_status' => "closed",
          'post_password' => "",
          'post_name' => $image,
          'to_ping' => "",
          'pinged' => "",
          'post_modified' => "2021-06-11 10:36:07",
          'post_modified_gmt' => "2021-06-11 10:36:07",
          'post_content_filtered' => "",
          'post_parent' => 0,
          'guid' => 'http://example.org/wp-content/uploads/2021/06/' . $image,
          'menu_order' => 0,
          'post_type' => "attachment",
          'post_mime_type' => "image/jpeg",
          'comment_count' => "0",
          'filter' => "raw",
        ],
      ],
      Impex::SLICE_DATA => $image,
    ];
  }

  function testAttachmentsImporter(): void
  {
    $this->assertEmpty(iterator_to_array(Impex::getInstance()->Import->getProviders()), 'there should be no import providers registered');

    $provider = __registerAttachmentImportProvider();

    $importProviders = iterator_to_array(Impex::getInstance()->Import->getProviders());
    $this->assertEquals(1, count($importProviders), 'there should be exactly one import provider registered');

    $this->assertEquals($provider, $importProviders[0], AttachmentImporter::PROVIDER_NAME . ' should be the registered provider');

    $this->assertEquals($provider->name, $importProviders[0]->name, AttachmentImporter::PROVIDER_NAME . ' should be the name of the provider');

    $exportTransformationContext = $this->createImpexExportTransformationContextMock();

    $importTransformationContext = ImpexImportTransformationContext::fromJson([
      'profile' => Impex::getInstance()->Import->addProfile('testAttachmentsImporter-profile')->name,
      'id'   => $exportTransformationContext->id,
      'name' => 'testAttachmentsImporter',
      'description' => '',
      'created' => $exportTransformationContext->created,
      'user' => $exportTransformationContext->user->user_login,
      'options' => [AttachmentImporter::OPTION_OVERWRITE => false],
    ]);

    $retVal = call_user_func(
      $provider->callback,
      self::__createSlice(self::IMAGE, $importTransformationContext),
      [],
      $importTransformationContext,
    );

    $this->assertTrue($retVal, 'upload import should return it was successful');

    // \list_files returns not only files but also empty folders
    $uploaded_files = \list_files(\wp_get_upload_dir()['basedir'] . '/2021/06');
    // thats why we filter out directories
    $uploaded_files = array_values(array_filter($uploaded_files, function ($path) {
      // count only files within
      return is_file($path);
    }));

    // ensure uploaded image is the only one
    $this->assertEquals(1, count($uploaded_files), 'there should be only our own uploaded file');
    $this->assertStringEndsWith('/2021/06/' . self::IMAGE, $uploaded_files[0]);

    // ensure there is only one attachment
    $attachments = \get_posts(['post_type' => 'attachment', 'numberposts' => -1, 'post_status' => null, 'post_parent' => null]);
    $this->assertCount(1, $attachments, 'only one wordpress attachment should be registered');
  }

  function testImageRenamedifAlreadyExists(): void
  {
    // we patch the upload dir to have the same subdir as our imported content
    \add_filter(
      'upload_dir',
      function ($uploads) {
        $uploads['subdir'] = '/2021/06';
        $uploads['path'] = $uploads['basedir'] . $uploads['subdir'];
        $uploads['url'] = $uploads['baseurl'] . $uploads['subdir'];
        return $uploads;
      },
    );



    // create initial attachment
    $post = $this->factory->post->create_and_get();
    $attachment_id = $this->factory->attachment->create_upload_object(__DIR__ . '/fixtures/uploads/images/' . self::IMAGE, $post->ID);

    // create a post referencing the attachment to upload (see next step)
    $page_id = $this->factory->post->create(['post_content' => '
<!-- wp:cover {"url":"http:\/\/example.org\/wp-content\/uploads\/2021\/06\/zdf-hitparade.jpg","id":5,"cm4allBlockId":"35b8b3b5-6b53-455f-ad18-a01cac854a52"} -->
<div class="wp-block-cover has-background-dim">
  <img class="wp-block-cover__image-background wp-image-5" alt="" src="http://example.org/wp-content/uploads/2021/06/zdf-hitparade.jpg" data-object-fit="cover"/>
  <div class="wp-block-cover__inner-container">
    <!-- wp:paragraph {"align":"center","placeholder":"Write title\u2026","textColor":"white","className":"","fontSize":"large","cm4allBlockId":"4ca836d4-9163-400b-a306-cb7c6973c667"} -->
      <p class="has-text-align-center has-white-color has-text-color has-large-font-size">WOW</p>
    <!--/wp:paragraph -->
  </div>
</div>
<!-- /wp:cover -->

<!-- wp:image {"id":6,"sizeSlug":"large","linkDestination":"none","cm4allBlockId":"eb607000-4198-4f14-ac26-c163e974ca5c"} -->
  <figure class="wp-block-image size-large"><img src="http://example.org/wp-content/uploads/2021/06/zdf-hitparade.jpg" alt="" class="wp-image-6"/></figure>
<!-- /wp:image -->
    ']);

    $provider = __registerAttachmentImportProvider();

    $exportTransformationContext = $this->createImpexExportTransformationContextMock();

    $importTransformationContext = ImpexImportTransformationContext::fromJson([
      'profile' => Impex::getInstance()->Import->addProfile('testAttachmentsImporter-profile')->name,
      'id'   => $exportTransformationContext->id,
      'name' => 'testAttachmentsImporter',
      'description' => '',
      'created' => $exportTransformationContext->created,
      'user' => $exportTransformationContext->user->user_login,
      'options' => [AttachmentImporter::OPTION_OVERWRITE => false],
    ]);

    $retVal = call_user_func(
      $provider->callback,
      self::__createSlice(self::IMAGE, $importTransformationContext),
      [AttachmentImporter::OPTION_OVERWRITE => false],
      $importTransformationContext,
    );

    $this->assertTrue($retVal, 'upload import should return it was successful');

    // ensure uploaded image exists twice
    // \list_files returns not only files but also empty folders
    $uploaded_files = \list_files(\wp_get_upload_dir()['basedir'] . '/2021/06');
    // thats why we filter out directories
    $uploaded_files = array_values(array_filter($uploaded_files, function ($path) {
      return is_file($path);
    }));

    sort($uploaded_files);
    $this->assertEquals(2, count($uploaded_files), 'there should be 2 uploads');

    $this->assertStringEndsWith(str_replace('.jpg', '-1.jpg', self::IMAGE), $uploaded_files[0]);
    $this->assertStringEndsWith(self::IMAGE, $uploaded_files[1]);

    // ensure there are 2 attachments
    $attachments = \get_posts(['post_type' => 'attachment', 'numberposts' => -1, 'post_status' => null, 'post_parent' => null]);
    $this->assertCount(2, $attachments, 'only one wordpress attachment should be registered');

    // ensure page referencing uploaded image attachment was rewritten to reference our our renamed image
    clean_post_cache($page_id);
    $page_content = \get_post($page_id)->post_content;

    $this->assertStringNotContainsString(self::IMAGE, $page_content, 'previous image name should be no more found in document');
    $this->assertStringContainsString(str_replace('.jpg', '-1.jpg', self::IMAGE), $page_content, 'new image name should be found in document');
  }
}
