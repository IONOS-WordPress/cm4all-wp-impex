<?php

namespace cm4all\wp\impex;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit();
}

require_once ABSPATH . '/wp-admin/includes/import.php';

require_once __DIR__ . '/interface-impex-named-item.php';
require_once __DIR__ . '/class-impex.php';

use cm4all\wp\impex\Impex;

function __WpOptionsImportProviderCallback(array $slice, array $options, ImpexImportTransformationContext $transformationContext): bool
{
  if ($slice[Impex::SLICE_TAG] === WpOptionsExporter::SLICE_TAG) {
    if ($slice[Impex::SLICE_META][Impex::SLICE_META_ENTITY] === WpOptionsExporter::SLICE_META_ENTITY_WP_OPTIONS) {
      if ($slice[Impex::SLICE_VERSION] !== WpOptionsExporter::VERSION) {
        throw new ImpexImportRuntimeException(sprintf('Dont know how to import slice(tag="%s", version="%s") : unsupported version. current version is "%s"', WpOptionsExporter::SLICE_TAG, $slice[Impex::SLICE_VERSION], WpOptionsExporter::VERSION));
      }

      foreach ($slice[Impex::SLICE_DATA] as $wpOptionName => $wpOptionValue) {
        \update_option($wpOptionName, $wpOptionValue);
      }

      // remember updated option names 
      $updatedOptions = \get_option(ImpexImport::KEY_TRANSIENT_IMPORT_METADATA, []);
      $updatedOptions = array_merge($updatedOptions, array_keys($slice[Impex::SLICE_DATA]));
      $updatedOptions = array_unique($updatedOptions);
      \update_option(ImpexImport::KEY_TRANSIENT_IMPORT_METADATA, $updatedOptions);

      return true;
    }
  }

  return false;
}

interface WpOptionsImporter
{
  const PROVIDER_NAME = self::class;
}

function __registerWpOptionsImportProvider()
{
  $provider = Impex::getInstance()->Import->addProvider(WpOptionsImporter::PROVIDER_NAME, __NAMESPACE__ . '\__WpOptionsImportProviderCallback');
  return $provider;
}

\add_action(
  Impex::WP_ACTION_REGISTER_PROVIDERS,
  __NAMESPACE__ . '\__registerWpOptionsImportProvider',
);
