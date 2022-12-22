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

  // meta key attached to all imported documents / terms 
  // with the original ID value of the imported post
  // - will be deleted after final consume() call
  // can be used to remap imported content referenced in other documents
  // its even applied to imported nav_menu(s) in wp_terms
  // (key is prefixed with underscore to hide it in metaboxes)
  const KEY_TRANSIENT_IMPORT_METADATA = '_cm4all_KEY_TRANSIENT_IMPORT_METADATA';

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
  function _delete_transient_import_metadata() : void {
    // delete existing import metadata from last run
    \delete_metadata( 'post', null, self::KEY_TRANSIENT_IMPORT_METADATA, '', true );

    $term_ids = \get_terms([
      'fields' => 'ids',
      'hide_empty' => false, // also retrieve terms which are not used yet
      // 'taxonomy'  => 'nav_menu',
      'meta_query' => [
        ['key' => self::KEY_TRANSIENT_IMPORT_METADATA, 'compare' => 'EXISTS', ],
      ],
    ]);
    foreach ($term_ids as $term_id) {
      \delete_term_meta($term_id, self::KEY_TRANSIENT_IMPORT_METADATA);
    }

    \delete_option(self::KEY_TRANSIENT_IMPORT_METADATA);
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

        $postsToDelete= \get_posts( ['post_type'=>['page', 'post', 'wp_block', 'nav_menu_item', 'wp_template', 'wp_template_part', 'wp_global_styles', 'wp_navigation'],'numberposts'=>-1, 'fields' => 'ids'] );
        foreach ($postsToDelete as $postToDelete) {
          \wp_delete_post( $postToDelete, true );
        }

        $attachmentsToDelete= \get_posts( ['post_type'=>'attachment','numberposts'=>-1,'fields' => 'ids'] );
        foreach ($attachmentsToDelete as $attachmentToDelete) {
          \wp_delete_attachment($attachmentToDelete, true);
        }
      } else {
        $this->_delete_transient_import_metadata();
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
      $imported_term_ids = \get_terms([
        'fields' => 'ids',
        'hide_empty' => false, // also retrieve terms which are not used yet
        // 'taxonomy'  => array_keys(get_taxonomies()), //'nav_menu',
        'meta_query' => [
          ['key' => self::KEY_TRANSIENT_IMPORT_METADATA, 'compare' => 'EXISTS', ],
        ],
      ]);
      $imported_term_ids = array_reduce(
        $imported_term_ids,
        function($accu, $term_id) {
          $accu[(int)array_shift(\get_term_meta($term_id, self::KEY_TRANSIENT_IMPORT_METADATA))] = $term_id;
          return $accu;
        }, 
        [],
      );

      // fetch imported posts
      $imported_post_ids =  array_reduce(
        $wpdb->get_results(
          $wpdb->prepare("SELECT {$wpdb->prefix}postmeta.meta_value, {$wpdb->prefix}posts.ID FROM {$wpdb->prefix}posts INNER JOIN {$wpdb->prefix}postmeta ON ( {$wpdb->prefix}posts.ID = {$wpdb->prefix}postmeta.post_id ) WHERE ( {$wpdb->prefix}postmeta.meta_key = '%s')", self::KEY_TRANSIENT_IMPORT_METADATA)
        ),
        function($accu, $row) {
          $accu[(int)$row->meta_value] = (int)$row->ID;
          return $accu;
        }, 
        [],
        );
      /*
      array_reduce(
        \get_posts([
          'fields' => 'ids',
          'numberposts'=>-1,
          'post_type' => 'any',
          'meta_query' => [
            ['key' => self::KEY_TRANSIENT_IMPORT_METADATA, 'compare' => 'EXISTS', ],
          ],
        ]),
        function($accu, $post_id) {
          $accu[(int)\get_post_meta($post_id, self::KEY_TRANSIENT_IMPORT_METADATA, true)] = $post_id;
          return $accu;
        }, 
        [],
      );*/

      $imported = [ 
        'terms'   => &$imported_term_ids,
        'posts'   => &$imported_post_ids,
        'options' => \get_option(self::KEY_TRANSIENT_IMPORT_METADATA, []),
      ];

      $this->_backfill_ids($imported);

      $profile->events(self::EVENT_IMPORT_END)($transformationContext, $imported);

      $this->_delete_transient_import_metadata();
    }

    return $unconsumed_slices;
  }

  function _backfill_ids(array &$imported) : void {
    // $first_taxonomy_old_id = (int)array_shift(\get_term_meta($imported_terms[0]->term_id, self::KEY_TRANSIENT_IMPORT_METADATA));
    // $first_taxonomy_id = (int)$imported_posts[0]->_cm4all_KEY_TRANSIENT_IMPORT_METADATA;

    list('terms' => $terms, 'posts' => $posts, 'options' => $options) = $imported;

    // check/fix imported options referencing imported posts
    $wp_options_referencing_posts = ['site_logo', 'site_icon', 'page_on_front', 'page_for_posts'];
    foreach ($wp_options_referencing_posts as $wp_option) {
      // remap wp option if imported
      if(in_array($wp_option, $options)) {
        $post_id = \get_option($wp_option, 0);
        if($post_id!==0) { // if post_id points to a post
          // get the new post id
          $post = \get_post($posts[$post_id]);

          // ensure the new post exists
          if($post!==null) {
            // adjust site_logo value to new post id
            \update_option($wp_option, $post->ID);
          } else if (\get_post($posts[$post_id])===null) {
            // otherwise reset wp_option 
            \delete_option($wp_option);
          }
        }
      }       
    }

    // check/fix menus of current theme
    $nav_menu_locations = get_theme_mod('nav_menu_locations');
    // https://wordpress.stackexchange.com/questions/124658/setting-a-default-theme-location-when-creating-a-menu

    $nav_menus = \wp_get_nav_menus();
    foreach ($nav_menu_locations as $nav_menu_name => $nav_menu_value) {
      // if a term with same old id was imported
      if(isset($terms[$nav_menu_value])) {
        // find the nav_menu term with same name name and mapped id
        foreach ($nav_menus as $nav_menu) {
          if($nav_menu->name === $nav_menu_name && $nav_menu->term_id===$terms[$nav_menu_value]) {
            $nav_menu_locations[$nav_menu_name] = $terms[$nav_menu_value];
          }
        }
      }
    }

    set_theme_mod('nav_menu_locations', $nav_menu_locations);
  
    /* 
      @TODO: adjust reusable block references like

      <!-- wp:block {"ref":1,"cm4allBlockId":"db3b99bc-6e4d-4109-86a2-a76ff2543d64"} /-->

      can also be accessed using a the WP_POst_Object magic __get getter 
      <?php echo $post->some_meta_field; ?>
      https://since1979.dev/wordpress-access-post-meta-fields-through-wp-post/

      use parse_blocks to have a future safe version applicable for templates and template_parts : 
      https://developer.wordpress.org/reference/functions/parse_blocks/
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
