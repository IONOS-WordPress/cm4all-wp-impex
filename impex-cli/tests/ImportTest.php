<?php

use cm4all\wp\impex\tests\phpunit\AbstractImpexCLITestCase;

use function cm4all\wp\impex\tests\phpunit\impex_cli;

require_once __DIR__ . "/abstract-impex-cli-testcase.php";
final class ImportTest extends AbstractImpexCLITestCase
{
  function testSimpleImport()
  {
    $this->assertEquals(1, 1,);
  }
}
