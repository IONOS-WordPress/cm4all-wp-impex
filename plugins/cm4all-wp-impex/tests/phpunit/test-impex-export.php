<?php

namespace cm4all\wp\impex\tests\phpunit;

use cm4all\wp\impex\AttachmentsExporter;
use cm4all\wp\impex\Impex;
use cm4all\wp\impex\ImpexExport;
use cm4all\wp\impex\ImpexExportProfile;
use cm4all\wp\impex\ImpexExportProvider;
use cm4all\wp\impex\ImpexExportTransformationContext;
use cm4all\wp\impex\ImpexPart;
use cm4all\wp\impex\WpOptionsExporter;

class TestImpexExport extends ImpexUnitTestcase
{
  function setUp()
  {
    parent::setUp();

    /*
     CAVEAT : tables created using Wordpress install/upgrade mechanism 
     (https://codex.wordpress.org/Creating_Tables_with_Plugins) will not use 
     the wp phpunit filter making the table temporary. 

     => so we need to drop the table manually to have a consistent setup
    */
    global $wpdb;
    $wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . Impex::DB_SNAPSHOTS_TABLENAME);
  }

  function tearDown()
  {
    parent::tearDown();

    // in case of broken phpunit calls old uploads may exist within $ignored_files (populated in setUp)
    // to ensure these will be cleaned up properly we force to forget about these intermediate file uploads
    self::$ignore_files = [];

    // remove all uploads
    $this->remove_added_uploads();
  }

  function testExportTypes(): void
  {
    $Export = Impex::getInstance()->Export;

    $this->assertInstanceOf(ImpexExport::class, $Export);
    $this->assertInstanceOf(ImpexPart::class, $Export);
  }

  function testExportInitialized(): void
  {
    $Export = Impex::getInstance()->Export;

    $this->assertInstanceOf(ImpexExport::class, Impex::getInstance()->Export);
    $this->assertInstanceOf(ImpexPart::class, Impex::getInstance()->Export);
  }

  function testExportProviders(): void
  {
    $Export = Impex::getInstance()->Export;

    // create providers
    $PROVIDER_NAMES = ['pages', 'settings'];
    foreach ($PROVIDER_NAMES as $name) {
      $Export->addProvider($name, function () {
      });
    }

    // test providers exist
    $extensionNames = \wp_list_pluck(iterator_to_array($Export->getProviders()), 'name');
    $this->assertEquals($PROVIDER_NAMES, $extensionNames);
  }

  function testExportProfiles(): void
  {
    $Export = Impex::getInstance()->Export;

    // create profiles
    $PROFILE_NAMES = ['all', 'content'];
    foreach ($PROFILE_NAMES as $name) {
      $Export->addProfile($name);
    }

    // test profiles exist
    $profileNames = \wp_list_pluck(iterator_to_array($Export->getProfiles()), 'name');
    $this->assertEquals($PROFILE_NAMES, $profileNames);
  }

  function testExportDbTablesInstalled(): void
  {
    global $wpdb;

    /*
      this is a bit quirky ... wp unit tests apply a query filter to wpdb which transforms CREATE/DROP table statements 
      into CREATE/DROP TEMPORARY table statements (https://wordpress.stackexchange.com/questions/220275/wordpress-unit-testing-cannot-create-tables)

      therefore tables created in wp unit tests are not visible using SHOW TABLES LIKE statements.
      that's why we "test" the existence of a table using SELECT COUNT (=> which returns null / error in case the temporary table doesnt exists)
    */
    $db_chunks_tablename = $wpdb->prefix . Impex::DB_SNAPSHOTS_TABLENAME;
    $db_chunks_tablename_rowCount = $wpdb->get_var("SELECT COUNT(*) FROM $db_chunks_tablename");
    $this->assertNotNull($db_chunks_tablename_rowCount, "ensure $db_chunks_tablename exists");
  }

  function testExportProfileWithDisabledTasks(): void
  {
    $PROVIDER_NAME = 'provider';
    Impex::getInstance()->Export->addProvider($PROVIDER_NAME, function (array $options, ImpexExportTransformationContext $transformationContext) use ($PROVIDER_NAME): \Generator {
      yield [
        Impex::SLICE_TAG => $PROVIDER_NAME,
        Impex::SLICE_DATA => $options[Impex::SLICE_DATA],
      ];
    });

    $profile = Impex::getInstance()->Export->addProfile('profile');

    $profile->addTask(
      'task-a',
      $PROVIDER_NAME,
      [
        Impex::SLICE_DATA => 'task-a',
      ]
    );

    $task_b = $profile->addTask(
      'task-b',
      $PROVIDER_NAME,
      [
        Impex::SLICE_DATA => 'task-b',
      ]
    );

    \wp_set_current_user(self::factory()->user->create(['role' => 'administrator', 'user_login' => 'me']));
    $transformationContext = new ImpexExportTransformationContext($profile->name);

    $slices = iterator_to_array(Impex::getInstance()->Export->extract($transformationContext));
    $this->assertCount(2, $slices, '2 slices should be exported');

    $task_b->disabled = true;
    $slices = iterator_to_array(Impex::getInstance()->Export->extract($transformationContext));
    $this->assertCount(1, $slices, '1 slice should be exported');
    $this->assertEquals($slices[0][Impex::SLICE_DATA], 'task-a', 'only data from task-a was exported');
  }

  function testExportExtractWithOptions(): void
  {
    $this->__invokeImpexActions();

    $profile = Impex::getInstance()->Export->addProfile('dummy');

    $matchingWpOptions = array_values(array_filter(array_keys(\wp_load_alloptions()), function ($wpOption) {
      return str_starts_with($wpOption, 'mailserver_') || str_starts_with($wpOption, 'blog_');
    }));

    $profile->addTask(
      'wp_o',
      WpOptionsExporter::PROVIDER_NAME,
      [
        WpOptionsExporter::OPTION_SELECTOR => ['mailserver_*', 'blog_*'],
        WpOptionsExporter::OPTION_SLICE_MAX_ITEMS => count($matchingWpOptions),
      ]
    );

    \wp_set_current_user(self::factory()->user->create(['role' => 'administrator', 'user_login' => 'me']));
    $slices = iterator_to_array(Impex::getInstance()->Export->extract(new ImpexExportTransformationContext($profile->name)));

    $this->assertCount(1, $slices, '1 slice should be exported');
    $this->assertCount(count($matchingWpOptions), $slices[0][Impex::SLICE_DATA], 'all matching wp_options should be contained in the exported slice');

    // test per-task-option overriding
    $slices = iterator_to_array(Impex::getInstance()->Export->extract(
      new ImpexExportTransformationContext(
        profile_name: $profile->name,
        options: ['wp_o' . WpOptionsExporter::OPTION_SLICE_MAX_ITEMS => 1],
      )
    ));
    $this->assertCount(count($matchingWpOptions), $slices, '1 slice per matching wp_option should be exported');
    foreach ($slices as $slice) {
      $this->assertCount(1, $slice[Impex::SLICE_DATA], 'contained wp_option count is always one wp_option(=1)');
      $this->assertEquals(Impex::SLICE_TYPE_PHP, $slice[Impex::SLICE_TYPE], 'slice type is Impex::SLICE_TYPE_PHP');
    }
  }

  function testExportSave(): void
  {
    $this->__invokeImpexActions();

    $profile = Impex::getInstance()->Export->addProfile('dummy');

    $matchingWpOptions = array_values(array_filter(array_keys(\wp_load_alloptions()), function ($wpOption) {
      return str_starts_with($wpOption, 'mailserver_') || str_starts_with($wpOption, 'blog_');
    }));

    $profile->addTask(
      'wp_o',
      WpOptionsExporter::PROVIDER_NAME,
      [
        WpOptionsExporter::OPTION_SELECTOR => ['mailserver_*', 'blog_*'],
        WpOptionsExporter::OPTION_SLICE_MAX_ITEMS => 1,
      ]
    );

    $options = [];
    $this->assertFalse(\get_option(ImpexExport::WP_OPTION_EXPORTS), 'wp_option for registered exports should be initially uninitialized');

    \wp_set_current_user(self::factory()->user->create(['role' => 'editor', 'user_login' => 'exported_user']));

    $transformationContext = Impex::getInstance()->Export->save($profile, $options);
    $this->assertTrue(\wp_is_uuid($transformationContext->id), 'save() should return a valid uuidv4 export_id');

    $wp_option_exports = \get_option(ImpexExport::WP_OPTION_EXPORTS);
    $this->assertCount(1, $wp_option_exports, 'after save() wp options should contain an entry for the saveed export');

    $this->assertSameSetsWithIndex([
      'id' => $transformationContext->id,
      'profile' => $profile->name,
      'options' => $options,
      'description' => '',
      'name' => $wp_option_exports[0]['name'],
      'user' => $wp_option_exports[0]['user'],
      'created' => $wp_option_exports[0]['created'],
    ], $wp_option_exports[0], 'wp_options item created by save() matches the expected');

    global $wpdb;
    $rows = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . Impex::DB_SNAPSHOTS_TABLENAME, ARRAY_A);

    $this->assertCount(count($matchingWpOptions), $rows, 'for each generated slice a row should be created');

    $slices = iterator_to_array(Impex::getInstance()->Export->extract($transformationContext));
    foreach ($slices as $position => $slice) {
      $this->assertEquals($slice, json_decode($rows[$position]['slice'], JSON_OBJECT_AS_ARRAY), 'column "slice" should contain the JSON serialized form of the slice');
    }
  }

  function testExportSaveFilters(): void
  {
    $this->__invokeImpexActions();

    $profile = Impex::getInstance()->Export->addProfile('dummy');

    $profile->addTask(
      'wp_a',
      AttachmentsExporter::PROVIDER_NAME,
      []
    );

    $upload_names = ['angeln.jpg', 'wood.jpg', 'zdf-hitparade.jpg'];

    $attachment_ids = [];
    foreach ($upload_names as $upload_name) {
      $post = $this->factory->post->create_and_get();
      $attachment_ids[$upload_name] = $this->factory->attachment->create_upload_object(__DIR__ . '/fixtures/uploads/images/' . $upload_name, $post->ID);
    }

    \wp_set_current_user(self::factory()->user->create(['role' => 'administrator', 'user_login' => 'me']));
    // attachment exporter installs a export filter removing the slice data portion (which is a non-serializable stream descriptor)
    $transformationContext = Impex::getInstance()->Export->save($profile);

    global $wpdb;
    $rows = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . Impex::DB_SNAPSHOTS_TABLENAME, ARRAY_A);

    $this->assertCount(count($upload_names), $rows, 'for each attachment a slice(and also a row) should be created');

    foreach ($rows as $index => $row) {
      $slice_json = json_decode($row['slice'], JSON_OBJECT_AS_ARRAY);

      // simulate deserialization to trigger attachment importer's deserialization filter
      $slice = \apply_filters(ImpexExport::WP_FILTER_SLICE_DESERIALIZE, $slice_json, $transformationContext);

      $origFile = \get_attached_file($attachment_ids[$slice[Impex::SLICE_META]['name']]);
      $uploadsFile = $transformationContext->path . '/' . $slice[Impex::SLICE_DATA];

      $this->assertEquals(
        sha1_file($origFile, true),
        sha1_file($uploadsFile, true),
        'deserialized attachment exports "data" references the uploaded attachment copy'
      );
    }
  }
}
