<?php

namespace cm4all\wp\impex;

use WPN_Helper;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit();
}

require_once __DIR__ . '/class-impex-part.php';
require_once __DIR__ . '/class-impex-import-provider.php';
require_once __DIR__ . '/class-impex-import-profile.php';
require_once __DIR__ . '/class-impex-import-runtime-exception.php';
abstract class ImpexImport extends ImpexPart
{
  const WP_OPTION_IMPORTS = 'impex_imports';

  // meta key attached to all imported documents 
  // with the original ID value of the imported post
  // - will be deleted after final consume() call
  // can be used to remap imported content referenced in other documents
  // its even applied to imported nav_menu(s) in wp_terms
  // (key is prefixed with underscore to hide it in metaboxes)
  const META_KEY_OLD_ID = '_cm4all_meta_key_old_id';

  // triggered when all chunks was successfully imported
  const EVENT_IMPORT_END = 'cm4all_wp_import_end';

  // triggered when a chunk was successfully imported
  const EVENT_IMPORT_CHUNK_END = 'cm4all_wp_import_chunk_end';

  const WP_FILTER_PROFILES = 'impex_import_filter_profiles';

  // post, media, block pattern, nav_menu an reusable block items
  const OPTION_CLEANUP_CONTENTS = 'impex-import-option-cleanup_contents';
  const OPTION_CLEANUP_CONTENTS_DEFAULT = false;

  protected function _createProvider(string $name, callable $cb): ImpexImportProvider
  {
    return new class($name, $cb) extends ImpexImportProvider
    {
      public function __construct($name, $cb)
      {
        parent::__construct($name, $cb);
      }
    };
  }

  protected function _createProfile(string $name, ImpexPart $context): ImpexImportProfile
  {
    return new class($name, $context) extends ImpexImportProfile
    {
      public function __construct($name, $context)
      {
        parent::__construct($name, $context);
      }
    };
  }

  function _upsert_slice(string $snapshot_id, int $position, array $slice): bool
  {
    $json = json_encode($slice);
    if ($json === false) {
      throw new ImpexExportRuntimeException(sprintf('failed to encode slice to json : %s(=%s)', json_last_error(), json_last_error_msg()));
    }

    /** @var wpdb */
    global $wpdb;

    $data = [
      'position' => $position,
      'snapshot_id' => $snapshot_id,
      'slice' => $json,
    ];

    $existing_id = $wpdb->get_var(
      $wpdb->prepare("SELECT DISTINCT id from {$this->_db_chunks_tablename} WHERE snapshot_id=%s and position=%d", $snapshot_id, $position)
    );

    if ($existing_id !== null) {
      $data['id'] = $existing_id;
    }

    return $wpdb->replace(
      $this->_db_chunks_tablename,
      $data,
    );
  }

  function create(ImpexImportProfile $profile, array $options = [], string $name = '',  string $description = ''): ImpexImportTransformationContext
  {
    $transformationContext = new ImpexImportTransformationContext(
      profile_name: $profile->name,
      name: $name,
      description: $description,
      options: $options,
    );

    $imports = \get_option(self::WP_OPTION_IMPORTS, []);

    $imports[] = $transformationContext->jsonSerialize();

    \update_option(self::WP_OPTION_IMPORTS, $imports);

    return $transformationContext;
  }

  /**
   * @return Generator|array[]
   */
  function get_slices(string $snapshot_id, int $limit = PHP_INT_MAX, int $offset = 0): \Generator
  {
    global $wpdb;

    $rows = $wpdb->get_results(
      $wpdb->prepare("SELECT * from {$this->_db_chunks_tablename} WHERE snapshot_id=%s ORDER BY position LIMIT %d OFFSET %d", $snapshot_id, $limit, $offset)
    );
    foreach ($rows as $row) {
      yield json_decode($row->slice, JSON_OBJECT_AS_ARRAY);
    }
  }

  /**
   * clean up temporary import metadata 
   */
  function _delete_old_key_metadata() : void {
    // delete existing import metadata from last run
    \delete_metadata( 'post', null, ImpexImport::META_KEY_OLD_ID, '', true );

    $terms = \get_terms([
      'hide_empty' => false, // also retrieve terms which are not used yet
      // 'taxonomy'  => 'nav_menu',
      'meta_query' => [
        ['key' => ImpexImport::META_KEY_OLD_ID, 'compare' => 'EXISTS', ],
      ],
    ]);
    foreach ($terms as $term) {
      \delete_term_meta($term->term_id, ImpexImport::META_KEY_OLD_ID);
    }
  }

