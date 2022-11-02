<?php

namespace cm4all\wp\impex;

use Exception;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit();
}

require_once ABSPATH . '/wp-admin/includes/import.php';

require_once __DIR__ . '/interface-impex-named-item.php';
require_once __DIR__ . '/class-impex.php';

function __ImportContentProviderProviderCallback(array $slice, array $options, ImpexImportTransformationContext $transformationContext): bool
{
  if ($slice[Impex::SLICE_TAG] === ContentExporter::SLICE_TAG) {
    if ($slice[Impex::SLICE_META][Impex::SLICE_META_ENTITY] === ContentExporter::SLICE_META_ENTITY_CONTENT) {
      if ($slice[Impex::SLICE_VERSION] !== ContentExporter::VERSION) {
        throw new ImpexImportRuntimeException(sprintf('Dont know how to import slice(tag="%s", version="%s") : unsupported version. current version is "%s"', ContentExporter::SLICE_TAG, $slice[Impex::SLICE_VERSION], ContentExporter::VERSION));
      }

      $options['users'] ??= [];

      // required if non admin user imports attachments
      if (!function_exists('\post_exists')) {
        require_once(ABSPATH . 'wp-admin/includes/post.php');
      }

      if (!function_exists('\wp_insert_category')) {
        require_once(ABSPATH . '/wp-admin/includes/taxonomy.php');
      }

      if (!function_exists('\wp_read_video_metadata')) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
      }

      try {
        \wp_defer_term_counting(true);
        \wp_defer_comment_counting(true);

        // see https://github.com/WordPress/wordpress-importer/blob/e05f678835c60030ca23c9a186f50999e198a360/src/class-wp-import.php#L85
        // disable cache
        \wp_suspend_cache_invalidation();
        // THIS IS IMPORTANT: clear cache to ensure that we dont get half baked data from cache !
        \wp_cache_flush();

        $author_mapping = _import_authors($options, $slice, $transformationContext);

        $processed_taxonomies = _import_taxonomies($options, $slice, $transformationContext);

        $processed_categories = _import_categories($options, $slice, $transformationContext);
        $processed_terms = _import_terms($options, $slice, $transformationContext);
        $processed_tags = _import_tags($options, $slice, $transformationContext);

        list($processed_posts, $post_orphans, $featured_images, $missing_menu_items, $processed_menu_items, $menu_item_orphans) = _import_posts($options, $slice, $author_mapping, $processed_categories, $processed_terms, $processed_tags, $transformationContext);

        \wp_suspend_cache_invalidation(false);

        // update incorrect/missing information in the DB
        _backfill_parents(
          processed_posts: $processed_posts,
          post_orphans: $post_orphans,
          processed_terms: $processed_terms,
          missing_menu_items: $missing_menu_items,
          processed_menu_items: $processed_menu_items,
          menu_item_orphans: $menu_item_orphans,
        );
        // $this->backfill_attachment_urls();
        // $this->remap_featured_images();
      } catch (ImpexImportRuntimeException $ex) {
        // @TODO: what should happen in case of an error ? 
        throw new ImpexImportRuntimeException(
          message: 'Importing content failed.',
          context: ['options' => $options, 'export' => $export],
          previous: $ex
        );
      } finally {
        // adapted from https://github.com/WordPress/wordpress-importer/blob/master/src/class-wp-import.php#L139
        \wp_cache_flush();

        foreach (\get_taxonomies() as $tax) {
          \delete_option("{$tax}_children");
          \_get_term_hierarchy($tax);
        }

        \wp_defer_term_counting(false);
        \wp_defer_comment_counting(false);
      }

      return true;
    }
  }

  return false;
}

