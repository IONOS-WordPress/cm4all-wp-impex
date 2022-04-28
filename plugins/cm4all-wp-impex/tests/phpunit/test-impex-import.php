<?php

namespace cm4all\wp\impex\tests\phpunit;

use cm4all\wp\impex\AttachmentImporter;
use cm4all\wp\impex\Impex;
use cm4all\wp\impex\ImpexExportRuntimeException;
use cm4all\wp\impex\ImpexImport;
use cm4all\wp\impex\ImpexImportTransformationContext;
use cm4all\wp\impex\ImpexPart;

class TestImpexImport extends ImpexUnitTestcase
{
  function setUp()
  {
    parent::setUp();

    if (!defined('WP_IMPORTING')) {
      define('WP_IMPORTING', true);
    }

    if (!defined('WP_LOAD_IMPORTERS')) {
      define('WP_LOAD_IMPORTERS', true);
    }

    \add_filter(
      'import_allow_create_users',
      '__return_true',
    );

    global $wpdb;
    // crude but effective: make sure there's no residual data in the main tables
    foreach (array('posts', 'postmeta', 'comments', 'terms', 'term_taxonomy', 'term_relationships', 'users', 'usermeta') as $table) {
      // phpcs:ignore WordPress.DB.PreparedSQL
      $wpdb->query("DELETE FROM {$wpdb->$table}");
    }

    /*
     CAVEAT : tables created using WordPress install/upgrade mechanism 
     (https://codex.wordpress.org/Creating_Tables_with_Plugins) will not use 
     the wp phpunit filter making the table temporary. 

     => so we need to drop the table manually to have a consistent setup
    */
    global $wpdb;
    $wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . Impex::DB_SNAPSHOTS_TABLENAME);

    $this->user = $this->factory->user->create(['role' => 'administrator', 'user_login' => 'test-admin']);
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

  function testImporttTypes(): void
  {
    $Import = Impex::getInstance()->Import;

    $this->assertInstanceOf(ImpexImport::class, $Import);
    $this->assertInstanceOf(ImpexPart::class, $Import);
  }

  function testImportInitialized(): void
  {
    $Import = Impex::getInstance()->Import;

    $this->assertInstanceOf(ImpexImport::class, Impex::getInstance()->Import);
    $this->assertInstanceOf(ImpexPart::class, Impex::getInstance()->Import);
  }

  function testImportProviders(): void
  {
    $Import = Impex::getInstance()->Import;

    // create providers
    $PROVIDER_NAMES = ['pages', 'settings'];
    foreach ($PROVIDER_NAMES as $name) {
      $Import->addProvider($name, function () {
      });
    }

    // test providers exist
    $extensionNames = \wp_list_pluck(iterator_to_array($Import->getProviders()), 'name');
    $this->assertEquals($PROVIDER_NAMES, $extensionNames);
  }

  function testImportProfiles(): void
  {
    $Import = Impex::getInstance()->Import;

    // create profiles
    $PROFILE_NAMES = ['all', 'content'];
    foreach ($PROFILE_NAMES as $name) {
      $Import->addProfile($name);
    }

    // test profiles exist
    $profileNames = \wp_list_pluck(iterator_to_array($Import->getProfiles()), 'name');
    $this->assertEquals($PROFILE_NAMES, $profileNames);
  }

  function testImportProfileWithDisabledTasks(): void
  {
    $PROVIDER_NAME = 'provider';
    Impex::getInstance()->Import->addProvider($PROVIDER_NAME, function (array $slice, array $options, ImpexImportTransformationContext $transformationContext): bool {
      if ($slice[Impex::SLICE_DATA] === $options[Impex::SLICE_DATA]) {
        return true;
      }
      return false;
    });

    $profile = Impex::getInstance()->Import->addProfile('profile');

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

    $slices = [
      [
        Impex::SLICE_TAG => $PROVIDER_NAME,
        Impex::SLICE_DATA => 'task-a',
      ], [
        Impex::SLICE_TAG => $PROVIDER_NAME,
        Impex::SLICE_DATA => 'task-b',
      ]
    ];

    \wp_set_current_user(self::factory()->user->create(['role' => 'administrator', 'user_login' => 'me']));

    $importTransformationContext = Impex::getInstance()->Import->create($profile);
    foreach ($slices as $position => $slice) {
      $success = Impex::getInstance()->Import->_upsert_slice($importTransformationContext->id, $position, $slice);

      if ($success === false) {
        global $wpdb;
        throw new ImpexExportRuntimeException(sprintf('failed to insert/update jsonized slice(=%s) to database : %s', $slice, $wpdb->last_error));
      }
    }
    $unconsumedSlices = Impex::getInstance()->Import->consume($importTransformationContext);

    $this->assertEmpty($unconsumedSlices, 'all slices should be consumed');

    $task_b->disabled = true;
    $unconsumedSlices = Impex::getInstance()->Import->consume($importTransformationContext);
    $this->assertSameSets([$slices[1]], $unconsumedSlices, 'only the first slice should be consumed');
  }

