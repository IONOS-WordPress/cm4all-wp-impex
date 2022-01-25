<?php

namespace cm4all\wp\impex;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit();
}

$profile = Impex::getInstance()->Import->addProfile('all');
$profile->setDescription('Import everything');

// stupid php ... variable $provider needs to be defined before use statement in addProvider
$provider = null;

$provider = Impex::getInstance()->Import->addProvider('all', function (array $slice, array $options, ImpexImportTransformationContext $transformationContext) use (&$provider): bool {
  foreach (Impex::getInstance()->Import->getProviders() as $_provider) {
    if ($_provider === $provider) {
      continue;
    }

    if (call_user_func($_provider->callback, $slice, $options, $transformationContext)) {
      return true;
    }
  }

  return false;
});

$profile->addTask('main', $provider->name);