function _import_posts(array $options, array $slice, array $author_mapping, array $processed_categories, array $processed_terms, array $processed_tags, ImpexImportTransformationContext $transformationContext): array
{
  $processed_posts = [];
  $post_orphans = [];
  $featured_images = [];
  $missing_menu_items = [];
  $menu_item_orphans = [];
  $processed_menu_items = [];

  $posts = $slice[Impex::SLICE_DATA][ContentExporter::SLICE_DATA_POSTS];

  // @TODO: should we do it (adapted from wp importer) ? it makes actually no sense since the given data is different ...
  // https://github.com/WordPress/wordpress-importer/blob/e05f678835c60030ca23c9a186f50999e198a360/src/class-wp-import.php#L628
  $posts = \apply_filters('wp_import_posts', $posts);

  foreach ($posts as $post) {
    // @TODO: should we do it (adapted from wp importer) ? it makes actually no sense since the given data is different ...
    $post = \apply_filters('wp_import_post_data_raw', $post);

    if (!\post_type_exists($post[ContentExporter::SLICE_DATA_POSTS_TYPE] ?? 'post')) {
      $transformationContext->warn("Failed to create post(title='{$post['post_title']}', post_type='{$post['post_title']}') : post_type does not exist.");
      continue;
      /*
      throw new ImpexImportRuntimeException(
        "Failed to create post(title='{$post['post_title']}', post_type='{$post['post_title']}') : post_type does not exist."
      );
      */
    }

    // @TODO: : do we want this ? abort post import if post id already exists
    if (isset($processed_posts[$post[ContentExporter::SLICE_DATA_POSTS_ID]]) && !empty($post[ContentExporter::SLICE_DATA_POSTS_ID])) {
      continue;
    }

    // skip auto-draft posts
    if ('auto-draft' == $post[ContentExporter::SLICE_DATA_POSTS_STATUS]) {
      continue;
    }

    // delegate import 
    if ('nav_menu_item' == $post[ContentExporter::SLICE_DATA_POSTS_TYPE]) {
      $result = _process_menu_item($post, $processed_terms, $processed_posts, $missing_menu_items, $processed_menu_items, $menu_item_orphans);
      if($result) {
        list($missing_menu_items, $processed_menu_items, $menu_item_orphans) = $result;
      }

      continue;
    }

    $post_type_object = \get_post_type_object($post[ContentExporter::SLICE_DATA_POSTS_TYPE]);

    $post_exists = \post_exists($post[ContentExporter::SLICE_DATA_POSTS_TITLE], '', $post[ContentExporter::SLICE_DATA_POSTS_DATE]);

    /**
     * @TODO: should we do it ? 
     * Filter ID of the existing post corresponding to post currently importing.
     *
     * Return 0 to force the post to be imported. Filter the ID to be something else
     * to override which existing post is mapped to the imported post.
     *
     * @see post_exists()
     * @since 0.6.2
     *
     * @param int   $post_exists  Post ID, or 0 if post did not exist.
     * @param array $post         The post array to be inserted.
     */
    $post_exists = \apply_filters('wp_import_existing_post', $post_exists, $post);

    if ($post_exists && \get_post_type($post_exists) == $post[ContentExporter::SLICE_DATA_POSTS_TYPE]) {
      // @TODO: log notice post(type=$post_type_object->labels->singular_name}, title={$post['post_title']}) alread exists 
      // => skip post import

      $transformationContext->warn(
        "Skip importing Post(post_type='{$post[ContentExporter::SLICE_DATA_POSTS_TYPE]}', title='{$post[ContentExporter::SLICE_DATA_POSTS_TITLE]}') already exists (id={$post[ContentExporter::SLICE_DATA_POSTS_ID]})."
      );

      $comment_post_id = $post_exists;
      $post_id         = $post_exists;
      $processed_posts[(int)$post[ContentExporter::SLICE_DATA_POSTS_ID]] = (int)$post_exists;
    } else {
      $post_parent = (int) $post[ContentExporter::SLICE_DATA_POSTS_PARENT];
      if ($post_parent !== 0) {
        // if we already know the parent, map it to the new local ID
        if (isset($processed_posts[$post_parent])) {
          $post_parent = $processed_posts[$post_parent];
          // otherwise record the parent for later
        } else {
          $post_orphans[(int)$post['post_id']] = $post_parent;
          $featured_images = [];
          $post_parent = 0;
        }
      }

      // map the post author
      $author = \sanitize_user($post[ContentExporter::SLICE_DATA_POSTS_CREATOR], true);
      $author = $author_mapping[$author] ?? (int)\get_current_user_id();

      // see defaults here : https://developer.wordpress.org/reference/functions/wp_insert_post/
      $postdata = [
        'import_id'      => $post[ContentExporter::SLICE_DATA_POSTS_ID],
        'post_author'    => $author,
        'post_date'      => $post[ContentExporter::SLICE_DATA_POSTS_DATE],
        'post_date_gmt'  => $post[ContentExporter::SLICE_DATA_POSTS_DATE_GMT],
        'post_content'   => $post[ContentExporter::SLICE_DATA_POSTS_CONTENT],
        'post_excerpt'   => $post[ContentExporter::SLICE_DATA_POSTS_EXCERPT],
        'post_title'     => $post[ContentExporter::SLICE_DATA_POSTS_TITLE],
        'post_status'    => $post[ContentExporter::SLICE_DATA_POSTS_STATUS],
        'post_name'      => $post[ContentExporter::SLICE_DATA_POSTS_NAME],
        'comment_status' => $post[ContentExporter::SLICE_DATA_POSTS_COMMENT_STATUS],
        'ping_status'    => $post[ContentExporter::SLICE_DATA_POSTS_PING_STATUS],
        'guid'           => $post[ContentExporter::SLICE_DATA_POSTS_GUID],
        'post_parent'    => $post_parent,
        'menu_order'     => $post[ContentExporter::SLICE_DATA_POSTS_MENU_ORDER],
        'post_type'      => $post[ContentExporter::SLICE_DATA_POSTS_TYPE],
        'post_password'  => $post[ContentExporter::SLICE_DATA_POSTS_PASSWORD],
      ];

      // filter out undefined properties
      foreach ($postdata as $key => $value) {
        if ($value === null) {
          unset($postdata[$key]);
        }
      }

      $original_post_id = $post[ContentExporter::SLICE_DATA_POSTS_ID];
      $postdata = \apply_filters('wp_import_post_data_processed', $postdata, $post);

      $postdata = \wp_slash($postdata);

      // @TODO: attachment processing is not implemented yet
      if ('attachment' == $postdata['post_type']) {
        $remote_url = !empty($post[ContentExporter::SLICE_DATA_POSTS_ATTACHMENT_URL]) ? $post[ContentExporter::SLICE_DATA_POSTS_ATTACHMENT_URL] : $post[ContentExporter::SLICE_DATA_POSTS_GUID];

        // try to use _wp_attached file for upload folder placement to ensure the same location as the export site
        // e.g. location is 2003/05/image.jpg but the attachment post_date is 2010/09, see media_handle_upload()
        $postdata['upload_date'] = $post['post_date'];
        if (isset($post['postmeta'])) {
          foreach ($post['postmeta'] as $meta) {
            if ('_wp_attached_file' == $meta['key']) {
              if (preg_match('%^[0-9]{4}/[0-9]{2}%', $meta['value'], $matches)) {
                $postdata['upload_date'] = $matches[0];
              }
              break;
            }
          }
        }

        $comment_post_id = _process_attachment($postdata, $remote_url);
        $post_id         = $comment_post_id;
      } else {
        $comment_post_id = \wp_insert_post($postdata, true);
        $post_id = $comment_post_id;
        // @TODO: should we do it (adapted from wp importer) ? it makes actually no sense since the given data is different ...
        \do_action('wp_import_insert_post', $post_id, $original_post_id, $postdata, $post);
      }

      if (\is_wp_error($post_id)) {
        throw new ImpexImportRuntimeException(
          "Failed to create post(title='{$post[ContentExporter::SLICE_DATA_POSTS_TITLE]}, post_type={$post[ContentExporter::SLICE_DATA_POSTS_TYPE]}') : {$post_id->get_error_message()}"
        );
      }

      if (1 == $post[ContentExporter::SLICE_DATA_POSTS_IS_STICKY]) {
        \stick_post($post_id);
      }
    }

    // map pre-import ID to local ID
    $processed_posts[(int)$post[ContentExporter::SLICE_DATA_POSTS_ID]] = (int) $post_id;

    $post[ContentExporter::SLICE_DATA_POSTS_TAXONOMY_TERMS] ??= [];

    // @TODO: should we do it (adapted from wp importer) ? it makes actually no sense since the given data is different ...
    $post[ContentExporter::SLICE_DATA_POSTS_TAXONOMY_TERMS] = \apply_filters('wp_import_post_terms', $post[ContentExporter::SLICE_DATA_POSTS_TAXONOMY_TERMS], $post_id, $post);

    // add categories, tags and other terms
    $terms_to_set = [];
    foreach ($post[ContentExporter::SLICE_DATA_POSTS_TAXONOMY_TERMS] as $term) {
      // back compat with WXR 1.0 map 'tag' to 'post_tag'
      $taxonomy = ('tag' == $term[ContentExporter::SLICE_DATA_TERMS_TAXONOMY]) ? 'post_tag' : $term[ContentExporter::SLICE_DATA_TERMS_TAXONOMY];
      $term_exists = \term_exists($term[ContentExporter::SLICE_DATA_TERMS_NAME], $taxonomy);
      $term_id = $term_exists['term_id'] ?? $term_exists; // is_array($term_exists) ? $term_exists['term_id'] : $term_exists;
      if (!$term_id) {
        $t = \wp_insert_term($term[ContentExporter::SLICE_DATA_TERMS_NAME], $taxonomy, ['slug' => $term[ContentExporter::SLICE_DATA_TERMS_SLUG]]);
        if (!is_wp_error($t)) {
          $term_id = $t['term_id'];
          // @TODO: should we do it (adapted from wp importer) ?
          \do_action('wp_import_insert_term', $t, $term, $post_id, $post);
        } else {
          throw new ImpexImportRuntimeException(
            "Failed to import term (taxonomy='{$taxonomy}, name={$term[ContentExporter::SLICE_DATA_TERMS_NAME]}') of post : {$t->get_error_message()}"
          );
        }
      }
      $terms_to_set[$taxonomy][] = (int)$term_id;
    }

    foreach ($terms_to_set as $tax => $ids) {
      $tt_ids = \wp_set_post_terms($post_id, $ids, $tax);
      // @TODO: should we do it (adapted from wp importer) ?
      \do_action('wp_import_set_post_terms', $tt_ids, $ids, $tax, $post_id, $post);
    }
    // @TODO: should we do it (adapted from wp importer) ?
    unset($terms_to_set);

    $post[ContentExporter::SLICE_DATA_POSTS_COMMENTS] ??= [];

    // @TODO: should we do it (adapted from wp importer) ?
    $post[ContentExporter::SLICE_DATA_POSTS_COMMENTS] = \apply_filters('wp_import_post_comments', $post[ContentExporter::SLICE_DATA_POSTS_COMMENTS], $post_id, $post);

    // add/update comments
    $num_comments = 0;
    $inserted_comments = [];
    $newcomments = [];
    foreach ($post[ContentExporter::SLICE_DATA_POSTS_COMMENTS] as $comment) {
      $comment_id = $comment[ContentExporter::SLICE_DATA_POSTS_COMMENTS_ID];
      $newcomments[$comment_id]['comment_post_ID'] = $comment_post_id;
      $newcomments[$comment_id]['comment_author']  = $comment[ContentExporter::SLICE_DATA_POSTS_COMMENTS_AUTHOR];
      $newcomments[$comment_id]['comment_author_email'] = $comment[ContentExporter::SLICE_DATA_POSTS_COMMENTS_AUTHOR_EMAIL];
      $newcomments[$comment_id]['comment_author_IP']  = $comment[ContentExporter::SLICE_DATA_POSTS_COMMENTS_AUTHOR_IP];
      $newcomments[$comment_id]['comment_author_url'] = $comment[ContentExporter::SLICE_DATA_POSTS_COMMENTS_AUTHOR_URL];
      $newcomments[$comment_id]['comment_date'] = $comment[ContentExporter::SLICE_DATA_POSTS_COMMENTS_DATE];
      $newcomments[$comment_id]['comment_date_gmt'] = $comment[ContentExporter::SLICE_DATA_POSTS_COMMENTS_DATE_GMT];
      $newcomments[$comment_id]['comment_content'] = $comment[ContentExporter::SLICE_DATA_POSTS_COMMENTS_CONTENT];
      $newcomments[$comment_id]['comment_approved'] = $comment[ContentExporter::SLICE_DATA_POSTS_COMMENTS_APPROVED];
      $newcomments[$comment_id]['comment_type'] = $comment[ContentExporter::SLICE_DATA_POSTS_COMMENTS_TYPE];
      $newcomments[$comment_id]['comment_parent'] = $comment[ContentExporter::SLICE_DATA_POSTS_COMMENTS_PARENT];
      $newcomments[$comment_id]['commentmeta'] = $comment[ContentExporter::SLICE_DATA_POSTS_COMMENTS_META] ?? [];
      if (isset($processed_authors[$comment[ContentExporter::SLICE_DATA_POSTS_COMMENTS_USER_ID]])) {
        $newcomments[$comment_id]['user_id'] = $processed_authors[$comment[ContentExporter::SLICE_DATA_POSTS_COMMENTS_USER_ID]];
      }
    }
    ksort($newcomments);

    foreach ($newcomments as $key => $comment) {
      // if this is a new post we can skip the comment_exists() check
      if (!$post_exists || !\comment_exists($comment['comment_author'], $comment['comment_date'])) {
        if (isset($inserted_comments[$comment['comment_parent']])) {
          $comment['comment_parent'] = $inserted_comments[$comment['comment_parent']];
        }

        $comment_data = \wp_slash($comment);
        unset($comment_data['commentmeta']); // Handled separately, wp_insert_comment() also expects `comment_meta`.
        $comment_data = \wp_filter_comment($comment_data);

        $inserted_comments[$key] = \wp_insert_comment($comment_data);

        // @TODO: should we do it (adapted from wp importer) ?
        \do_action('wp_import_insert_comment', $inserted_comments[$key], $comment, $comment_post_id, $post);

        foreach ($comment['commentmeta'] as $meta) {
          $value = \maybe_unserialize($meta[ContentExporter::SLICE_DATA_TERMS_META_VALUE]);

          \add_comment_meta($inserted_comments[$key], \wp_slash($meta[ContentExporter::SLICE_DATA_TERMS_META_KEY]), \wp_slash($value));
        }

        $num_comments++;
      }
    }

    // @TODO: do we need that ? 
    unset($newcomments, $inserted_comments);

    // @TODO: should we do it (adapted from wp importer) ?
    $post['postmeta'] = \apply_filters('wp_import_post_meta', $post['postmeta'] ?? [], $post_id, $post);

    // add/update post meta
    foreach ($post[ContentExporter::SLICE_DATA_POSTS_META] as $meta) {
      $key = $meta[ContentExporter::SLICE_DATA_TERMS_META_KEY];
      // skip attachment metadata since we'll regenerate it from scratch
      // skip _edit_lock as not relevant for import
      if (in_array($key, ['_wp_attached_file', '_wp_attachment_metadata', '_edit_lock'], true)) {
        continue;
      }
      $value = false;

      if ('_edit_last' == $key) {
        if (isset($processed_authors[(int)ContentExporter::SLICE_DATA_TERMS_META_VALUE])) {
          $value = $processed_authors[(int)ContentExporter::SLICE_DATA_TERMS_META_VALUE];
        } else {
          $key = false;
        }
      }

      if ($key) {
        // export gets meta straight from the DB so could have a serialized string
        if (!$value) {
          $value = \maybe_unserialize($meta[ContentExporter::SLICE_DATA_TERMS_META_VALUE]);
        }

        \add_post_meta($post_id, \wp_slash($key), \wp_slash($value));

        // @TODO: should we do it (adapted from wp importer) ?
        \do_action('import_post_meta', $post_id, $key, $value);

        // if the post has a featured image, take note of this in case of remap
        if ('_thumbnail_id' == $key) {
          $featured_images[$post_id] = (int) $value;
        }
      }
    }
  }

  return [$processed_posts, $post_orphans, $featured_images, $missing_menu_items, $processed_menu_items, $menu_item_orphans];
}

