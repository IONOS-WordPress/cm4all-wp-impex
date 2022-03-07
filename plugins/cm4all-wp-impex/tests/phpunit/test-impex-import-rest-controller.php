<?php

namespace cm4all\wp\impex\tests\phpunit;

use cm4all\wp\impex\AttachmentImporter;
use cm4all\wp\impex\Impex;
use cm4all\wp\impex\ImpexImport;
use cm4all\wp\impex\ImpexImportRESTController;
use cm4all\wp\impex\ImpexImportTransformationContext;
use cm4all\wp\impex\ImpexRestController;

/**
 * @group restapi
 */
class TestImpexImportRestController extends ImpexRestUnitTestcase
{
  public function setUp()
  {
    parent::setUp();

    $this->user = $this->factory->user->create(['role' => 'administrator', 'user_login' => 'test-admin']);
  }

  /*
  protected function __createImportProfile(): ImpexImportProfile
  {
    __registerAttachmentImportProvider();
    __registerDbTableImportProvider();
    __registerWordpressImportProvider();
    __registerWpOptionsImportProvider();

    $providers = [
      AttachmentImporter::PROVIDER_NAME,
      DbTableImporter::PROVIDER_NAME,
      WordpressImporter::PROVIDER_NAME,
      WpOptionsImporter::PROVIDER_NAME,
    ];

    $profile = Impex::getInstance()->Import->addProfile('test-profile');

    foreach ($providers as $provider_name) {
      $profile->addTask($provider_name, $provider_name);
    }

    return $profile;
  }
  */

  public function test_register_routes()
  {
    $routes = $this->server->get_routes();
    $this->assertArrayHasKey(ImpexRestController::BASE_URI . ImpexImportRESTController::REST_BASE, $routes, 'import route is registered');
  }

  public function test_RestApiSecured()
  {
    // ensure no user / user without right permission is set
    $this->assertFalse(is_user_logged_in());
    $this->assertFalse(current_user_can('import'));
    $response = $this->server->dispatch(new \WP_REST_Request('GET', ImpexRestController::BASE_URI . ImpexImportRESTController::REST_BASE));
    $this->assertErrorResponse('rest_forbidden', $response, 401);

    // test with valid in user
    \wp_set_current_user($this->user);
    $response = $this->server->dispatch(new \WP_REST_Request('GET', ImpexRestController::BASE_URI . ImpexImportRESTController::REST_BASE));
    $this->assertEquals(200, $response->status);
  }

  public function test_context_param()
  {
    // No op
  }

  public function test_get_items()
  {
    \wp_set_current_user($this->user);

    $response = $this->server->dispatch(new \WP_REST_Request('GET', ImpexRestController::BASE_URI . ImpexImportRESTController::REST_BASE));
    $this->assertIsArray($response->data);
    $this->assertCount(0, $response->data, 'assert no imports are registered');

    $import_ids = [];
    $import_ids[] = Impex::getInstance()->Import->create(
      name: 'test-import-0',
      profile: Impex::getInstance()->Import->addProfile('test-import-0-profile'),
    )->id;
    $response = $this->server->dispatch(new \WP_REST_Request('GET', ImpexRestController::BASE_URI . ImpexImportRESTController::REST_BASE));
    $this->assertIsArray($response->data);
    $this->assertCount(1, $response->data);

    $import_ids[] = Impex::getInstance()->Import->create(
      name: 'test-import-1',
      profile: Impex::getInstance()->Import->addProfile('test-import-1-profile'),
    )->id;
    $response = $this->server->dispatch(new \WP_REST_Request('GET', ImpexRestController::BASE_URI . ImpexImportRESTController::REST_BASE));
    $this->assertIsArray($response->data);
    $this->assertCount(2, $response->data);

    foreach ($response->data as $index => $import_item) {
      $this->assertIsArray($import_item);
      $this->assertArrayHasKey('id', $import_item);
      $this->assertArrayHasKey('name', $import_item);
      $this->assertArrayHasKey('description', $import_item);
      $this->assertEquals($import_ids[$index], $import_item['id']);
      $this->assertEquals("test-import-$index", $import_item['name']);
    }
  }

  public function test_get_item()
  {
    \wp_set_current_user($this->user);

    // create import
    $import_id = Impex::getInstance()->Import->create(Impex::getInstance()->Import->addProfile('dummy-import'))->id;

    // try to get it 
    $request = new \WP_REST_Request('GET', ImpexRestController::BASE_URI . ImpexImportRESTController::REST_BASE . '/' . $import_id);
    $response = $this->server->dispatch($request);
    $this->assertEquals(200, $response->status);
    $this->assertEquals($import_id, $response->data['id']);

    // try to get a non existion item
    $request = new \WP_REST_Request('GET', ImpexRestController::BASE_URI . ImpexImportRESTController::REST_BASE . '/nonsense');
    $response = $this->server->dispatch($request);
    $this->assertErrorResponse('not-found', $response, 404);
  }

