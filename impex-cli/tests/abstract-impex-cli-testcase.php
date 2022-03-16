<?php

namespace cm4all\wp\impex\tests\phpunit;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../impex-cli.php';

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
      realpath(__DIR__ . '/../impex-cli/impex-cli.php'),
      $operation,
      '-rest-url=http://host.docker.internal:8889/wp-json',
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

class AbstractImpexCLITestCase extends TestCase
{
  function setUp(): void
  {
    parent::setUp();
  }

  function tearDown(): void
  {
    parent::tearDown();
  }
}