// @TODO: not implemented yet
function _process_attachment($post, $url)
{
  throw new Exception('not implemented yet : https://github.com/WordPress/wordpress-importer/blob/e05f678835c60030ca23c9a186f50999e198a360/src/class-wp-import.php#L1009');
}

/**
 * Attempt to create a new menu item from import data
 *
 * Fails for draft, orphaned menu items and those without an associated nav_menu
 * or an invalid nav_menu term. If the post type or term object which the menu item
 * represents doesn't exist then the menu item will not be imported (waits until the
 * end of the import to retry again before discarding).
 *
 * @param array $item Menu item details from WXR file
 */
function _process_menu_item($item, array $processed_terms, array $processed_posts, array $missing_menu_items, array $processed_menu_items, array $menu_item_orphans)
{
  // see https://github.com/WordPress/wordpress-importer/blob/e05f678835c60030ca23c9a186f50999e198a360/src/class-wp-import.php#L920

  // skip draft, orphaned menu items
  if ('draft' == $item[ContentExporter::SLICE_DATA_POSTS_STATUS]) {
    return;
  }

  $menu_slug = false;
  if (isset($item[ContentExporter::SLICE_DATA_POSTS_TAXONOMY_TERMS])) {
    // loop through terms, assume first nav_menu term is correct menu
    foreach ($item[ContentExporter::SLICE_DATA_POSTS_TAXONOMY_TERMS] as $term) {
      if ('nav_menu' == $term[ContentExporter::SLICE_DATA_TERMS_TAXONOMY]) {
        $menu_slug = $term[ContentExporter::SLICE_DATA_TERMS_SLUG];
        break;
      }
    }
  }

  // no nav_menu term associated with this menu item
  if (!$menu_slug) {
    throw new ImpexImportRuntimeException(
      "Failed to import menu item post due to missing menu slug"
    );
    //_e('Menu item skipped due to missing menu slug', 'wordpress-importer');
  }

  $menu_id = \term_exists($menu_slug, 'nav_menu');
  if (!$menu_id) {
    throw new ImpexImportRuntimeException(
      "Failed to import menu item post due to invalid menu slug(slug={$menu_slug})"
    );
  } else {
    $menu_id = $menu_id['term_id'] ?? $menu_id;
  }

  foreach ($item[ContentExporter::SLICE_DATA_POSTS_META] as $meta) {
    ${$meta[ContentExporter::SLICE_DATA_TERMS_META_KEY]} = $meta[ContentExporter::SLICE_DATA_TERMS_META_VALUE];
  }

  if ('taxonomy' == $_menu_item_type && isset($processed_terms[(int)$_menu_item_object_id])) {
    $_menu_item_object_id = $processed_terms[(int)$_menu_item_object_id];
  } elseif ('post_type' == $_menu_item_type && isset($processed_posts[(int)$_menu_item_object_id])) {
    $_menu_item_object_id = $processed_posts[(int)$_menu_item_object_id];
  } elseif ('custom' != $_menu_item_type) {
    // associated object is missing or not imported yet, we'll retry later
    $missing_menu_items[] = $item;
    return [$missing_menu_items, $processed_menu_items, $menu_item_orphans];
  }

  if (isset($processed_menu_items[(int)$_menu_item_menu_item_parent])) {
    $_menu_item_menu_item_parent = $processed_menu_items[(int)$_menu_item_menu_item_parent];
  } elseif ($_menu_item_menu_item_parent) {
    $menu_item_orphans[(int)$item['wp:post_id']] = (int) $_menu_item_menu_item_parent;
    $_menu_item_menu_item_parent = 0;
  }

  // wp_update_nav_menu_item expects CSS classes as a space separated string
  $_menu_item_classes = \maybe_unserialize($_menu_item_classes);
  if (is_array($_menu_item_classes)) {
    $_menu_item_classes = implode(' ', $_menu_item_classes);
  }

  $args = [
    'menu-item-object-id'   => $_menu_item_object_id,
    'menu-item-object'      => $_menu_item_object,
    'menu-item-parent-id'   => $_menu_item_menu_item_parent,
    'menu-item-position'    => (int)$item[ContentExporter::SLICE_DATA_POSTS_MENU_ORDER],
    'menu-item-type'        => $_menu_item_type,
    'menu-item-title'       => $item[ContentExporter::SLICE_DATA_POSTS_TITLE],
    'menu-item-url'         => $_menu_item_url,
    'menu-item-description' => $item[ContentExporter::SLICE_DATA_POSTS_CONTENT],
    'menu-item-attr-title'  => $item[ContentExporter::SLICE_DATA_POSTS_CONTENT],
    'menu-item-target'      => $_menu_item_target,
    'menu-item-classes'     => $_menu_item_classes,
    'menu-item-xfn'         => $_menu_item_xfn,
    'menu-item-status'      => $item[ContentExporter::SLICE_DATA_POSTS_STATUS],
  ];

  $id = \wp_update_nav_menu_item($menu_id, 0, $args);
  if ($id && !is_wp_error($id)) {
    $processed_menu_items[(int)$item[ContentExporter::SLICE_DATA_POSTS_ID]] = (int) $id;
  }

  return [$missing_menu_items, $processed_menu_items, $menu_item_orphans];
}

