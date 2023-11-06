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

function __AttachmentsExportProviderCallback(array $options, ImpexExportTransformationContext $transformationContext): \Generator
{
  $attachments = \get_posts(['post_type' => 'attachment', 'numberposts' => -1, 'post_status' => null, 'post_parent' => null]);

  foreach ($attachments as $attachment) {
    $mediaRelPath = substr(\get_attached_file($attachment->ID), strlen(\wp_get_upload_dir()['basedir']) + 1);

    $targetFile = $transformationContext->path . '/' . $mediaRelPath;

    global $wp_filesystem;
    \WP_Filesystem();

    // ensure target file directory exists
    $wp_filesystem->exists(dirname($targetFile)) || \wp_mkdir_p(dirname($targetFile));
    $successfulCopied = $wp_filesystem->copy(\get_attached_file($attachment->ID), $targetFile);

    yield [
      Impex::SLICE_TAG => AttachmentsExporter::SLICE_TAG,
      Impex::SLICE_VERSION => AttachmentsExporter::VERSION,
      Impex::SLICE_TYPE => Impex::SLICE_TYPE_RESOURCE,
      Impex::SLICE_META => [
        'name' => $attachment->post_title,
        Impex::SLICE_META_ENTITY => AttachmentsExporter::SLICE_META_ENTITY_ATTACHMENT,
        'options' => $options,
        'data' => (array)$attachment,
      ],
      Impex::SLICE_DATA => $mediaRelPath,
    ];
  }
}

/**
 * @TODO: convert to enum if enums once are available in PHP
 */
interface AttachmentsExporter
{
  const SLICE_TAG = 'attachment';
  const SLICE_META_ENTITY_ATTACHMENT = 'attachment';

  const PROVIDER_NAME = self::class;

  const VERSION = '1.0.0';
}

function __registerAttachmentsExportProvider()
{
  $provider = Impex::getInstance()->Export->addProvider(AttachmentsExporter::PROVIDER_NAME, __NAMESPACE__ . '\__AttachmentsExportProviderCallback');
  return $provider;
}

\add_action(
  Impex::WP_ACTION_REGISTER_PROVIDERS,
  __NAMESPACE__ . '\__registerAttachmentsExportProvider',
);

\add_action(
  'rest_api_init',
  function () {
    require_once __DIR__ . '/class-impex-export-rest-controller.php';

    \add_filter(
      ImpexExportRESTController::WP_FILTER_EXPORT_SLICE_REST_MARSHAL,
      function (array $serialized_slice, ImpexExportTransformationContext $transformationContext) {
        if (
          $serialized_slice[Impex::SLICE_TAG] === AttachmentsExporter::SLICE_TAG &&
          $serialized_slice[Impex::SLICE_META][Impex::SLICE_META_ENTITY] === AttachmentsExporter::SLICE_META_ENTITY_ATTACHMENT &&
          $serialized_slice[Impex::SLICE_TYPE] === Impex::SLICE_TYPE_RESOURCE
        ) {
          $serialized_slice['_links'] ??= [];

          $serialized_slice['_links']['self'] ??= [];

          $serialized_slice['_links']['self'][] = [
            'href' => $transformationContext->url . '/' . $serialized_slice[Impex::SLICE_DATA],
            'tag'  => AttachmentsExporter::SLICE_TAG,
            'provider'  => AttachmentsExporter::PROVIDER_NAME,
          ];
        }
        return $serialized_slice;
      },
      10,
      2,
    );
  },
);

\add_action(
  Impex::WP_ACTION_ENQUEUE_IMPEX_PROVIDER_SCRIPT,
  function ($client_asset_handle, $in_footer) {
    \cm4all\wp\impex\wp_enqueue_script(
      strtolower(str_replace('\\', '-', AttachmentsExporter::PROVIDER_NAME)),
      'build/wp.impex.extension.export.attachments.js',
      [$client_asset_handle, $client_asset_handle . '-debug'],
      in_footer: $in_footer
    );
  },
  10,
  2,
);
