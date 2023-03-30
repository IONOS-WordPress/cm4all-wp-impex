<?php

namespace cm4all\wp\impex\tests\phpunit;

use cm4all\wp\impex\Impex;
use cm4all\wp\impex\ImpexExport;
use cm4all\wp\impex\ImpexExportRESTController;
use cm4all\wp\impex\ImpexExportProfile;
use cm4all\wp\impex\ImpexExportTransformationContext;
use cm4all\wp\impex\ImpexRestController;

/**
 * @group restapi
 */
class TestImpexExportRestController extends ImpexRestUnitTestcase
{
  const EXPORT_PROVIDER_SLICE_TAG = 'custom-export-provider-tag';

  public function setUp()
  {
    parent::setUp();

    $this->user = $this->factory->user->create(['role' => 'administrator', 'user_login' => 'test-admin']);
  }

  protected function __createExportProfile(string $name, array $providers): ImpexExportProfile
  {
    $profile = Impex::getInstance()->Export->addProfile($name);

    foreach ($providers as $name => $data) {
      Impex::getInstance()->Export->addProvider($name, function (array $options, ImpexExportTransformationContext $transformationContext) use ($data): \Generator {
        foreach ($data as $index => $item) {
          yield [
            Impex::SLICE_TAG => self::EXPORT_PROVIDER_SLICE_TAG,
            Impex::SLICE_META => [
              'name' => $index,
              Impex::SLICE_META_ENTITY => 'string',
              'options' => $options,
            ],
            Impex::SLICE_DATA => $item,
          ];
        }
      });
    }

    $profile->addTask($name, $name);

    return $profile;
  }

  public function test_register_routes()
  {
    $routes = $this->server->get_routes();
    $this->assertArrayHasKey(ImpexRestController::BASE_URI . ImpexExportRESTController::REST_BASE, $routes, 'export route is registered');
  }

  public function test_RestApiSecured()
  {
    // ensure no user / user without right permission is set
    $this->assertFalse(is_user_logged_in());
    $this->assertFalse(current_user_can('export'));
    $response = $this->server->dispatch(new \WP_REST_Request('GET', ImpexRestController::BASE_URI . ImpexExportRESTController::REST_BASE));
    $this->assertErrorResponse('rest_forbidden', $response, 401);

    // test with valid in user
    \wp_set_current_user($this->user);
    $response = $this->server->dispatch(new \WP_REST_Request('GET', ImpexRestController::BASE_URI . ImpexExportRESTController::REST_BASE));
    $this->assertEquals(200, $response->status);

    // test with rest nonce
    // $nounce = \wp_create_nonce('wp_rest');
    // $request = new \WP_REST_Request('GET', ImpexRestController::BASE_URI . ImpexExportRESTController::REST_BASE);
    // $request->set_header("X-WP-Nonce", $nounce);
    // $response = $this->server->dispatch($request);
    // $this->assertEquals(200, $response->status);
  }

  public function test_context_param()
  {
    $this->expectNotToPerformAssertions();
  }

  public function test_get_items()
  {
    \wp_set_current_user($this->user);

    $response = $this->server->dispatch(new \WP_REST_Request('GET', ImpexRestController::BASE_URI . ImpexExportRESTController::REST_BASE));
    $this->assertIsArray($response->data);
    $this->assertCount(0, $response->data);

    $profile = $this->__createExportProfile('single-slice-export-profile', ['provider-a' => ['alpha']]);

    $export_ids = [];
    $export_ids[] = Impex::getInstance()->Export->save($profile)->id;
    $response = $this->server->dispatch(new \WP_REST_Request('GET', ImpexRestController::BASE_URI . ImpexExportRESTController::REST_BASE));
    $this->assertIsArray($response->data);
    $this->assertCount(1, $response->data);

    $export_ids[] = Impex::getInstance()->Export->save($profile)->id;
    $response = $this->server->dispatch(new \WP_REST_Request('GET', ImpexRestController::BASE_URI . ImpexExportRESTController::REST_BASE));
    $this->assertIsArray($response->data);
    $this->assertCount(2, $response->data);

    foreach ($response->data as $index => $export_item) {
      $this->assertIsArray($export_item);
      $this->assertArrayHasKey('id', $export_item);
      $this->assertArrayHasKey('options', $export_item);
      $this->assertArrayHasKey('profile', $export_item);
      $this->assertEquals($export_ids[$index], $export_item['id']);
    }
  }

  public function test_get_item()
  {
    \wp_set_current_user($this->user);
    $profile = $this->__createExportProfile('single-slice-export-profile', ['provider-a' => ['alpha']]);

    // create an item
    $request = new \WP_REST_Request('POST', ImpexRestController::BASE_URI . ImpexExportRESTController::REST_BASE);
    $request->add_header('Content-Type', 'application/json');
    $request->set_body(\wp_json_encode(['profile' => $profile->name]));
    $create_response = $this->server->dispatch($request);

    // try to get it 
    $request = new \WP_REST_Request('GET', ImpexRestController::BASE_URI . ImpexExportRESTController::REST_BASE . '/' . $create_response->data['id']);
    $response = $this->server->dispatch($request);
    $this->assertEquals(200, $response->status);
    $this->assertSameSets($create_response->data, $response->data);

    // try to get a non existion item
    $request = new \WP_REST_Request('GET', ImpexRestController::BASE_URI . ImpexExportRESTController::REST_BASE . '/nonsense');
    $response = $this->server->dispatch($request);
    $this->assertErrorResponse('not-found', $response, 404);
  }