function _import_taxonomies(array $options, array $slice): array
{
  $processed_taxomonies = [];

  $taxonomies = $slice[Impex::SLICE_DATA][ContentExporter::SLICE_DATA_TAXONOMIES];

  foreach ($taxonomies as $taxonomy) {
    $wpTaxonomy = \register_taxonomy($taxonomy['name'], $taxonomy['object_type'], $taxonomy['args']);

    if (!is_wp_error($wpTaxonomy)) {
      $processed_terms[$taxonomy['name']] = $wpTaxonomy;
    } else {
      throw new ImpexImportRuntimeException("Failed to create taxonomy(name==='{$taxonomy['name']}') : {$wpTaxonomy->get_error_message()}");
    }
  }

  return $processed_taxomonies;
}

function _import_tags(array $options, array $slice): array
{
  $tags = $slice[Impex::SLICE_DATA][ContentExporter::SLICE_DATA_TAGS];

  // @TODO: should we do it (adapted from wp importer) ? it makes actually no sense since the given data is different ...
  // see https://github.com/WordPress/wordpress-importer/blob/e05f678835c60030ca23c9a186f50999e198a360/src/class-wp-import.php#L452
  $tags = \apply_filters('wp_import_tags', $tags);

  $processed_terms = [];

  foreach ($tags as $tag) {
    // if the tag already exists leave it alone
    $term_id = \term_exists($tag[ContentExporter::SLICE_DATA_TERMS_SLUG], 'post_tag');
    if ($term_id) {
      if (is_array($term_id)) {
        $term_id = $term_id['term_id'];
      }
      if (isset($tag[ContentExporter::SLICE_DATA_TERMS_ID])) {
        $processed_terms[(int)$tag[ContentExporter::SLICE_DATA_TERMS_ID]] = (int)$term_id;
      }
      continue;
    }

    $description = $tag[ContentExporter::SLICE_DATA_TERMS_DESCRIPTION] ?? '';
    $args        = [
      'slug'        => $tag[ContentExporter::SLICE_DATA_TERMS_SLUG],
      'description' => \wp_slash($description),
    ];

    $term_id = \wp_insert_term(\wp_slash($tag[ContentExporter::SLICE_DATA_TERMS_NAME]), 'post_tag', $args);
    if (!is_wp_error($term_id)) {
      if (isset($tag[ContentExporter::SLICE_DATA_TERMS_ID])) {
        $processed_terms[(int)$tag[ContentExporter::SLICE_DATA_TERMS_ID]] = $term_id['term_id'];
      }
    } else {
      throw new ImpexImportRuntimeException("Failed to create term(tag_name==='{$tag[ContentExporter::SLICE_DATA_TERMS_NAME]}') : {$term_id->get_error_message()}");
    }

    _process_termmeta($tag, $term_id['term_id']);
  }

  return $processed_terms;
}

