<?php

use cm4all\wp\impex\tests\phpunit\AbstractImpexCLITestCase;

use function cm4all\wp\impex\tests\phpunit\impex_cli;

require_once __DIR__ . "/abstract-impex-cli-testcase.php";

final class ImportProfileTest extends AbstractImpexCLITestCase
{
  function testImportProfiles()
  {
    // curl -v 'http://host.docker.internal:8889/wp-json/cm4all-wp-impex/v1/export/profile' -H 'accept: application/json' -H 'authorization: Basic YWRtaW46cGFzc3dvcmQ='

    $result = impex_cli(
      'import-profile',
      // '-CURLOPT_VERBOSE',
      // '-verbose',
      // __DIR__ . '/fixtures/impex-cli/simple-snapshot',
    );

    $this->assertEquals('', $result['stderr'], 'stderr should be empty');
    $this->assertEquals(0, $result['exit_code'], 'should succeed');
  }
}
