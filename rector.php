<?php

declare(strict_types=1);

use Rector\Core\Configuration\Option;
use Rector\Php74\Rector\Property\TypedPropertyRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Set\ValueObject\DowngradeSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Set\ValueObject\DowngradeLevelSetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
  // get parameters
  $parameters = $containerConfigurator->parameters();
  $parameters->set(Option::PATHS, [
    __DIR__ . '/dist/cm4all-wp-impex-php7.4.0'
  ]);

  $parameters->set(Option::PHP_VERSION_FEATURES, PhpVersion::PHP_80);

  // Define what rule sets will be applied
  $containerConfigurator->import(DowngradeLevelSetList::DOWN_TO_PHP_80);
  $containerConfigurator->import(DowngradeSetList::PHP_80);

  // get services (needed for register a single rule)
  // $services = $containerConfigurator->services();

  // register a single rule
  // $services->set(TypedPropertyRector::class);
};