function _import_terms(array $options, array $slice, ImpexImportTransformationContext $transformationContext): array
{
  $processed_terms = [];

  $terms = $slice[Impex::SLICE_DATA][ContentExporter::SLICE_DATA_TERMS];
  // @TODO: should we do it (adapted from wp importer) ? it makes actually no sense since the given data is different ...
  // see https://github.com/WordPress/wordpress-importer/blob/e05f678835c60030ca23c9a186f50999e198a360/src/class-wp-import.php#L452
  $terms = \apply_filters('wp_import_terms', $terms);

  foreach ($terms as $term) {
    // if the term already exists in the correct taxonomy leave it alone
    $term_id = \term_exists($term[ContentExporter::SLICE_DATA_TERMS_SLUG], $term[ContentExporter::SLICE_DATA_TERMS_TAXONOMY]);
    if ($term_id) {
      if (is_array($term_id)) {
        $term_id = $term_id['term_id'];
      }
      if (isset($term[ContentExporter::SLICE_DATA_TERMS_ID])) {
        $processed_terms[(int)$term[ContentExporter::SLICE_DATA_TERMS_ID]] = (int) $term_id;
      }
      continue;
    }

    if (empty($term[ContentExporter::SLICE_DATA_TERMS_PARENT])) {
      $parent = 0;
    } else {
      $parent = \term_exists($term[ContentExporter::SLICE_DATA_TERMS_PARENT], $term[ContentExporter::SLICE_DATA_TERMS_TAXONOMY]);
      if (is_array($parent)) {
        $parent = $parent['term_id'];
      }
    }

    $description = $term[ContentExporter::SLICE_DATA_TERMS_DESCRIPTION] ?? '';
    $args        = [
      'slug'        => $term[ContentExporter::SLICE_DATA_TERMS_SLUG],
      'description' => \wp_slash($description),
      'parent'      => (int) $parent,
    ];

    $term_id = \wp_insert_term(\wp_slash($term[ContentExporter::SLICE_DATA_TERMS_NAME]), $term[ContentExporter::SLICE_DATA_TERMS_TAXONOMY], $args);
    if (!\is_wp_error($term_id)) {
      if (isset($term[ContentExporter::SLICE_DATA_TERMS_ID])) {
        $processed_terms[(int)$term[ContentExporter::SLICE_DATA_TERMS_ID]] = $term_id['term_id'];
      }
      _process_termmeta($term, $term_id['term_id']);
    } else {
      $transformationContext->warn("Failed to create term(term_name==='{$term[ContentExporter::SLICE_DATA_TERMS_NAME]}') : {$term_id->get_error_message()}", $term);
      // throw new ImpexImportRuntimeException("Failed to create term(term_name==='{$term[ContentExporter::SLICE_DATA_TERMS_NAME]}') : {$term_id->get_error_message()}");
    }
  }

  return $processed_terms;
}

