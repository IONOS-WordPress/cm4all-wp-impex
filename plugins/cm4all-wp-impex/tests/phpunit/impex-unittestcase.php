<?php

namespace cm4all\wp\impex\tests\phpunit;

use cm4all\wp\impex\Impex;
use cm4all\wp\impex\ImpexExportTransformationContext;

abstract class ImpexUnitTestcase extends \WP_UnitTestCase
{
  public function setUp()
  {
    /*
		 * When running core tests, ensure that post types and taxonomies
		 * are reset for each test.
		 */
    if (!defined('WP_RUN_CORE_TESTS')) {
      define('WP_RUN_CORE_TESTS', true);
    }

    parent::setUp();

    self::_resetImpex();
  }

  /**
   * Triggers the ImpEx actions for provider and profile registration so that all by default loaded prviders and profiles will be available.
   *
   * Regularly the Implex plugin will trigger the actions
   * - Impex::WP_ACTION_REGISTER_PROVIDERS
   * - Impex::WP_ACTION_REGISTER_PROFILES
   * automatically.
   *
   * But ImpexUnitTestcase->setUp resets all registered providers/profiles for convenience.
   *
   * So we need to trigger the registration actions again in rare cases
   */
  protected function __invokeImpexActions(): void
  {
    // trigger Impex::WP_ACTION_REGISTER_PROVIDERS to execute profile registration
    \do_action(Impex::WP_ACTION_REGISTER_PROVIDERS);
    // trigger Impex::WP_ACTION_REGISTER_PROFILES to execute provider registration
    \do_action(Impex::WP_ACTION_REGISTER_PROFILES);
  }

  function createImpexExportTransformationContextMock($options = []): ImpexExportTransformationContext
  {
    if (!Impex::getInstance()->Export->hasProfile('dummy_profile')) {
      Impex::getInstance()->Export->addProfile('dummy_profile');
    };

    if (\username_exists('me') === false) {
      \wp_set_current_user(self::factory()->user->create(['role' => 'administrator', 'user_login' => 'me']));
    }

    return new ImpexExportTransformationContext(
      profile_name: 'dummy_profile',
      options: $options,
    );
  }

  static function _resetImpex()
  {
    // cleanup registered extensions before running test function
    $reset = function () {
      self::$instance = null;
    };

    // https://stackoverflow.com/questions/20334355/how-to-get-protected-property-of-object-in-php/44361579#44361579
    $reset->call(Impex::getInstance());
    Impex::getInstance();
  }

  public function tearDown()
  {
    parent::tearDown();

    global $wp_filesystem;
    \WP_Filesystem();

    if ($wp_filesystem->exists(\wp_get_upload_dir()['basedir'] . '/impex/export')) {
      $wp_filesystem->rmdir(path: \wp_get_upload_dir()['basedir'] . '/impex/export', recursive: true);
    }
  }
}