  /**
   * @TODO: makes it sense to rename this function to aggregate or reduce ? 
   * 
   * @return array[] return uncomsumed slices
   */
  function consume(ImpexImportTransformationContext $transformationContext, int $limit = PHP_INT_MAX, int $offset = 0): array
  {
    $unconsumed_slices = [];

    $options = $transformationContext->options;
    $profile = $transformationContext->profile;

    // do clean up before importing first slices
    if($offset===0) {
      if(($options[self::OPTION_CLEANUP_CONTENTS] ?? false) == true) {
        $menus = \wp_get_nav_menus(['fields' => 'ids' ]);
        foreach ($menus as $menu) {
          \wp_delete_nav_menu( $menu);
        }

        $postsToDelete= \get_posts( ['post_type'=>['page', 'post', 'wp_block'],'numberposts'=>-1, 'fields' => 'ids'] );
        foreach ($postsToDelete as $postToDelete) {
          \wp_delete_post( $postToDelete, true );
        }

        $attachmentsToDelete= \get_posts( ['post_type'=>'attachment','numberposts'=>-1,'fields' => 'ids'] );
        foreach ($attachmentsToDelete as $attachmentToDelete) {
          \wp_delete_attachment($attachmentToDelete, true);
        }
      } else {
        $this->_delete_old_key_metadata();
      }
    }

    foreach ($this->get_slices($transformationContext->id, $limit, $offset) as $slice) {
      $consumed = false;

      foreach ($profile->getTasks() as $task) {
        if ($task->disabled) {
          continue;
        }

        $_options = self::_computeOptions($task, $options);

        $consumed = call_user_func($task->provider->callback, $slice, $_options, $transformationContext);

        if ($consumed) {
          break;
        }
      }

      if (!$consumed) {
        $unconsumed_slices[] = $slice;
      }
    }

    $profile->events(self::EVENT_IMPORT_CHUNK_END)($transformationContext, [
      'unconsumed_slices' => &$unconsumed_slices,
      'limit' => $limit,
      'offset' => $offset,
    ]);

    global $wpdb;
    $sliceCount = $wpdb->get_var(
      $wpdb->prepare("SELECT COUNT(*) from {$this->_db_chunks_tablename} WHERE snapshot_id=%s", $transformationContext->id)
    );
    // if we consumed the last chunk of slices
    if($sliceCount <= $offset + $limit) {
      // fetch imported terms
      $imported_terms = \get_terms([
        'hide_empty' => false, // also retrieve terms which are not used yet
        // 'taxonomy'  => array_keys(get_taxonomies()), //'nav_menu',
        'meta_query' => [
          ['key' => ImpexImport::META_KEY_OLD_ID, 'compare' => 'EXISTS', ],
        ],
      ]);

      // fetch imported posts
      $imported_posts = \get_posts([
        'post_type' => \get_post_types(),
        'meta_query' => [
          ['key' => ImpexImport::META_KEY_OLD_ID, 'compare' => 'EXISTS', ],
        ],
      ]);

      $imported = [ 
        'terms' => &$imported_terms,
        'posts' => &$imported_posts,
      ];

      $this->_backfill_ids($imported);

      $profile->events(self::EVENT_IMPORT_END)($transformationContext, $imported);

      $this->_delete_old_key_metadata();
    }

    return $unconsumed_slices;
  }

  function _backfill_ids(array &$imported) : void {
    // $first_taxonomy_old_id = (int)array_shift(\get_term_meta($imported_terms[0]->term_id, ImpexImport::META_KEY_OLD_ID));
    // $first_taxonomy_id = (int)$imported_posts[0]->_cm4all_meta_key_old_id;

    list('terms' => $terms, 'posts' => $posts) = $imported;

    /*
    @TODO:

    use blog title, blog description and other reimported common wp_options

    wp_option site_icon	references the site logo by attachment id

    wp_option site_logo	references the site logo by attachment id

    select * from wp_options where option_name like 'theme_mod%'\G;
    nav menu id must be adjusted in theme mods.

    $locations = get_theme_mod( 'nav_menu_locations' );
    https://wordpress.stackexchange.com/questions/124658/setting-a-default-theme-location-when-creating-a-menu

    select * from wp_options where option_name like 'theme_mod%'\G;

    https://digitalzoomstudio.net/2017/07/31/where-to-find-menus-in-wordpress-database/

    $locations = \get_theme_mod( 'nav_menu_locations' );
        $locations['primary-menu'] = 10;
        \set_theme_mod('nav_menu_locations', $locations);

    select * from wp_terms AS t LEFT JOIN wp_term_taxonomy AS tt  ON tt.term_id = t.term_id;

    old ids als _impex_old_id wärend des imports speichern und danach wieder löschen
    https://developer.wordpress.org/reference/functions/add_post_meta/
    https://developer.wordpress.org/plugins/metadata/managing-post-metadata/#hidden-custom-fields

    can also be accessed using a the WP_POst_Object magic __get getter 
    <?php echo $post->some_meta_field; ?>
    https://since1979.dev/wordpress-access-post-meta-fields-through-wp-post/
    */
  }

  function update(string $snapshot_id, array $data): array|bool
  {
    $imports = \get_option(self::WP_OPTION_IMPORTS, []);
    foreach ($imports as &$import) {
      if ($import['id'] === $snapshot_id) {
        foreach ($data as $key => $value) {
          // prevent updating 'id', 'options', 'profile', 'user', 'created'
          if (!in_array($key, ['id', 'options', 'profile', 'user', 'created'])) {
            if ($value === null) {
              unset($import[$key]);
            } else {
              $import[$key] = $value;
            }
          }
        }

        \update_option(self::WP_OPTION_IMPORTS, $imports);

        return $import;
      }
    };

    return false;
  }

  function remove(string $snapshot_id): bool|array
  {
    $imports = \get_option(self::WP_OPTION_IMPORTS, []);
    foreach ($imports as $index => $import) {
      if ($import['id'] === $snapshot_id) {
        $transformationContext = ImpexImportTransformationContext::fromJson($import);

        global $wpdb;
        global $wp_filesystem;

        \WP_Filesystem();

        // remove matching export table rows
        $rowsDeleted = $wpdb->delete($this->_db_chunks_tablename, ['snapshot_id' => $snapshot_id,]);
        if ($rowsDeleted === false) {
          throw new ImpexExportRuntimeException(sprintf('failed to delete jsonized slices from database : %s', $wpdb->last_error));
        }

        // remove export specific uploads directory
        if ($wp_filesystem->exists($transformationContext->path)) {
          $wp_filesystem->rmdir($transformationContext->path, true);
        }

        $removedItems = array_splice($imports, $index, 1);
        \update_option(self::WP_OPTION_IMPORTS, $imports);

        return $removedItems[0];
      }
    };

    return false;
  }
}