function _import_categories(array $options, array $slice, ImpexImportTransformationContext $transformationContext): array
{
  $categories = $slice[Impex::SLICE_DATA][ContentExporter::SLICE_DATA_CATEGORIES];

  // @TODO: should we do it (adapted from wp importer) ? it makes actually no sense since the given data is different ...
  // see https://github.com/WordPress/wordpress-importer/blob/e05f678835c60030ca23c9a186f50999e198a360/src/class-wp-import.php#L397
  $categories = \apply_filters('wp_import_categories', $categories);

  $processed_terms = [];

  foreach ($categories as $category) {
    // if the category already exists leave it alone
    $term_id = \term_exists($category[ContentExporter::SLICE_DATA_TERMS_SLUG], 'category');
    if ($term_id) {
      if (is_array($term_id)) {
        $term_id = $term_id['term_id'];
      }
      if (isset($category[ContentExporter::SLICE_DATA_TERMS_ID])) {
        $processed_terms[(int)$category[ContentExporter::SLICE_DATA_TERMS_ID]] = (int)$term_id;
      }
      continue;
    }

    $parent      = empty($category[ContentExporter::SLICE_DATA_TERMS_PARENT]) ? 0 : \category_exists($category[ContentExporter::SLICE_DATA_TERMS_PARENT]);
    $description = $category[ContentExporter::SLICE_DATA_TERMS_DESCRIPTION] ?? '';

    $data = [
      'category_nicename'    => $category[ContentExporter::SLICE_DATA_TERMS_SLUG],
      'category_parent'      => $parent,
      'cat_name'             => \wp_slash($category[ContentExporter::SLICE_DATA_TERMS_NAME]),
      'category_description' => \wp_slash($description),
    ];

    $category_id = \wp_insert_category($data);

    if (!\is_wp_error($category_id) && $category_id > 0) {
      if (isset($category[ContentExporter::SLICE_DATA_TERMS_ID])) {
        $processed_terms[(int)$category[ContentExporter::SLICE_DATA_TERMS_ID]] = $category_id;
      }
    } else {
      throw new ImpexImportRuntimeException("Failed to create category(category_nicename==='{$category['category_nicename']}') : {$category_id->get_error_message()}");
    }

    _process_termmeta($category, $category_id);
  }

  return $processed_terms;
}