  public function test_create_item()
  {
    \wp_set_current_user($this->user);

    $profile = Impex::getInstance()->Import->addProfile('dummy-import');

    $EXPECT = ['name' => 'test-import-1', 'description' => 'test_description', 'profile' => $profile->name, 'options' => []];

    $request = new \WP_REST_Request('POST', ImpexRestController::BASE_URI . ImpexImportRESTController::REST_BASE);
    $request->add_header('Content-Type', 'application/json');
    $request->set_body(\wp_json_encode($EXPECT));

    $create_response = $this->server->dispatch($request);
    $this->assertIsArray($create_response->data);
    $this->assertArrayHasKey('id', $create_response->data);
    $this->assertArrayHasKey('name', $create_response->data);
    $this->assertArrayHasKey('description', $create_response->data);
    $this->assertEquals($EXPECT['name'], $create_response->data['name']);
    $this->assertEquals($EXPECT['description'], $create_response->data['description']);

    $response = $this->server->dispatch(new \WP_REST_Request('GET', ImpexRestController::BASE_URI . ImpexImportRESTController::REST_BASE));
    $this->assertIsArray($response->data);
    $this->assertCount(1, $response->data);

    $this->assertEquals($create_response->data, $response->data[0]);
  }

  public function test_update_item()
  {
    \wp_set_current_user($this->user);

    // create item
    $request = new \WP_REST_Request('POST', ImpexRestController::BASE_URI . ImpexImportRESTController::REST_BASE);
    $request->add_header('Content-Type', 'application/json');
    $request->set_body(\wp_json_encode([
      'name' => 'initial-name',
      'description' => 'initial-description',
      'profile' => Impex::getInstance()->Import->addProfile('dummy-profile')->name,
      'options' => []
    ]));
    $create_response = $this->server->dispatch($request);

    // update export
    $request = new \WP_REST_Request('PATCH', ImpexRestController::BASE_URI . ImpexImportRESTController::REST_BASE . '/' . $create_response->data['id']);
    $request->add_header('Content-Type', 'application/json');
    $UPDATE_VALUES = ['name' => 'updated-name', 'description' => 'updated-description'];
    $request->set_body(\wp_json_encode($UPDATE_VALUES));
    $update_response = $this->server->dispatch($request);
    $this->assertEquals(200, $update_response->status);
    $this->assertArrayHasKey('description', $update_response->data, 'description should be available');
    $this->assertEquals($create_response->data['created'], $update_response->data['created'], 'created should be unchanged');
    $this->assertEquals($UPDATE_VALUES['name'], $update_response->data['name'], 'name should be changed');

    // ensure updates are persisted
    $request = new \WP_REST_Request('GET', ImpexRestController::BASE_URI . ImpexImportRESTController::REST_BASE . '/' . $create_response->data['id']);
    $get_response = $this->server->dispatch($request);
    $this->assertEqualSets($update_response->data, $get_response->data);

    // try to update non existing export should fail
    $request = new \WP_REST_Request('PATCH', ImpexRestController::BASE_URI . ImpexImportRESTController::REST_BASE . '/nonsense');
    $request->add_header('Content-Type', 'application/json');
    $UPDATE_VALUES = ['name' => 'xxx-name', 'profile' => 'xxx-profile', 'xxx-description' => 'whoop whoop'];
    $request->set_body(\wp_json_encode($UPDATE_VALUES));
    $update_response = $this->server->dispatch($request);
    $this->assertErrorResponse('not-found', $update_response, 404);
  }

  public function test_delete_item()
  {
    \wp_set_current_user($this->user);

    // create import
    $request = new \WP_REST_Request('POST', ImpexRestController::BASE_URI . ImpexImportRESTController::REST_BASE);
    $request->add_header('Content-Type', 'application/json');
    $request->set_body(\wp_json_encode(['name' => 'test-import', 'profile' => Impex::getInstance()->Import->addProfile('dummy-profile')->name]));
    $create_response = $this->server->dispatch($request);

    $transformationContext = ImpexImportTransformationContext::fromJson($create_response->data);

    $this->assertTrue(file_exists($transformationContext->path), 'ensure an export specific uploads directory as created');

    // delete import
    $request = new \WP_REST_Request('DELETE', ImpexRestController::BASE_URI . ImpexImportRESTController::REST_BASE . '/' . $create_response->data['id']);
    $delete_response = $this->server->dispatch($request);
    $this->assertEquals(200, $delete_response->status);

    $imports = \get_option(ImpexImport::WP_OPTION_IMPORTS, []);
    $this->assertCount(0, $imports, 'no exports should be avaliable');

    $this->assertFalse(file_exists($transformationContext->path), 'ensure an export specific uploads directory as created');
  }

