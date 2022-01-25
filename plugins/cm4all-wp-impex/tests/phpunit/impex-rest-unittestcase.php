<?php

namespace cm4all\wp\impex\tests\phpunit;

use cm4all\wp\impex\Impex;

abstract class ImpexRestUnitTestcase extends \WP_Test_REST_Controller_Testcase
{
  public function setUp()
  {
    parent::setUp();

    ImpexUnitTestcase::_resetImpex();

    /** @var WP_REST_Server $wp_rest_server */
    global $wp_rest_server;
    $this->server = $wp_rest_server;
  }
}
