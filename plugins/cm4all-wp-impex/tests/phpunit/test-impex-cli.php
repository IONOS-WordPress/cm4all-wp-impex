<?php

namespace cm4all\wp\impex\tests\phpunit;

use cm4all\wp\impex\AttachmentImporter;
use cm4all\wp\impex\ImpexExportRuntimeException;
use cm4all\wp\impex\Impex;
use cm4all\wp\impex\ImpexImport;
use cm4all\wp\impex\ImpexImportTransformationContext;
use cm4all\wp\impex\ImpexPart;

require_once ABSPATH . 'impex-cli/impex-cli.php';


function impex_cli($operation, ...$args): array
{
  $retval = [
    'stderr' => '',
    'stdout' => '',
    'exit_code' => 0,
  ];

  ob_start();

  try {
    \cm4all\wp\impex\cli\main([
      ABSPATH . 'impex-cli/impex-cli.php',
      $operation,
      //  '-username=' . TestImpexCLI::IMPEX_USER_LOGIN,
      //  '-password=' . TestImpexCLI::IMPEX_USER_PASS,
      '-username=admin',
      '-password=password',
      ...$args,
    ]);
  } catch (\cm4all\wp\impex\cli\DieException $ex) {
    $retval['stderr'] = $ex->getMessage();
    $retval['exit_code'] = $ex->getCode();
  } finally {
    $retval['stdout'] = ob_get_clean();
  }

  return $retval;
}

class TestImpexCLI extends ImpexUnitTestcase
{
  // const IMPEX_USER_LOGIN = 'test-admin';
  // const IMPEX_USER_PASS = 'test-admin-pass';

  function setUp()
  {
    parent::setUp();

    // $all_plugins = array_keys(\get_plugins());
    // \activate_plugins($all_plugins);

    $this->set_permalink_structure('/%postname%/');

    // global $wpdb;
    // // crude but effective: make sure there's no residual data in the main tables
    // foreach (array('posts', 'postmeta', 'comments', 'terms', 'term_taxonomy', 'term_relationships', 'users', 'usermeta') as $table) {
    //   // phpcs:ignore WordPress.DB.PreparedSQL
    //   $wpdb->query("DELETE FROM {$wpdb->$table}");
    // }

    // /*
    //  CAVEAT : tables created using Wordpress install/upgrade mechanism 
    //  (https://codex.wordpress.org/Creating_Tables_with_Plugins) will not use 
    //  the wp phpunit filter making the table temporary. 

    //  => so we need to drop the table manually to have a consistent setup
    // */
    // global $wpdb;
    // $wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . Impex::DB_SNAPSHOTS_TABLENAME);

    // $this->user = $this->factory->user->create(['role' => 'administrator', 'user_login' => self::IMPEX_USER_LOGIN, 'user_pass' => self::IMPEX_USER_PASS]);
  }

  function tearDown()
  {
    parent::tearDown();

    // // in case of broken phpunit calls old uploads may exist within $ignored_files (populated in setUp)
    // // to ensure these will be cleaned up properly we force to forget about these intermediate file uploads
    // self::$ignore_files = [];

    // // remove all uploads
    // $this->remove_added_uploads();
  }

  function _testInvalidoptions(): void
  {
    $result = impex_cli(
      '-overwrite',
      '/tmp/test-export.zip',
    );

    $this->assertEquals(1, $result['exit_code'], 'should fail because of misplaced options/arguments');

    // TODO: add username / password of created user to impex_cli
  }

  function _testImportProfiles(): void
  {
    $active_plugins = \get_option('active_plugins');

    $permalink_structure = \get_option('permalink_structure',);

    // curl -v 'http://localhost:8888/wp-json/cm4all-wp-impex/v1/export/profile' -H 'accept: application/json' -H 'authorization: Basic YWRtaW46cGFzc3dvcmQ='
    $rest_url = 'http://tests-wordpress/wp-json/'; // cm4all_wordpress rewrites the rest url ... so we cannot use \get_rest_url();

    $result = impex_cli(
      'import-profile',
      "-rest-url=$rest_url",
      "-CURLOPT_VERBOSE",
      "-verbose",
      // __DIR__ . '/fixtures/impex-cli/simple-snapshot',
    );

    $this->assertEquals('', $result['stderr'], 'stderr should be empty');
    $this->assertEquals(0, $result['exit_code'], 'should succeed');

    // TODO: add username / password of created user to impex_cli
  }
}