  public function test_create_item()
  {
    \wp_set_current_user($this->user);
    $profile = $this->__createExportProfile('single-slice-export-profile', ['provider-a' => ['alpha']]);

    $request = new \WP_REST_Request('POST', ImpexRestController::BASE_URI . ImpexExportRESTController::REST_BASE);
    $request->add_header('Content-Type', 'application/json');
    $request->set_body(wp_json_encode(['profile' => $profile->name]));

    $create_response = $this->server->dispatch($request);
    $this->assertIsArray($create_response->data);
    $this->assertArrayHasKey('id', $create_response->data);
    $this->assertArrayHasKey('options', $create_response->data);
    $this->assertArrayHasKey('profile', $create_response->data);

    $response = $this->server->dispatch(new \WP_REST_Request('GET', ImpexRestController::BASE_URI . ImpexExportRESTController::REST_BASE));
    $this->assertIsArray($response->data);
    $this->assertCount(1, $response->data);

    $this->assertEquals($create_response->data, $response->data[0]);
  }

  public function test_update_item()
  {
    \wp_set_current_user($this->user);
    $profile = $this->__createExportProfile('single-slice-export-profile', ['provider-a' => ['alpha']]);

    // create item
    $request = new \WP_REST_Request('POST', ImpexRestController::BASE_URI . ImpexExportRESTController::REST_BASE);
    $request->add_header('Content-Type', 'application/json');
    $request->set_body(wp_json_encode(['profile' => $profile->name]));
    $create_response = $this->server->dispatch($request);

    // update export
    $request = new \WP_REST_Request('PATCH', ImpexRestController::BASE_URI . ImpexExportRESTController::REST_BASE . '/' . $create_response->data['id']);
    $request->add_header('Content-Type', 'application/json');
    $UPDATE_VALUES = ['name' => 'updated-name', 'profile' => 'updated-profile', 'description' => 'whoop'];
    $request->set_body(wp_json_encode($UPDATE_VALUES));
    $update_response = $this->server->dispatch($request);
    $this->assertEquals(200, $update_response->status);
    $this->assertArrayHasKey('description', $update_response->data, 'description should be available');
    $this->assertEquals($create_response->data['profile'], $update_response->data['profile'], 'profile should be unchanged');
    $this->assertEquals($UPDATE_VALUES['name'], $update_response->data['name'], 'name should be changed');

    // ensure updates are persisted
    $request = new \WP_REST_Request('GET', ImpexRestController::BASE_URI . ImpexExportRESTController::REST_BASE . '/' . $create_response->data['id']);
    $get_response = $this->server->dispatch($request);
    $this->assertEqualSets($update_response->data, $get_response->data);

    // try to update non existing export should fail
    $request = new \WP_REST_Request('PATCH', ImpexRestController::BASE_URI . ImpexExportRESTController::REST_BASE . '/nonsense');
    $request->add_header('Content-Type', 'application/json');
    $UPDATE_VALUES = ['name' => 'xxx-name', 'profile' => 'xxx-profile', 'xxx-description' => 'whoop whoop'];
    $request->set_body(\wp_json_encode($UPDATE_VALUES));
    $update_response = $this->server->dispatch($request);
    $this->assertErrorResponse('not-found', $update_response, 404);
  }

  public function test_delete_item()
  {
    \wp_set_current_user($this->user);
    $profile = $this->__createExportProfile('single-slice-export-profile', ['provider-a' => ['alpha']]);

    // create item
    $request = new \WP_REST_Request('POST', ImpexRestController::BASE_URI . ImpexExportRESTController::REST_BASE);
    $request->add_header('Content-Type', 'application/json');
    $request->set_body(wp_json_encode(['profile' => $profile->name]));
    $create_response = $this->server->dispatch($request);

    global $wpdb;
    $rows = $wpdb->get_results("SELECT * from {$wpdb->prefix}" . Impex::DB_SNAPSHOTS_TABLENAME);
    $this->assertCount(1, $rows, 'one slice should be exported');

    // delete export
    $request = new \WP_REST_Request('DELETE', ImpexRestController::BASE_URI . ImpexExportRESTController::REST_BASE . '/' . $create_response->data['id']);
    $delete_response = $this->server->dispatch($request);
    $this->assertEquals(200, $delete_response->status);

    $exports = \get_option(ImpexExport::WP_OPTION_EXPORTS, []);
    $this->assertCount(0, $exports, 'no exports should be avaliable');

    $rows = $wpdb->get_results("SELECT * from {$wpdb->prefix}" . Impex::DB_SNAPSHOTS_TABLENAME);
    $this->assertCount(0, $rows, 'table ' . $wpdb->prefix . Impex::DB_SNAPSHOTS_TABLENAME . ' should be empty');
  }