function _process_termmeta(array $term, int $term_id)
{
  $term[ContentExporter::SLICE_DATA_TERMS_META] ??= [];

  // TODO: should we take this over as is ? 
  // adapted from https://github.com/WordPress/wordpress-importer/blob/e05f678835c60030ca23c9a186f50999e198a360/src/class-wp-import.php#L580
  $term[ContentExporter::SLICE_DATA_TERMS_META] = \apply_filters('wp_import_term_meta', $term[ContentExporter::SLICE_DATA_TERMS_META], $term_id, $term);

  foreach ($term[ContentExporter::SLICE_DATA_TERMS_META] as $meta) {
    // TODO: should we take this over as is ? 
    // adapted from https://github.com/WordPress/wordpress-importer/blob/e05f678835c60030ca23c9a186f50999e198a360/src/class-wp-import.php#L596
    $key = \apply_filters('import_term_meta_key', $meta[ContentExporter::SLICE_DATA_TERMS_META_KEY], $term_id, $term);
    if (!$key) {
      continue;
    }

    // Export gets meta straight from the DB so could have a serialized string
    $value = \maybe_unserialize($meta[ContentExporter::SLICE_DATA_TERMS_META_VALUE]);

    \add_term_meta($term_id, \wp_slash($key), \wp_slash($value));

    // TODO: should we take this over as is ? 
    // adapted from https://github.com/WordPress/wordpress-importer/blob/e05f678835c60030ca23c9a186f50999e198a360/src/class-wp-import.php#L615
    \do_action('import_term_meta', $term_id, $key, $value);
  }
}

/**
 * @throws ImpexImportRuntimeException
 * @return array<string,integer>
 */
function _import_authors(array $options, array $slice, ImpexImportTransformationContext $transformationContext): array
{
  if ($options['users'] === false) {
    return false;
  }
  // https://github.com/WordPress/wordpress-importer/blob/e05f678835c60030ca23c9a186f50999e198a360/src/class-wp-import.php#L286
  if (!\apply_filters('import_allow_create_users', true)) {
    throw new ImpexImportRuntimeException("Importing users is not allowed(=>filter'import_allow_create_users'===false).");
  };

  $users = [];
  // 'alice' => 1, // alice will be mapped to user ID 1
  // 'bob' => 'john', // bob will be transformed into john
  // 'eve' => false // eve will be imported as is
  foreach ($slice[Impex::SLICE_DATA][ContentExporter::SLICE_DATA_AUTHORS] as $author) {
    // Multisite adds strtolower to sanitize_user. Need to sanitize here to stop breakage in process_posts.
    $login =  sanitize_user($author[ContentExporter::SLICE_DATA_AUTHORS_LOGIN], true);

    // bugfix: if current user is '' (which is illegal but happens on bad configured machines)
    // => map it to the current user
    if ($login === '' && !isset($options['users'][$login])) {
      $options['users'][$login] = (int)\get_current_user_id();
    }

    $map = $options['users'][$login] ?? \username_exists($login);

    if ($map === false) {
      // create user if not exists

      // see https://github.com/WordPress/wordpress-importer/blob/e05f678835c60030ca23c9a186f50999e198a360/src/class-wp-import.php#L353
      // plain old variant : $user_id = \wp_create_user($login, $login, \wp_generate_password());

      $user_id   = \wp_insert_user([
        'user_login'   => $login,
        'user_pass'    => wp_generate_password(),
        'user_email'   => $author[ContentExporter::SLICE_DATA_AUTHORS_EMAIL] ?? '',
        'display_name' => $author[ContentExporter::SLICE_DATA_AUTHORS_DISPLAY_NAME] ?? $login,
        'first_name'   => $author[ContentExporter::SLICE_DATA_AUTHORS_FIRST_NAME] ?? '',
        'last_name'    => $author[ContentExporter::SLICE_DATA_AUTHORS_LAST_NAME] ?? '',
      ]);

      if ($user_id instanceof \WP_Error) {
        throw new ImpexImportRuntimeException("Failed to create user(login==='${login}') : {$user_id->get_error_message()}");
      }
    } else if (gettype($map) === 'string') {
      // map to user "by login"
      $user_id = \username_exists($map);  // optimization: more performant than \get_user_by('login', $map)
      if ($user_id === false) {
        throw new ImpexImportRuntimeException("Invalid user mapping : failed to find expected user(login==='{$map}')");
      }
    } else if (gettype($map) === 'integer') {
      // map to user "by id"
      $user_id = \get_user_by('id', $map)?->ID ?? false; // ensure user with ID exists 
      if ($user_id === false) {
        throw new ImpexImportRuntimeException("Invalid user mapping : Failed to find expected user(id==={$map})");
      }
    } else {
      // invalid $map value
      throw new ImpexImportRuntimeException("Invalid user mapping : don't know to to handle user mapping('{$login}'=>{$map})");
    }

    $users[$login] = $user_id;
  }

  return $users;
}

