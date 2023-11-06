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

/**
 * @TODO: we can declare the contents of an array (https://bleepcoder.com/plugin-php/617134322/broken-phpdoc-for-closures)
 * // @param array{a: int, b: string} $bar
 */
function __AttachmentImportProviderCallback(array $slice, array $options, ImpexImportTransformationContext $transformationContext): bool
{
  if ($slice[Impex::SLICE_TAG] === AttachmentsExporter::SLICE_TAG) {
    if (($slice[Impex::SLICE_TYPE] === Impex::SLICE_TYPE_RESOURCE) && $slice[Impex::SLICE_META][Impex::SLICE_META_ENTITY] === AttachmentsExporter::SLICE_META_ENTITY_ATTACHMENT) {
      if ($slice[Impex::SLICE_VERSION] !== AttachmentsExporter::VERSION) {
        throw new ImpexImportRuntimeException(sprintf('Dont know how to import slice(tag="%s", version="%s") : unsupported version. current version is "%s"', AttachmentsExporter::SLICE_TAG, $slice[Impex::SLICE_VERSION], AttachmentsExporter::VERSION));
      }

      $attachmentImporter = new __AttachmentImporter($slice, $options, $transformationContext);
      $attachmentImporter();

      return true;
    }
  }

  return false;
}

interface AttachmentImporter
{
  const PROVIDER_NAME = self::class;

  const WP_FILTER_IMPORT_REST_SLICE_UPLOAD_FILE = "AttachmentImporter";

  const REST_API_ENDPOINT_UPDATE_METADATA = ImpexImportRESTController::REST_BASE . '/attachment/update-metadata';

  const OPTION_OVERWRITE = 'wp-attachment-import-option-overwrite';
  const OPTION_OVERWRITE_DEFAULT = true;

  // optional slice meta property of type array of string
  // if set in a slice, the imported attachment replace the references
  // of this property in the content of all posts / pages
  const SLICE_META_POST_REFERENCES = 'impex:post-references';
}

function __registerAttachmentImportProvider()
{
  $provider = Impex::getInstance()->Import->addProvider(AttachmentImporter::PROVIDER_NAME, __NAMESPACE__ . '\__AttachmentImportProviderCallback');
  return $provider;
}

\add_action(
  Impex::WP_ACTION_REGISTER_PROVIDERS,
  __NAMESPACE__ . '\__registerAttachmentImportProvider',
);

class __AttachmentImporter
{
  protected $url_remap = [];

  function __construct(protected array $slice, protected array $options, protected ImpexImportTransformationContext $transformationContext)
  {
  }

  protected function delete_existing_attachments_matching_upload()
  {
    $file_name = basename(parse_url($this->slice[Impex::SLICE_META]['data']['guid'], PHP_URL_PATH));

    //$uploads = \wp_upload_dir($this->slice[Impex::SLICE_META]['post_date']);
    $post_date = $this->slice[Impex::SLICE_META]['data']['post_date'];
    $uploads = \wp_upload_dir($post_date);
    if (!($uploads && false === $uploads['error'])) {
      return;
    }

    $url = $uploads['url'] . "/$file_name";

    global $wpdb;

    foreach ($wpdb->get_col($wpdb->prepare("select ID from wp_posts where post_type='attachment' and guid =%s", $url)) as $ID) {
      $success = \wp_delete_attachment($ID, true);
      if ($success == false || $success == null) {
        throw new ImpexImportRuntimeException(sprintf('import attachment : failed to remove existing attachment(ID="%s") referencing with same attachment url(="%s")', $ID, $url));
      }
    }
  }

