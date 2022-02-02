<?php

namespace cm4all\wp\impex;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit();
}

require_once ABSPATH . '/wp-admin/includes/export.php';

require_once __DIR__ . '/interface-impex-named-item.php';
require_once __DIR__ . '/class-impex.php';

use cm4all\wp\impex\Impex;

function __matchesSelector(string $wpOptionName, array|string $selectors): bool
{
  // normalize selector to array of selectors
  if (is_string($selectors)) {
    $selectors = [$selectors];
  }

  if (is_array($selectors)) {
    foreach ($selectors as $selector) {
      if (fnmatch($selector, $wpOptionName)) {
        return true;
      }
    }
  }

  return false;
}

function __WpOptionsExporterProviderCallback(array $options, ImpexExportTransformationContext $transformationContext): \Generator
{
  $selector = $options[WpOptionsExporter::OPTION_SELECTOR] ?? null;

  // ensure selector is valid
  if (!(is_array($selector) || is_string($selector))) {
    throw new ImpexExportRuntimeException(sprintf('dont know how to handle export option WpOptionsExporter::OPTION_SELECTOR(=%s)', json_encode($selector)));
  }

  $chunk_max_items = $options[WpOptionsExporter::OPTION_SLICE_MAX_ITEMS] ?? WpOptionsExporter::OPTION_SLICE_MAX_ITEMS_DEFAULT;
  $chunks = [];
  $current_chunk = [];
  foreach (\wp_load_alloptions() as $wpOptionName => $wpOptionValue) {
    if (__matchesSelector($wpOptionName, $selector)) {
      // CAVEAT: we cannot use $wpOptionValue since its not automagically deserialized
      // $wpOptionValue transports just the plain serialization string, so we need to utilize
      // \get_option to get the correct value
      // otherwise array and object values will not be coeectly exported 
      $current_chunk[$wpOptionName] = \get_option($wpOptionName);

      if (count($current_chunk) === $chunk_max_items) {
        $chunks[] = $current_chunk;
        $current_chunk = [];
      }
    }
  }

  if (count($current_chunk) > 0) {
    $chunks[] = $current_chunk;
  }

  foreach ($chunks as $chunk) {
    yield [
      Impex::SLICE_TAG => WpOptionsExporter::SLICE_TAG,
      Impex::SLICE_VERSION => WpOptionsExporter::VERSION,
      Impex::SLICE_META => [
        Impex::SLICE_META_ENTITY => WpOptionsExporter::SLICE_META_ENTITY_WP_OPTIONS,
        'options' => $options,
      ],
      Impex::SLICE_DATA => $chunk,
    ];
  }
}

/**
 * @TODO: convert to enum if enums once are available in PHP
 */
interface WpOptionsExporter
{
  const SLICE_TAG = 'wp-options';
  const SLICE_META_ENTITY_WP_OPTIONS = self::SLICE_TAG;

  const OPTION_SELECTOR = 'wp-options-export-option-selector';
  const OPTION_SLICE_MAX_ITEMS = 'wp-options-export-option-chunk-max-items';
  const OPTION_SLICE_MAX_ITEMS_DEFAULT = 50;

  const PROVIDER_NAME = self::class;

  const VERSION = '1.0.0';
}

function __registerWpOptionsExportProvider()
{
  $provider = Impex::getInstance()->Export->addProvider(WpOptionsExporter::PROVIDER_NAME, __NAMESPACE__ . '\__WpOptionsExporterProviderCallback');
  return $provider;
}

\add_action(
  Impex::WP_ACTION_REGISTER_PROVIDERS,
  __NAMESPACE__ . '\__registerWpOptionsExportProvider',
);