/**
 * Attempt to associate posts and menu items with previously missing parents
 *
 * An imported post's parent may not have been imported when it was first created
 * so try again. Similarly for child menu items and menu items which were missing
 * the object (e.g. post) they represent in the menu
 * 
 * @see https://github.com/WordPress/wordpress-importer/blob/e05f678835c60030ca23c9a186f50999e198a360/src/class-wp-import.php#L1219
 */
function _backfill_parents(array $processed_posts, array $post_orphans, array $processed_terms, array $missing_menu_items, array $processed_menu_items, array $menu_item_orphans)
{
  global $wpdb;

  // find parents for post orphans
  foreach ($post_orphans as $child_id => $parent_id) {
    $local_child_id  = false;
    $local_parent_id = false;
    if (isset($processed_posts[$child_id])) {
      $local_child_id = $processed_posts[$child_id];
    }
    if (isset($processed_posts[$parent_id])) {
      $local_parent_id = $processed_posts[$parent_id];
    }

    if ($local_child_id && $local_parent_id) {
      $wpdb->update($wpdb->posts, ['post_parent' => $local_parent_id], ['ID' => $local_child_id], '%d', '%d');
      \clean_post_cache($local_child_id);
    }
  }

  // all other posts/terms are imported, retry menu items with missing associated object
  foreach ($missing_menu_items as $item) {
    _process_menu_item($item, $processed_terms, $processed_posts, $missing_menu_items, $processed_menu_items, $menu_item_orphans);
  }

  // find parents for menu item orphans
  foreach ($menu_item_orphans as $child_id => $parent_id) {
    $local_child_id  = 0;
    $local_parent_id = 0;
    if (isset($processed_menu_items[$child_id])) {
      $local_child_id = $processed_menu_items[$child_id];
    }
    if (isset($processed_menu_items[$parent_id])) {
      $local_parent_id = $processed_menu_items[$parent_id];
    }

    if ($local_child_id && $local_parent_id) {
      update_post_meta($local_child_id, '_menu_item_menu_item_parent', (int) $local_parent_id);
    }
  }
}

/**
 * Use stored mapping information to update old attachment URLs
 */
/*
@TODO: needs adaption
function backfill_attachment_urls()
{
  global $wpdb;
  // make sure we do the longest urls first, in case one is a substring of another
  uksort($this->url_remap, [&$this, 'cmpr_strlen']);

  foreach ($this->url_remap as $from_url => $to_url) {
    // remap urls in post_content
    $wpdb->query($wpdb->prepare("UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, %s, %s)", $from_url, $to_url));
    // remap enclosure urls
    $result = $wpdb->query($wpdb->prepare("UPDATE {$wpdb->postmeta} SET meta_value = REPLACE(meta_value, %s, %s) WHERE meta_key='enclosure'", $from_url, $to_url));
  }
}
*/

/**
 * Update _thumbnail_id meta to new, imported attachment IDs
 */
/*
  // @TODO: needs adaption
	function remap_featured_images() {
		// cycle through posts that have a featured image
		foreach ( $this->featured_images as $post_id => $value ) {
			if ( isset( $this->processed_posts[ $value ] ) ) {
				$new_id = $this->processed_posts[ $value ];
				// only update if there's a difference
				if ( $new_id != $value ) {
					update_post_meta( $post_id, '_thumbnail_id', $new_id );
				}
			}
		}
	}
  */

interface ContentImporter
{
  const PROVIDER_NAME = self::class;
}

function __registerContentImportProvider()
{
  $provider = Impex::getInstance()->Import->addProvider(ContentImporter::PROVIDER_NAME, __NAMESPACE__ . '\__ImportContentProviderProviderCallback');
  return $provider;
}

\add_action(
  Impex::WP_ACTION_REGISTER_PROVIDERS,
  __NAMESPACE__ . '\__registerContentImportProvider'
);
