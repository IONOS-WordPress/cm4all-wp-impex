<?php

use cm4all\wp\impex\tests\phpunit\AbstractImpexCLITestCase;

use function cm4all\wp\impex\tests\phpunit\impex_cli;

require_once __DIR__ . "/abstract-impex-cli-testcase.php";
final class ImportTest extends AbstractImpexCLITestCase
{
  function testInvalidOptions()
  {
    $result = impex_cli(
      '-overwrite',
      '/tmp/test-export.zip',
    );

    $this->assertEquals(1, $result['exit_code'], 'should fail because of misplaced options/arguments');
  }

  function testImportEmptySnapshotDirectory()
  {
    $result = impex_cli(
      'import',
      '-profile=all',
      __DIR__ . '/fixtures/empty-snapshot',
    );

    $this->assertEquals(0, $result['exit_code'], 'should succeed');
  }

  function testSimpleImport()
  {
    $result = impex_cli(
      'import',
      '-profile=all',
      __DIR__ . '/fixtures/simple-import',
    );

    $this->assertEquals(0, $result['exit_code'], 'should succeed');
  }

  function testImportWithInvalidOptions()
  {
    $result = impex_cli(
     'import',
     '-options="impex-import-option-cleanup_contents" : true}',
      '-profile=all',
      __DIR__ . '/fixtures/simple-import',
    );

    $this->assertEquals(1, $result['exit_code'], 'should fail');
  }

  function testImportWithCleanupOption()
  {
    $result = impex_cli(
     'import',
     '-options={"impex-import-option-cleanup_contents" : true}',
      '-profile=all',
      __DIR__ . '/fixtures/simple-import',
    );

    $this->assertEquals(0, $result['exit_code'], 'should succeed');
  }
}
