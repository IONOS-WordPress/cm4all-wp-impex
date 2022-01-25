<?php

namespace cm4all\wp\impex\tests\phpunit;

require_once __DIR__ . '/../../../cm4all-wp-impex/inc/class-impex.php';

use cm4all\wp\impex\Impex;
use cm4all\wp\impex\ImpexExportProfile;
use cm4all\wp\impex\ImpexProfile;
use cm4all\wp\impex\tests\phpunit\ImpexUnitTestcase;

class TestImpexExportProfileCm4all extends ImpexUnitTestcase
{
  function setUp()
  {
    parent::setUp();

    \tests_add_filter('muplugins_loaded', function () {
      require_once ABSPATH . 'wp-content/plugins/complianz-gdpr/complianz-gpdr.php';
      require_once ABSPATH . 'wp-content/plugins/ninja-forms/ninja-forms.php';
      require_once ABSPATH . 'wp-content/plugins/ultimate-maps-by-supsystic/ums.php';
    });

    \activate_plugin('gutenberg/gutenberg.php');
    // \activate_plugin('cm4all-wordpress/plugin.php');
    \activate_plugin('cm4all-wp-impex/plugin.php');

    \activate_plugin('complianz-gdpr/complianz-gpdr.php');
    \activate_plugin('ninja-forms/ninja-forms.php');
    \activate_plugin('wp-content/plugins/ultimate-maps-by-supsystic/ums.php');

    // force reexecution of the import profle registration since we depend on the "all" import provider
    require __DIR__ . '/../../../cm4all-wp-impex/profiles/import-profile-all.php';

    require_once __DIR__ . '/../../plugin.php';

    // trigger Impex::WP_ACTION_REGISTER_PROVIDERS to execute profile registration
    \do_action(Impex::WP_ACTION_REGISTER_PROVIDERS);
    // trigger Impex::WP_ACTION_REGISTER_PROFILES to execute cm4all profile registration
    \do_action(Impex::WP_ACTION_REGISTER_PROFILES);
  }

  function test_Cm4allProfileAvailable()
  {
    $profile = Impex::getInstance()->Export->getProfile('impex-export-profile-example');
    $this->assertInstanceOf(ImpexExportProfile::class, Impex::getInstance()->Export->getProfile('impex-export-profile-example'), 'cm4all export profile should be available');
  }

  /**
   * @TODO: implementation needed
   * @doesNotPerformAssertions
   */
  function test_Cm4allProfileExport()
  {
    // $this->markTestSkipped('not implemented yet');
  }
}