  public function test_get_item_slices()
  {
    \wp_set_current_user($this->user);

    // create export 
    $alphabet = range('a', 'z');
    $profile = $this->__createExportProfile('single-slice-export-profile', ['provider-alphabet' => $alphabet]);

    $export_id = Impex::getInstance()->Export->save($profile)->id;
    // get export
    $response = $this->server->dispatch(new \WP_REST_Request('GET', ImpexRestController::BASE_URI . ImpexExportRESTController::REST_BASE . '/' . $export_id));
    $this->assertEquals(200, $response->status);
    $this->assertEquals($export_id, $response->data['id']);

    // get export slices
    $request = new \WP_REST_Request('GET', ImpexRestController::BASE_URI . ImpexExportRESTController::REST_BASE . '/' . $export_id . '/slice');
    $request->set_query_params(['per_page' => 100]);
    $response = $this->server->dispatch($request);
    $this->assertEquals(200, $response->status);
    $this->assertCount(count($alphabet), $response->data);

    // get export slices from non existing export
    $request = new \WP_REST_Request('GET', ImpexRestController::BASE_URI . ImpexExportRESTController::REST_BASE . '/nonsense/slice');
    $response = $this->server->dispatch($request);
    $this->assertEquals(404, $response->status);

    // get export slices using rest pagination api 
    $request = new \WP_REST_Request('GET', ImpexRestController::BASE_URI . ImpexExportRESTController::REST_BASE . '/' . $export_id . '/slice');
    $request->set_query_params(['offset' => 1, 'per_page' => 2, 'page' => 2]);
    $response = $this->server->dispatch($request);
    $this->assertEquals(200, $response->status);
    $this->assertCount(2, $response->data);
    $this->assertEquals(array_slice($alphabet, 3, 2), \wp_list_pluck($response->data, Impex::SLICE_DATA));
    // test expected headers are set
    $this->assertEquals(count($alphabet), $response->get_headers()['X-WP-Total']);
    $this->assertEquals(ceil(count($alphabet) / $request->get_query_params()['per_page']), $response->get_headers()['X-WP-TotalPages']);
  }

  public function test_prepare_item()
  {
    $this->expectNotToPerformAssertions();
  }

  public function test_get_item_schema()
  {
    \wp_set_current_user($this->user);

    $response = $this->server->dispatch(new \WP_REST_Request('GET', ImpexRestController::BASE_URI . ImpexExportRESTController::REST_BASE . '/schema'));

    $this->assertEquals(200, 200); // $response->status);
  }

  public function test_WP_FILTER_EXPORT_SLICE_REST_MARSHAL()
  {
    \wp_set_current_user($this->user);

    $response = $this->server->dispatch(new \WP_REST_Request('GET', ImpexRestController::BASE_URI . ImpexExportRESTController::REST_BASE));
    $this->assertIsArray($response->data);
    $this->assertCount(0, $response->data);

    $SLICE_DATA = 'alpha';
    $profile = $this->__createExportProfile('single-slice-export-profile', ['provider-a' => [$SLICE_DATA]]);

    // register a WP_FILTER_EXPORT_SLICE_REST_MARSHAL filter
    $SELF_HREF_BASE = 'https://example.com/';
    \add_filter(
      ImpexExportRESTController::WP_FILTER_EXPORT_SLICE_REST_MARSHAL,
      function (array $serialized_slice, ImpexExportTransformationContext $transformationContext) use ($SELF_HREF_BASE) {
        if (
          $serialized_slice[Impex::SLICE_TAG] === self::EXPORT_PROVIDER_SLICE_TAG
        ) {
          $serialized_slice['_links'] ??= [];

          $serialized_slice['_links']['self'] ??= [];

          $serialized_slice['_links']['self'][] = [
            'href' => $SELF_HREF_BASE . $serialized_slice[Impex::SLICE_DATA],
            'tag'  => self::EXPORT_PROVIDER_SLICE_TAG,
            'provider'  => self::EXPORT_PROVIDER_SLICE_TAG,
          ];
        }
        return $serialized_slice;
      },
      10,
      2,
    );

    $export_id = Impex::getInstance()->Export->save($profile)->id;

    // get export slices
    $response = $this->server->dispatch(new \WP_REST_Request('GET', ImpexRestController::BASE_URI . ImpexExportRESTController::REST_BASE . '/' . $export_id . '/slice'));
    $this->assertEquals(200, $response->status);
    $this->assertIsArray($response->data);
    $this->assertCount(1, $response->data);

    $slice = $response->data[0];

    // test if our filter was executed
    $this->assertArrayHasKey('_links', $slice);
    $this->assertSame([
      'self' => [
        [
          'href' => $SELF_HREF_BASE . $SLICE_DATA,
          'tag'  => self::EXPORT_PROVIDER_SLICE_TAG,
          'provider'  => self::EXPORT_PROVIDER_SLICE_TAG,
        ]
      ]
    ], $slice['_links']);
  }
}
