<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Core\Configuration\Option;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Set\ValueObject\DowngradeSetList;
use Rector\Set\ValueObject\DowngradeLevelSetList;

return static function (RectorConfig $rectorConfig): void {
  // get parameters
  $parameters = $rectorConfig->parameters();
  $parameters->set(Option::PATHS, [
    __DIR__ . '/dist/cm4all-wp-impex-php7.4.0'
  ]);
  $parameters->set(Option::PARALLEL, false);

  $parameters->set(Option::PHP_VERSION_FEATURES, PhpVersion::PHP_80);

  // Define what rule sets will be applied
  $rectorConfig->import(DowngradeLevelSetList::DOWN_TO_PHP_80);
  $rectorConfig->import(DowngradeSetList::PHP_80);
};
