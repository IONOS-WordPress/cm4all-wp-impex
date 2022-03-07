<?php

namespace cm4all\wp\impex\tests\phpunit\ImpexTransformationContext;

use cm4all\wp\impex\Impex;
use cm4all\wp\impex\ImpexTransformationContext;
use cm4all\wp\impex\ImpexExportTransformationContext;
use cm4all\wp\impex\ImpexImportTransformationContext;
use cm4all\wp\impex\ImpexRuntimeException;
use cm4all\wp\impex\tests\phpunit\ImpexUnitTestcase;
use RuntimeException;

class TestImpexTransformationContext extends ImpexUnitTestcase
{
  function testInvalidProfileNameShouldFail()
  {
    $this->expectException(ImpexRuntimeException::class);
    new ImpexExportTransformationContext(profile_name: 'my-profile');
  }

  function testInvalidUserLoginShouldFail()
  {
    Impex::getInstance()->Export->addProfile('my-profile');

    $this->expectException(ImpexRuntimeException::class);
    new ImpexExportTransformationContext(user_login: 'not-existing_user_login', profile_name: 'my-profile');
  }


  function testArgumentDerivation(): void
  {
    $exportProfile = Impex::getInstance()->Export->addProfile('my-profile');

    $user_id = self::factory()->user->create(array('role' => 'editor', 'user_login' => 'me'));
    \wp_set_current_user($user_id);

    $options = ['foo' => 'bar'];

    $context = new ImpexExportTransformationContext(profile_name: 'my-profile', options: $options);

    $this->assertInstanceOf(ImpexExportTransformationContext::class, $context,);
    $this->assertInstanceOf(ImpexTransformationContext::class, $context,);
    $this->assertNotInstanceOf(ImpexImportTransformationContext::class, $context,);

    $this->assertEqualSetsWithIndex(
      [
        'description' => '',
        'user' => \get_user_by('id', $user_id),
        'profile' => $exportProfile,
        'options' => $options,
      ],
      [
        'description' => $context->description,
        'user' => $context->user,
        'profile' => $context->profile,
        'options' => $context->options,
      ],
    );
    $this->assertStringStartsWith('Export ', $context->name);
    $this->assertStringStartsWith('/var/www/html/wp-content/uploads/impex/snapshots/', $context->path);
  }

  function testSerialization(): void
  {
    Impex::getInstance()->Export->addProfile('my-profile');

    $user_id = self::factory()->user->create(array('role' => 'editor', 'user_login' => 'me'));
    \wp_set_current_user($user_id);

    $options = ['foo' => 'bar'];

    $context = new ImpexExportTransformationContext(profile_name: 'my-profile', options: $options);

    $json = $context->jsonSerialize();

    $this->assertEqualSetsWithIndex(
      [
        'id' => $context->id,
        'name' => $context->name,
        'description' => $context->description,
        'created' => $context->created,
        'user' => $context->user->user_login,
        'profile' => $context->profile->name,
        'options' => $context->options,
      ],
      $json,
    );

    $clonedContext = ImpexExportTransformationContext::fromJson($json);

    $this->assertEquals((array)$context, (array)$clonedContext);
  }

  function testImportContext(): void
  {
    $user_id = self::factory()->user->create(array('role' => 'editor', 'user_login' => 'me'));
    \wp_set_current_user($user_id);

    Impex::getInstance()->Import->addProfile('my-import-profile');
    $context = new ImpexImportTransformationContext(profile_name: 'my-import-profile');

    $this->assertInstanceOf(ImpexImportTransformationContext::class, $context,);
    $this->assertStringStartsWith('Import ', $context->name);
    $this->assertStringStartsWith('/var/www/html/wp-content/uploads/impex/snapshots/', $context->path);

    $json = $context->jsonSerialize();

    $this->assertEqualSetsWithIndex(
      [
        'id' => $context->id,
        'name' => $context->name,
        'description' => $context->description,
        'created' => $context->created,
        'user' => $context->user->user_login,
        'profile' => $context->profile->name,
        'options' => $context->options,
      ],
      $json,
    );

    $clonedContext = ImpexImportTransformationContext::fromJson($json);

    $this->assertEquals((array)$context, (array)$clonedContext);
  }
}