  function testImportDbTablesInstalled(): void
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

  /*
    testcase is obsolete since it utilizes the removed WordPress Importer 
    @TODO : adapt testcase to new ContentImporter 
  */
  function _testConsume()
  {
    $this->__invokeImpexActions();

    \wp_set_current_user(self::factory()->user->create(['role' => 'administrator', 'user_login' => 'me']));

    $profile = Impex::getInstance()->Import->addProfile('wpimporter-and-attachments');
    $profile->addTask('wp-importer', WordpressImporter::PROVIDER_NAME);
    $profile->addTask('attachments', AttachmentImporter::PROVIDER_NAME);

    // fake a import
    $importTransformationContext = Impex::getInstance()->Import->create($profile);

    // copy images to uploads_subpath of this import
    $EXPORT_PATH = __DIR__ . '/fixtures/exports/pages-and-attachments-export';
    $IMPORT_PATH = $importTransformationContext->path . '/2021/08';
    $success = \wp_mkdir_p($IMPORT_PATH);
    $attachments = array_values(array_filter(
      preg_grep(
        "/\.jpg$/",
        scandir($EXPORT_PATH)
      ),
      function ($_) use ($EXPORT_PATH) {
        return is_file($EXPORT_PATH  . '/' . $_);
      }
    ));
    foreach ($attachments as $attachment) {
      $this->assertTrue(copy($EXPORT_PATH . '/' . $attachment, $IMPORT_PATH . '/' . preg_replace('/^slice-\d+\-/', '', $attachment)));
    }


    // read in slices 
    $slices = array_map(function ($slice_filename) use ($EXPORT_PATH) {
      return json_decode(file_get_contents($EXPORT_PATH . '/' . $slice_filename), JSON_OBJECT_AS_ARRAY);
    }, array_filter(
      preg_grep(
        "/^slice-\d+\.json$/",
        scandir($EXPORT_PATH)
      ),
      function ($_) use ($EXPORT_PATH) {
        return is_file($EXPORT_PATH  . '/' . $_);
      }
    ));
    // inject them into import chunks table  
    global $wpdb;

    foreach ($slices as $position => $slice) {
      $wpdb->insert($wpdb->prefix . Impex::DB_SNAPSHOTS_TABLENAME, [
        'position' => $position,
        'snapshot_id' => $importTransformationContext->id,
        'slice' => json_encode($slice),
      ]);
    }

    $posts = \get_posts(
      [
        'post_type'   => 'any',
        'post_status' => 'any',
      ]
    );
    $this->assertCount(0, $posts, 'there should be no documents in wordpress');

    // now lets try the final import using a fake import context
    $importTransformationContext = ImpexImportTransformationContext::fromJson(
      array_merge(
        $importTransformationContext->jsonSerialize(),
        [
          'options' => [AttachmentImporter::OPTION_OVERWRITE => false]
        ],
      )
    );
    $unconsumedSlices = Impex::getInstance()->Import->consume($importTransformationContext);
    $this->assertCount(0, $unconsumedSlices, "all slices should be consumed");

    $all_posts = \get_posts(
      [
        'post_type'   => 'any',
        'post_status' => 'any',
      ]
    );
    $this->assertCount(5, $all_posts, 'there should be 5 documents in wordpress');

    $posts = array_filter($all_posts, function ($post) {
      return $post->post_type === 'post';
    });
    $this->assertCount(1, $posts, 'there should be 1 imported post in wordpress');

    $pages = array_filter($all_posts, function ($post) {
      return $post->post_type === 'page';
    });
    $this->assertCount(1, $pages, 'there should be 1 imported page in wordpress');

    $attachments = array_filter($all_posts, function ($post) {
      return $post->post_type === 'attachment';
    });
    $this->assertCount(3, $attachments, 'there should be 3 imported attachments in wordpress');
  }
}
