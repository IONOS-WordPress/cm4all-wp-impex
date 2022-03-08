<?php

namespace cm4all\wp\impex\tests\phpunit;

use cm4all\wp\impex\AttachmentImporter;
use cm4all\wp\impex\ImpexExportRuntimeException;
use cm4all\wp\impex\Impex;
use cm4all\wp\impex\ImpexImport;
use cm4all\wp\impex\ImpexImportTransformationContext;
use cm4all\wp\impex\ImpexPart;

require_once ABSPATH . 'usr/local/impex-cli/impex-cli.php';

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
      ABSPATH . 'usr/local/impex-cli/impex-cli.php',
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

  function testInvalidoptions(): void
  {
    $result = impex_cli(
      '-overwrite',
      '/tmp/test-export.zip',
    );

    $this->assertEquals(1, $result['exit_code'], 'should fail because of misplaced options/arguments');

    // TODO: add username / password of created user to impex_cli
  }

  function testImport(): void
  {
    $rest_url = \get_rest_url();

    $rest_url = "http://wordpress/wp-json";
    $rest_url = "http://tests-wordpress/wp-json";

    $result = impex_cli(
      'import-profile',
      "-rest-url=$rest_url",
      "-verbose",
      // __DIR__ . '/fixtures/impex-cli/simple-snapshot',
    );

    $this->assertEquals('', $result['stderr'], 'stderr should be empty');
    $this->assertEquals(0, $result['exit_code'], 'should succeed');

    // TODO: add username / password of created user to impex_cli
  }
}
