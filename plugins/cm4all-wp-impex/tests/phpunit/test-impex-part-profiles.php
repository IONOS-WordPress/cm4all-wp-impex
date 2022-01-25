<?php

namespace cm4all\wp\impex\tests\phpunit;

use cm4all\wp\impex\Impex;

class TestImpexPartProfiles extends ImpexUnitTestcase
{
  function testExportProfileAddTask(): void
  {
    $Export = Impex::getInstance()->Export;

    $pagesProvider = $Export->addProvider('pages', function () {
    });

    $profile = $Export->addProfile('default');

    $profile->addTask('export_pages', $pagesProvider->name);

    $this->assertNotNull($profile->getTask('export_pages'));
  }

  function testExportProfileAddTaskWithInvalidProviderShouldFail(): void
  {
    $Export = Impex::getInstance()->Export;

    $pagesProvider = $Export->addProvider('pages', function () {
    });

    $profile = $Export->addProfile('default');

    $this->expectException(\TypeError::class);
    $profile->addTask('export_pages', 'not-existent-export-provider');
  }

  /**
   * @TODO: implementation needed
   * @doesNotPerformAssertions
   */
  function testmoveTask(): void
  {
    //$this->markTestIncomplete('TODO: implementation');
  }
}