  /*
    testcase is obsolete since it utilizes the removed Wordpress Importer 
    @TODO : adapt testcase to new ContentImporter 
  */
  public function _test_upsert_item_slices()
  {
    \wp_set_current_user($this->user);
    $import_id = Impex::getInstance()->Import->create(Impex::getInstance()->Import->addProfile('test-import-1'))->id;

    $this->assertNotNull($import_id);

    $EXPORT_PATH = __DIR__ . '/fixtures/exports/pages-and-attachments-export';
    $slice_filenames = array_values(array_filter(
      preg_grep(
        "/^slice-\d+\.json$/",
        scandir($EXPORT_PATH)
      ),
      function ($_) use ($EXPORT_PATH) {
        return is_file($EXPORT_PATH  . '/' . $_);
      }
    ));

    foreach ($slice_filenames as $index => $slice_filename) {
      $slice_file = $EXPORT_PATH  . '/' . $slice_filename;

      $request = new \WP_REST_Request('POST', ImpexRestController::BASE_URI . ImpexImportRESTController::REST_BASE . '/' . $import_id . '/slice');
      $request->set_query_params(['position' => $index]);
      // $request->add_header('Content-Type', 'application/json');
      // $request->set_body(file_get_contents($slice_file));

      $request->set_body_params([
        'slice' => file_get_contents($slice_file)
      ]);

      $slice_json = json_decode(file_get_contents($slice_file), JSON_OBJECT_AS_ARRAY);
      if ($slice_json[Impex::SLICE_TAG] === 'attachment') {
        $attachment_filename = $EXPORT_PATH  . '/' . basename($slice_file, '.json') . '-' . basename($slice_json[Impex::SLICE_DATA]);

        // ATTENTION: we need to make a copy of the file since attachment importer will move the uploaded file 
        $uploadedAttachment = tempnam(sys_get_temp_dir(), 'impex_import_');
        $success = file_put_contents($uploadedAttachment, file_get_contents($attachment_filename));

        $file_params = [
          AttachmentImporter::WP_FILTER_IMPORT_REST_SLICE_UPLOAD_FILE => [
            'file'     => file_get_contents($uploadedAttachment),
            'name'     => $attachment_filename,
            'size'     => filesize($uploadedAttachment),
            'tmp_name' => $uploadedAttachment,
          ],
        ];

        $request->set_file_params($file_params);
      }

      $response = $this->server->dispatch($request);
      $this->assertEquals(200, $response->status);
    }

    global $wpdb;
    $inserted_slices = absint($wpdb->get_var(
      $wpdb->prepare("SELECT COUNT(*) from {$wpdb->prefix}" . Impex::DB_SNAPSHOTS_TABLENAME . ' WHERE snapshot_id=%s', $import_id)
    ));
    $this->assertEquals(count($slice_filenames), $inserted_slices, 'all uploaded chunks should be in the database');

    // try to insert the slices again 
    foreach ($slice_filenames as $index => $slice_filename) {
      $slice_file = $EXPORT_PATH  . '/' . $slice_filename;

      $request = new \WP_REST_Request('POST', ImpexRestController::BASE_URI . ImpexImportRESTController::REST_BASE . '/' . $import_id . '/slice');
      $request->set_query_params(['position' => $index]);
      // $request->add_header('Content-Type', 'application/json');
      $request->set_body_params([
        'slice' => file_get_contents($slice_file)
      ]);

      $slice_json = json_decode(file_get_contents($slice_file), JSON_OBJECT_AS_ARRAY);
      if ($slice_json[Impex::SLICE_TAG] === 'attachment') {
        $attachment_filename = $EXPORT_PATH  . '/' . basename($slice_file, '.json') . '-' . basename($slice_json[Impex::SLICE_DATA]);

        // ATTENTION: we need to make a copy of the file since attachment importer will move the uploaded file 
        $uploadedAttachment = tempnam(sys_get_temp_dir(), 'impex_import_');
        $success = file_put_contents($uploadedAttachment, file_get_contents($attachment_filename));

        $file_params = [
          AttachmentImporter::WP_FILTER_IMPORT_REST_SLICE_UPLOAD_FILE => [
            'file'     => file_get_contents($uploadedAttachment),
            'name'     => $attachment_filename,
            'size'     => filesize($uploadedAttachment),
            'tmp_name' => $uploadedAttachment,
          ],
        ];

        $request->set_file_params($file_params);
      }

      $response = $this->server->dispatch($request);
      $this->assertEquals(200, $response->status);
    }

    global $wpdb;
    $inserted_slices = absint($wpdb->get_var(
      $wpdb->prepare("SELECT COUNT(*) from {$wpdb->prefix}" . Impex::DB_SNAPSHOTS_TABLENAME . ' WHERE snapshot_id=%s', $import_id)
    ));
    $this->assertEquals(count($slice_filenames), $inserted_slices, 'uploaded chunks should be replaced in the database (but not newly inserted)');
  }


  public function test_prepare_item()
  {
    // No op
  }

  public function test_get_item_schema()
  {
    \wp_set_current_user($this->user);

    $response = $this->server->dispatch(new \WP_REST_Request('GET', ImpexRestController::BASE_URI . ImpexImportRESTController::REST_BASE . '/schema'));

    $this->assertEquals(200, 200); // $response->status);
  }
}