  function __invoke()
  {
    $overwrite = $this->options[AttachmentImporter::OPTION_OVERWRITE] ?? AttachmentImporter::OPTION_OVERWRITE_DEFAULT;
    if ($overwrite) {
      // ensure existing attachments referencing same file are removed before inserting ours
      $this->delete_existing_attachments_matching_upload();
    }

    $post = $this->slice[Impex::SLICE_META]['data'];
    $url = $post['guid'] ?? null;
    if ($url === null) {
      $path = $this->slice[Impex::SLICE_DATA];
      $fileExtension = pathinfo($path, PATHINFO_EXTENSION);
      $url = './' . \sanitize_title($post['post_title'] ?? pathinfo($path, PATHINFO_FILENAME)) . '.' . $fileExtension;
    }

    /*
      code more or less duplicated from wordpress-importer function process_attachment
      https://github.com/WordPress/wordpress-importer/blob/e05f678835c60030ca23c9a186f50999e198a360/src/class-wp-import.php#L1009
    */

    $upload = $this->fetch_remote_file($url, $post, $this->slice);
    if (\is_wp_error($upload)) {
      return $upload;
    }

    $post['guid'] = $upload['url'];

    $old_id = $post['ID'];

    unset($post['ID']);

    // if post_mime_type is not set the media will not appear correctly resized within media uploader
    if (!isset($post['post_mime_type'])) {
      $post_mime_type = \wp_get_image_mime($upload['file']);

      if (!$post_mime_type) {
        $post_mime_type = mime_content_type(basename($upload['file']));
      }

      if (is_string($post_mime_type)) {
        $post['post_mime_type'] = $post_mime_type;
      }
    }

    // as per wp-admin/includes/upload.php
    $post_id = \wp_insert_attachment($post, $upload['file']);

    // register old id as postmeta for later remapping
    \update_post_meta($post_id, ImpexImport::KEY_TRANSIENT_IMPORT_METADATA, $old_id, true);

    // // required if non admin user imports attachments
    // if (!function_exists('wp_crop_image')) {
    //   include(ABSPATH . 'wp-admin/includes/image.php');
    // }
    // if(!function_exists('wp_read_video_metadata')) {
    //   include(ABSPATH . 'wp-admin/includes/media.php');
    // }

    /*$attachment_invalid =*/
    // \wp_update_attachment_metadata($post_id, \wp_generate_attachment_metadata($post_id, $upload['file']));

    // register url callback to call by client side to update
    // attachment metadata step by step
    $basedir = \wp_upload_dir()['basedir'];
    $this->transformationContext->addCallback(
      // relative rest api url
      substr(AttachmentImporter::REST_API_ENDPOINT_UPDATE_METADATA, 1),
      'POST',
      [
        'post_id' => $post_id,
        'file' => substr($upload['file'], strlen($basedir)+1),
      ],
    );

    /*
    if ($attachment_invalid === false) {
      return new \WP_Error('wp_update_attachment_metadata', 'attachment metadata invalid');
    }
    */

    // remap resized image URLs, works by stripping the extension and remapping the URL stub.
    if (preg_match('!^image/!', $post['post_mime_type'])) {
      $parts = pathinfo($url);
      $name  = basename($parts['basename'], isset($parts['extension']) ? ".{$parts['extension']}" : ''); // PATHINFO_FILENAME in PHP 5.2

      $parts_new = pathinfo($upload['url']);
      $name_new  = basename($parts_new['basename'], isset($parts_new['extension']) ? ".{$parts_new['extension']}" : "");

      $this->url_remap[$parts['dirname'] . '/' . $name] = $parts_new['dirname'] . '/' . $name_new;
    }

    // @TODO: any change to do this AFTER all images are uploaded ?

    // replace default url_remap with SLICE_META_POST_REFERENCES if exists
    $meta_post_references = $this->slice[Impex::SLICE_META][AttachmentImporter::SLICE_META_POST_REFERENCES] ?? null;
    if (is_array($meta_post_references)) {
      $this->url_remap = [];
      foreach ($meta_post_references as $reference) {
        $this->url_remap[$reference] = $upload['url'];
      }
    }

    $this->backfill_attachment_urls();
  }

  /**
   * patched version of wordpress-importer fetch_remote_file()
   */
  function fetch_remote_file($url, $post, $slice)
  {
    // Extract the file name from the URL.
    $file_name = basename(parse_url($url, PHP_URL_PATH));

    $uploads = \wp_upload_dir($post['post_date'] ?? null);
    if (!($uploads && false === $uploads['error'])) {
      return new \WP_Error('upload_dir_error', $uploads['error']);
    }

    $file_name     = wp_unique_filename($uploads['path'], $file_name);
    $new_file      = $uploads['path'] . "/$file_name";

    // Copy the file to the uploads dir.
    copy(
      $this->transformationContext->path . '/' . $this->slice[Impex::SLICE_DATA],
      $new_file,
    );

    // Set correct file permissions.
    $stat  = stat(dirname($new_file));
    $perms = $stat['mode'] & 0000666;
    chmod($new_file, $perms);

    $upload = [
      'file'  => $new_file,
      'url'   => $uploads['url'] . "/$file_name",
      'type'  => $post['post_mime_type'],
      'error' => false,
    ];

    // keep track of the old and new urls so we can substitute them later
    $this->url_remap[$url]          = $upload['url'];
    $this->url_remap[$post['guid']] = $upload['url']; // r13735, really needed?
    /*
    // keep track of the destination if the remote url is redirected somewhere else
    if (isset($headers['x-final-location']) && $headers['x-final-location'] != $url) {
      $this->url_remap[$headers['x-final-location']] = $upload['url'];
    }
    */

    return $upload;
  }

