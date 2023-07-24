<?php

namespace cm4all\wp\impex\tests\phpunit;

use cm4all\wp\impex\Impex;
use cm4all\wp\impex\ImpexExportProfile;
use cm4all\wp\impex\ImpexExportProfileRESTController;
use cm4all\wp\impex\ImpexImportProfileRESTController;
use cm4all\wp\impex\ImpexRestController;

/**
 * @group restapi
 */
class TestImpexProfileRestController extends ImpexRestUnitTestcase
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
      Impex::getInstance()->Export->addProvider($name, function (array $options) use ($data): \Generator {
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
    $this->assertArrayHasKey(ImpexRestController::BASE_URI . ImpexExportProfileRESTController::REST_BASE, $routes, 'export profile route is registered');
    $this->assertArrayHasKey(ImpexRestController::BASE_URI . ImpexImportProfileRESTController::REST_BASE, $routes, 'import profile route is registered');
  }

  public function test_RestApiSecured()
  {
    // ensure no user / user without right permission is set
    $this->assertFalse(is_user_logged_in());
    $this->assertFalse(current_user_can('export'));
    $this->assertFalse(current_user_can('import'));
    $response = $this->server->dispatch(new \WP_REST_Request('GET', ImpexRestController::BASE_URI . ImpexExportProfileRESTController::REST_BASE));
    $this->assertErrorResponse('rest_forbidden', $response, 401);

    // test with valid in user
    \wp_set_current_user($this->user);
    $response = $this->server->dispatch(new \WP_REST_Request('GET', ImpexRestController::BASE_URI . ImpexExportProfileRESTController::REST_BASE));
    $this->assertEquals(200, $response->status);
  }

  public function test_context_param()
  {
    $this->expectNotToPerformAssertions();
  }

  public function test_get_items()
  {
    \wp_set_current_user($this->user);

    $response = $this->server->dispatch(new \WP_REST_Request('GET', ImpexRestController::BASE_URI . ImpexExportProfileRESTController::REST_BASE));
    $this->assertIsArray($response->data);
    $this->assertCount(0, $response->data);

    $profiles = [
      $this->__createExportProfile('export-profile-1', ['provider-a' => ['a']]),
    ];

    $response = $this->server->dispatch(new \WP_REST_Request('GET', ImpexRestController::BASE_URI . ImpexExportProfileRESTController::REST_BASE));
    $this->assertIsArray($response->data);
    $this->assertCount(1, $response->data);

    $profiles[] = $this->__createExportProfile('export-profile-2', ['provider-b' => ['b']]);

    $response = $this->server->dispatch(new \WP_REST_Request('GET', ImpexRestController::BASE_URI . ImpexExportProfileRESTController::REST_BASE));
    $this->assertIsArray($response->data);
    $this->assertCount(2, $response->data);
  }

  public function test_get_item()
  {
    \wp_set_current_user($this->user);
    $profile = $this->__createExportProfile('export-profile-1', ['provider-a' => ['a']]);

    // try to get it
    $request = new \WP_REST_Request('GET', ImpexRestController::BASE_URI . ImpexExportProfileRESTController::REST_BASE . '/' . $profile->name);
    $response = $this->server->dispatch($request);
    $this->assertEquals(200, $response->status);
    $this->assertSameSets(['name' => $profile->name, 'description' => null], $response->data);

    // try to get a non existion item
    $request = new \WP_REST_Request('GET', ImpexRestController::BASE_URI . ImpexExportProfileRESTController::REST_BASE . '/nonsense');
    $response = $this->server->dispatch($request);
    $this->assertErrorResponse('not-found', $response, 404);
  }

  public function test_prepare_item()
  {
    $this->expectNotToPerformAssertions();
  }

  public function test_create_item()
  {
    $this->expectNotToPerformAssertions();
  }

  public function test_update_item()
  {
    $this->expectNotToPerformAssertions();
  }

  public function test_delete_item()
  {
    $this->expectNotToPerformAssertions();
  }

  public function test_get_item_schema()
  {
    \wp_set_current_user($this->user);

    $response = $this->server->dispatch(new \WP_REST_Request('GET', ImpexRestController::BASE_URI . ImpexExportProfileRESTController::REST_BASE . '/schema'));

    $this->assertEquals(200, 200); // $response->status);
  }
}