  /**
   * patched version of wordpress-importer backfill_attachment_urls()
   * @see https://github.com/WordPress/wordpress-importer/blob/e05f678835c60030ca23c9a186f50999e198a360/src/class-wp-import.php#L1265
   */
  // @TODO: any change to do this AFTER all images are uploaded ?
  function backfill_attachment_urls()
  {
    global $wpdb;
    // make sure we do the shortest urls first, in case one is a substring of another
    uksort($this->url_remap, [&$this, 'cmpr_strlen']);

    foreach ($this->url_remap as $from_url => $to_url) {
      // remap urls in post_content
      // replace from_url => to_url and also for block attributes in json notation
      $wpdb->query($wpdb->prepare("UPDATE {$wpdb->posts} SET post_content = REPLACE( REPLACE(post_content, %s, %s), %s, %s)", $from_url, $to_url, json_encode($from_url), json_encode($to_url)));
      // remap enclosure urls
      $result = $wpdb->query($wpdb->prepare("UPDATE {$wpdb->postmeta} SET meta_value = REPLACE(meta_value, %s, %s) WHERE meta_key='enclosure'", $from_url, $to_url));
    }
  }

  // return the difference in length between two strings
  function cmpr_strlen($a, $b)
  {
    return strlen($a) - strlen($b);
  }
}

\add_action(
  'rest_api_init',
  function () {
    require_once __DIR__ . '/class-impex-import-rest-controller.php';
    \add_filter(
      ImpexImportRESTController::WP_FILTER_IMPORT_REST_SLICE_UPLOAD,
      function (array $slice, ImpexImportTransformationContext $transformationContext, \WP_REST_Request $request) {
        if (
          $slice[Impex::SLICE_TAG] === AttachmentsExporter::SLICE_TAG
        ) {
          $files = $request->get_file_params();
          if (!is_array($files)) {
            throw new \WP_Error('bad-request', __('Multipart file upload is missing in request', 'cm4all-wp-impex'), ['status' => 400]);
          }

          $attachmentFile = $files[AttachmentImporter::WP_FILTER_IMPORT_REST_SLICE_UPLOAD_FILE] ?? null;
          if ($attachmentFile === null) {
            throw new ImpexImportRuntimeException(sprintf('Multipart file upload "%s" is missing in request', AttachmentImporter::WP_FILTER_IMPORT_REST_SLICE_UPLOAD_FILE));
          }

          $to = $transformationContext->path . '/' . $slice[Impex::SLICE_DATA];
          $success = \wp_mkdir_p(dirname($to));
          $success = rename($attachmentFile["tmp_name"], $to);

          if ($success === null) {
            throw new ImpexImportRuntimeException(sprintf('Failed to move uploaded attachment(=%) to impex import directory : %s', $attachmentFile["tmp_name"], $to));
          }

          unset($files[AttachmentImporter::WP_FILTER_IMPORT_REST_SLICE_UPLOAD_FILE]);
          $request->set_file_params($files);
        }
        return $slice;
      },
      10,
      3,
    );

    \register_rest_route(ImpexImportRESTController::NAMESPACE, AttachmentImporter::REST_API_ENDPOINT_UPDATE_METADATA, [
      'methods'  => \WP_REST_Server::EDITABLE,
      /**
        * @param WP_REST_Request $request Full data about the request.
        * @return WP_Error|WP_REST_Response
        */
      'callback' => function($request) {
        $post_id = $request->get_param('post_id');
        $basedir = \wp_upload_dir()['basedir'];
        $file = $basedir . '/' . $request->get_param('file');

        // required if non admin user imports attachments
        if (!function_exists('wp_crop_image')) {
          include(ABSPATH . 'wp-admin/includes/image.php');
        }

        if(!function_exists('wp_read_video_metadata')) {
          include(ABSPATH . 'wp-admin/includes/media.php');
        }

        \wp_update_attachment_metadata($post_id, \wp_generate_attachment_metadata($post_id, $file));

        $response = new \WP_REST_Response(true, 200);
        return $response;
      },
      /**
        * @param WP_REST_Request $request Full data about the request.
        * @return WP_Error|WP_REST_Response
        */
      'permission_callback' => function($request) {
        // @TODO: check a more specific permission
        return \current_user_can('import');
      },
      /*
      'args'                => [],
      'schema' => [$this, 'get_public_item_schema'],
      */
    ]);
  },
);

\add_action(
  Impex::WP_ACTION_ENQUEUE_IMPEX_PROVIDER_SCRIPT,
  function ($client_asset_handle, $in_footer) {
    $HANDLE = strtolower(str_replace('\\', '-', AttachmentImporter::PROVIDER_NAME));
    \cm4all\wp\impex\wp_enqueue_script(
      $HANDLE,
      'build/wp.impex.extension.import.attachment.js',
      [$client_asset_handle, $client_asset_handle . '-debug'],
      $in_footer
    );

    \wp_add_inline_script(
      $HANDLE,
      sprintf('wp.impex.extension.import.attachment.WP_FILTER_IMPORT_REST_SLICE_UPLOAD_FILE=%s;', \wp_json_encode(AttachmentImporter::WP_FILTER_IMPORT_REST_SLICE_UPLOAD_FILE)),
    );
  },
  10,
  2,
);
