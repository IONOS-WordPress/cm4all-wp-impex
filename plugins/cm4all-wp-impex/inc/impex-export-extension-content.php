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

function __ContentExporterProviderCallback(array $options, ImpexExportTransformationContext $transformationContext): \Generator
{
  $chunk_max_items = $options[ContentExporter::OPTION_SLICE_MAX_ITEMS] ?? ContentExporter::OPTION_SLICE_MAX_ITEMS_DEFAULT;
  $chunks = [];
  $current_chunk = [];

  $chunks[] = _export_wp($options, $chunk_max_items);

  if ($current_chunk !== []) {
    $chunks[] = $current_chunk;
  }

  foreach ($chunks as $chunk) {
    yield [
      Impex::SLICE_TAG => ContentExporter::SLICE_TAG,
      Impex::SLICE_VERSION => ContentExporter::VERSION,
      Impex::SLICE_META => [
        Impex::SLICE_META_ENTITY => ContentExporter::SLICE_META_ENTITY_CONTENT,
        'options' => $options,
      ],
      Impex::SLICE_DATA => $chunk,
    ];
  }
}

/**
 * @TODO: convert to enum if enums once are available in PHP
 */
interface ContentExporter
{
  const SLICE_TAG = 'content-exporter';
  const SLICE_META_ENTITY_CONTENT = self::SLICE_TAG;

  const OPTION_SLICE_MAX_ITEMS = 'content-export-option-chunk-max-items';
  const OPTION_SLICE_MAX_ITEMS_DEFAULT = 50;
  const OPTION_SLICE_POST_TYPES = 'content-export-option-post-types';
  const OPTION_SLICE_POST_TYPES_DEFAULT = 'all';
  const OPTION_SLICE_AUTHOR = 'content-export-option-author';
  const OPTION_SLICE_AUTHOR_DEFAULT = false;
  const OPTION_SLICE_CATEGORY = 'content-export-option-category';
  const OPTION_SLICE_CATEGORY_DEFAULT = false;
  const OPTION_SLICE_STATUS = 'content-export-option-status';
  const OPTION_SLICE_STATUS_DEFAULT = false;
  const OPTION_SLICE_CATEGORY_START_DATE = 'content-export-option-start-date';
  const OPTION_SLICE_CATEGORY_START_DATE_DEFAULT = false;
  const OPTION_SLICE_CATEGORY_END_DATE = 'content-export-option-end-date';
  const OPTION_SLICE_CATEGORY_END_DATE_DEFAULT = false;

  const PROVIDER_NAME = self::class;

  const VERSION = '1.0.0';

  const SLICE_DATA_TITLE = 'wp:bloginfo_title';
  const SLICE_DATA_URL = 'wp:bloginfo_url';
  const SLICE_DATA_DESCRIPTION = 'wp:bloginfo_description';
  const SLICE_DATA_LANGUAGE = 'wp:bloginfo_language';
  const SLICE_DATA_RSS_URL = 'wp:bloginfo_rss_url';
  const SLICE_DATA_AUTHORS = 'authors';
  const SLICE_DATA_TAXONOMIES = 'taxonomies';
  const SLICE_DATA_CATEGORIES = 'categories';
  const SLICE_DATA_TAGS = 'tags';
  const SLICE_DATA_TERMS = 'terms';
  const SLICE_DATA_POSTS = 'posts';

  const SLICE_DATA_AUTHORS_ID = 'wp:author_id';
  const SLICE_DATA_AUTHORS_LOGIN = 'wp:author_login';
  const SLICE_DATA_AUTHORS_EMAIL = 'wp:author_email';
  const SLICE_DATA_AUTHORS_DISPLAY_NAME = 'wp:author_display_name';
  const SLICE_DATA_AUTHORS_FIRST_NAME = 'wp:author_first_name';
  const SLICE_DATA_AUTHORS_LAST_NAME = 'wp:author_last_name';

  const SLICE_DATA_TERMS_ID = 'wp:term_id';
  const SLICE_DATA_TERMS_TAXONOMY = 'wp:term_taxonomy';
  const SLICE_DATA_TERMS_SLUG = 'wp:term_slug';
  const SLICE_DATA_TERMS_PARENT = 'wp:term_parent';
  const SLICE_DATA_TERMS_NAME = 'wp:term_name';
  const SLICE_DATA_TERMS_DESCRIPTION = 'wp:term_description';
  const SLICE_DATA_TERMS_META = 'wp:term_meta';

  const SLICE_DATA_TERMS_META_VALUE = 'wp:term_meta_value';
  const SLICE_DATA_TERMS_META_KEY = 'wp:term_meta_key';

  const SLICE_DATA_POSTS_ID = 'wp:post_id';
  const SLICE_DATA_POSTS_TITLE = 'title';
  const SLICE_DATA_POSTS_LINK = 'link';
  const SLICE_DATA_POSTS_GUID = 'guid';
  const SLICE_DATA_POSTS_DATE = 'wp:post_date';
  const SLICE_DATA_POSTS_DATE_GMT = 'wp:post_date_gmt';
  const SLICE_DATA_POSTS_MODIFIED_GMT = 'wp:post_modified_gmt';
  const SLICE_DATA_POSTS_CONTENT = 'wp:post_content';
  const SLICE_DATA_POSTS_EXCERPT = 'wp:post_excerpt';
  const SLICE_DATA_POSTS_CREATOR = 'dc:creator';
  const SLICE_DATA_POSTS_PUBDATE = 'pubDate';
  const SLICE_DATA_POSTS_STATUS = 'wp:status';
  const SLICE_DATA_POSTS_COMMENT_STATUS = 'wp:comment_status';
  const SLICE_DATA_POSTS_NAME = 'wp:post_name';
  const SLICE_DATA_POSTS_PING_STATUS = 'wp:ping_status';
  const SLICE_DATA_POSTS_PARENT = 'wp:post_parent';
  const SLICE_DATA_POSTS_MENU_ORDER = 'wp:menu_order';
  const SLICE_DATA_POSTS_TYPE = 'wp:post_type';
  const SLICE_DATA_POSTS_PASSWORD = 'wp:post_password';
  const SLICE_DATA_POSTS_IS_STICKY = 'wp:is_sticky';
  const SLICE_DATA_POSTS_ATTACHMENT_URL = 'wp:attachment_url';
  const SLICE_DATA_POSTS_TAXONOMY_TERMS = 'taxonomy_terms';
  const SLICE_DATA_POSTS_META = 'meta';
  const SLICE_DATA_POSTS_COMMENTS = 'comments';

  const SLICE_DATA_POSTS_COMMENTS_ID = 'wp:comment_id';
  const SLICE_DATA_POSTS_COMMENTS_AUTHOR = 'wp:comment_author';
  const SLICE_DATA_POSTS_COMMENTS_AUTHOR_EMAIL = 'wp:comment_author_email';
  const SLICE_DATA_POSTS_COMMENTS_AUTHOR_URL = 'wp:comment_author_url';
  const SLICE_DATA_POSTS_COMMENTS_AUTHOR_IP = 'wp:comment_author_IP';
  const SLICE_DATA_POSTS_COMMENTS_DATE = 'wp:comment_date';
  const SLICE_DATA_POSTS_COMMENTS_DATE_GMT = 'wp:comment_date_gmt';
  const SLICE_DATA_POSTS_COMMENTS_CONTENT = 'wp:comment_content';

  const SLICE_DATA_POSTS_COMMENTS_APPROVED = 'wp:comment_approved';
  const SLICE_DATA_POSTS_COMMENTS_TYPE = 'wp:comment_type';
  const SLICE_DATA_POSTS_COMMENTS_PARENT = 'wp:comment_parent';
  const SLICE_DATA_POSTS_COMMENTS_USER_ID = 'wp:comment_user_id';
  const SLICE_DATA_POSTS_COMMENTS_META = 'wp:commentmeta';
}

function __registerContentExportProvider()
{
  $provider = Impex::getInstance()->Export->addProvider(ContentExporter::PROVIDER_NAME, __NAMESPACE__ . '\__ContentExporterProviderCallback');
  return $provider;
}

\add_action(
  Impex::WP_ACTION_REGISTER_PROVIDERS,
  __NAMESPACE__ . '\__registerContentExportProvider',
);

function _export_wp(array $options, int $chunk_max_items = ContentExporter::OPTION_SLICE_MAX_ITEMS)
{
  $options     = \wp_parse_args($options, [
    ContentExporter::OPTION_SLICE_POST_TYPES => ContentExporter::OPTION_SLICE_POST_TYPES_DEFAULT,
    ContentExporter::OPTION_SLICE_AUTHOR     => ContentExporter::OPTION_SLICE_AUTHOR_DEFAULT,
    ContentExporter::OPTION_SLICE_CATEGORY   => ContentExporter::OPTION_SLICE_CATEGORY_DEFAULT,
    ContentExporter::OPTION_SLICE_CATEGORY_START_DATE => ContentExporter::OPTION_SLICE_CATEGORY_START_DATE_DEFAULT,
    ContentExporter::OPTION_SLICE_CATEGORY_END_DATE => ContentExporter::OPTION_SLICE_CATEGORY_END_DATE_DEFAULT,
    ContentExporter::OPTION_SLICE_STATUS => ContentExporter::OPTION_SLICE_STATUS_DEFAULT,
  ]);

  // @TODO: should we do it (adapted from wp importer) ? it makes actually no sense since the given data is different ...ually no sense since the given data is different ...
  \do_action('export_wp', $options);

  $post_ids = _fetch_post_ids($options);

  /*
	 * Get the requested terms ready, empty unless posts filtered by category
	 * or all content.
	 */
  $cats  = [];
  $tags  = [];
  $terms = [];
  $custom_taxonomies = [];
  if (isset($term) && $term) {
    $cat  = \get_term($term['term_id'], 'category');
    $cats = [$cat->term_id => $cat];
    unset($term, $cat);
  } elseif (ContentExporter::OPTION_SLICE_POST_TYPES_DEFAULT === $options[ContentExporter::OPTION_SLICE_POST_TYPES]) {
    $categories = \get_categories(['get' => 'all']);
    $tags       = \get_tags(array('get' => 'all'));

    // @TODO: process only taxonomies of exported post types
    $custom_taxonomies = \get_taxonomies(['_builtin' => false], 'objects');
    $custom_terms      = \get_terms(
      [
        'taxonomy' => array_column($custom_taxonomies, 'name'),
        'get'      => 'all',
      ]
    );

    // Put categories in order with no child going before its parent.
    while ($cat = array_shift($categories)) {
      if (!$cat->parent || isset($cats[$cat->parent])) {
        $cats[$cat->term_id] = $cat;
      } else {
        $categories[] = $cat;
      }
    }
    ksort($cats, SORT_NUMERIC);

    // Put terms in order with no child going before its parent.
    while ($t = array_shift($custom_terms)) {
      if (!$t->parent || isset($terms[$t->parent])) {
        $terms[$t->term_id] = $t;
      } else {
        $custom_terms[] = $t;
      }
    }
    ksort($terms, SORT_NUMERIC);
  }

  $data = [
    ContentExporter::SLICE_DATA_TITLE => \get_bloginfo('name'),
    ContentExporter::SLICE_DATA_URL => \get_bloginfo('url'),
    ContentExporter::SLICE_DATA_DESCRIPTION => \get_bloginfo('description'),
    ContentExporter::SLICE_DATA_LANGUAGE => \get_bloginfo('language'),
    ContentExporter::SLICE_DATA_RSS_URL => \is_multisite() ? \network_home_url() : \get_bloginfo_rss('url'),
    ContentExporter::SLICE_DATA_AUTHORS => _authors_list($post_ids),
    ContentExporter::SLICE_DATA_TAXONOMIES => _taxonomy_list($custom_taxonomies),
    ContentExporter::SLICE_DATA_CATEGORIES => _categories_list($cats),
    ContentExporter::SLICE_DATA_TAGS => _tags_list($tags),
    ContentExporter::SLICE_DATA_TERMS => array_merge(_terms_list($terms), ContentExporter::OPTION_SLICE_POST_TYPES_DEFAULT === $options[ContentExporter::OPTION_SLICE_POST_TYPES] ? _nav_menu_terms($terms) : []),
    ContentExporter::SLICE_DATA_POSTS => array_merge(..._posts($post_ids, $chunk_max_items)),
  ];

  return $data;
}

function _fetch_post_ids(array $options): array
{
  global $wpdb;

  // see https://github.com/WordPress/WordPress/blob/c569c157f0400344786ce94744c49afce1566e77/wp-admin/includes/export.php#L100

  // option ContentExporter::OPTION_SLICE_POST_TYPES might be ContentExporter::OPTION_SLICE_POST_TYPES_DEFAULT or a wordpress post_type
  if (ContentExporter::OPTION_SLICE_POST_TYPES_DEFAULT !== $options[ContentExporter::OPTION_SLICE_POST_TYPES] && \post_type_exists($options[ContentExporter::OPTION_SLICE_POST_TYPES])) {
    $ptype = \get_post_type_object($options[ContentExporter::OPTION_SLICE_POST_TYPES]);
    if (!$ptype->can_export) {
      $options[ContentExporter::OPTION_SLICE_POST_TYPES] = 'post';
    }

    $where = $wpdb->prepare("{$wpdb->posts}.post_type = %s", $options[ContentExporter::OPTION_SLICE_POST_TYPES]);
  } else {
    $post_types = array_filter(\get_post_types(['can_export' => true]), fn ($post_type) => 'attachment' !== $post_type);
    $esses      = array_fill(0, count($post_types), '%s');

    // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
    $where = $wpdb->prepare("{$wpdb->posts}.post_type IN (" . implode(',', $esses) . ')', $post_types);
  }

  // option ContentExporter::OPTION_SLICE_STATUS is only taken in to account when ContentExporter::OPTION_SLICE_POST_TYPES is 'page' or 'post'
  // by default we export posts with  any status except 'auto-draft
  if ($options[ContentExporter::OPTION_SLICE_STATUS] && ('post' === $options[ContentExporter::OPTION_SLICE_POST_TYPES] || 'page' === $options[ContentExporter::OPTION_SLICE_POST_TYPES])) {
    $where .= $wpdb->prepare(" AND {$wpdb->posts}.post_status = %s", ContentExporter::OPTION_SLICE_STATUS);
  } else {
    $where .= " AND {$wpdb->posts}.post_status != 'auto-draft'";
  }

  // option ContentExporter::OPTION_SLICE_CATEGORY is only taken in to account when ContentExporter::OPTION_SLICE_POST_TYPES is 'post'
  $join = '';
  if ($options[ContentExporter::OPTION_SLICE_CATEGORY] && 'post' === $options[ContentExporter::OPTION_SLICE_POST_TYPES]) {
    $term = \term_exists($options[ContentExporter::OPTION_SLICE_CATEGORY], 'category');
    if ($term) {
      $join   = "INNER JOIN {$wpdb->term_relationships} ON ({$wpdb->posts}.ID = {$wpdb->term_relationships}.object_id)";
      $where .= $wpdb->prepare(" AND {$wpdb->term_relationships}.term_taxonomy_id = %d", $term['term_taxonomy_id']);
    }
  }

  // option ContentExporter::OPTION_SLICE_CATEGORY_START_DATE, ContentExporter::OPTION_SLICE_CATEGORY_END_DATE are only taken in to account when ContentExporter::OPTION_SLICE_POST_TYPES is 'post', 'page' or 'attachment'
  if (in_array($options[ContentExporter::OPTION_SLICE_POST_TYPES], ['post', 'page', 'attachment'], true)) {
    if ($options[ContentExporter::OPTION_SLICE_AUTHOR]) {
      $where .= $wpdb->prepare(" AND {$wpdb->posts}.post_author = %d", $options[ContentExporter::OPTION_SLICE_AUTHOR]);
    }

    if ($options[ContentExporter::OPTION_SLICE_CATEGORY_START_DATE]) {
      $where .= $wpdb->prepare(" AND {$wpdb->posts}.post_date >= %s", gmdate('Y-m-d', strtotime($options[ContentExporter::OPTION_SLICE_CATEGORY_START_DATE])));
    }

    if ($options[ContentExporter::OPTION_SLICE_CATEGORY_END_DATE]) {
      $where .= $wpdb->prepare(" AND {$wpdb->posts}.post_date < %s", gmdate('Y-m-d', strtotime('+1 month', strtotime($options[ContentExporter::OPTION_SLICE_CATEGORY_END_DATE]))));
    }
  }

  // Grab a snapshot of post IDs, just in case it changes during the export.
  $post_ids = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} $join WHERE $where");

  return $post_ids;
}

function _taxonomy_list(array $taxonomies): array
{
  return array_map(function (\WP_Taxonomy $taxonomy): array {
    $_ = [
      'name' => $taxonomy->name,
      'object_type' => $taxonomy->object_type,
    ];

    $args = (array)$taxonomy;
    unset($args['name']);
    unset($args['object_type']);
    $args['capabilities'] = (array)$args['cap'];
    unset($args['cap']);

    foreach ($args as $key => $value) {
      if (is_object($value)) {
        $args[$key] = (array)($value);
      }
    }

    $_['args'] = $args;

    return $_;
  }, $taxonomies);
}

function _authors_list(array $post_ids): array
{
  global $wpdb;

  $results = $wpdb->get_results("SELECT DISTINCT post_author FROM $wpdb->posts WHERE post_status != 'auto-draft' AND ID IN ( " . implode(', ', $post_ids) . ")");
  $authors = array_map(function ($result) {
    $userdata = \get_userdata($result->post_author);

    return [
      ContentExporter::SLICE_DATA_AUTHORS_ID => (int) $userdata->ID,  // @TODO: could be removed from export since its not used by import
      ContentExporter::SLICE_DATA_AUTHORS_LOGIN => $userdata->user_login,
      ContentExporter::SLICE_DATA_AUTHORS_EMAIL => $userdata->user_email,
      ContentExporter::SLICE_DATA_AUTHORS_DISPLAY_NAME => $userdata->display_name,
      ContentExporter::SLICE_DATA_AUTHORS_FIRST_NAME => $userdata->first_name,
      ContentExporter::SLICE_DATA_AUTHORS_LAST_NAME => $userdata->last_name,
    ];
  }, $results);

  return $authors;
}

function _categories_list(array $cats): array
{
  return array_map(function ($c) use ($cats) {
    return [
      ContentExporter::SLICE_DATA_TERMS_ID => (int) $c->term_id,
      ContentExporter::SLICE_DATA_TERMS_SLUG => $c->slug,
      ContentExporter::SLICE_DATA_TERMS_PARENT => $c->parent ? $cats[$c->parent]->slug : '',
      ContentExporter::SLICE_DATA_TERMS_NAME => $c->name ??  null,
      ContentExporter::SLICE_DATA_TERMS_DESCRIPTION => $c->description ?? null,
      ContentExporter::SLICE_DATA_TERMS_META => _term_meta($c),
    ];
  }, $cats);
}

function _tags_list($tags): array
{
  return array_map(function ($t) {
    return [
      ContentExporter::SLICE_DATA_TERMS_ID => (int) $t->term_id,
      ContentExporter::SLICE_DATA_TERMS_SLUG => $t->slug,
      ContentExporter::SLICE_DATA_TERMS_NAME => $t->name ??  null,
      ContentExporter::SLICE_DATA_TERMS_DESCRIPTION => $t->description ??  null,
      ContentExporter::SLICE_DATA_TERMS_META => _term_meta($t),
    ];
  }, $tags);
}

function _terms_list($terms): array
{
  return array_map(function ($t) use ($terms) {
    return [
      ContentExporter::SLICE_DATA_TERMS_ID => (int) $t->term_id,
      ContentExporter::SLICE_DATA_TERMS_TAXONOMY => $t->taxonomy,
      ContentExporter::SLICE_DATA_TERMS_SLUG => $t->slug,
      ContentExporter::SLICE_DATA_TERMS_PARENT => $t->parent ? $terms[$t->parent]->slug : '',
      ContentExporter::SLICE_DATA_TERMS_NAME => $t->name ??  null,
      ContentExporter::SLICE_DATA_TERMS_DESCRIPTION => $t->description ??  null,
      ContentExporter::SLICE_DATA_TERMS_META => _term_meta($t),
    ];
  }, $terms);
}

function _nav_menu_terms(array $blacklist): array
{
  $nav_menus = \wp_get_nav_menus();
  // @TODO: can this happen ??
  $nav_menus = empty($nav_menus) || !is_array($nav_menus) ? [] : $nav_menus;

  $blacklisted_term_ids = array_column($blacklist, 'term_id');

  $nav_menus = array_filter($nav_menus, fn ($menu) => !in_array($menu->term_id, $blacklisted_term_ids, true));

  return array_map(function ($menu) {
    return [
      ContentExporter::SLICE_DATA_TERMS_ID => (int) $menu->term_id,
      ContentExporter::SLICE_DATA_TERMS_TAXONOMY => 'nav_menu',
      ContentExporter::SLICE_DATA_TERMS_SLUG => $menu->slug,
      ContentExporter::SLICE_DATA_TERMS_NAME => $menu->name ??  null,
    ];
  }, $nav_menus);
}

function _term_meta($term): array
{
  global $wpdb;

  $termmeta = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->termmeta WHERE term_id = %d", $term->term_id));
  return array_map(function ($meta) {
    return [
      ContentExporter::SLICE_DATA_TERMS_META_KEY => $meta->meta_key,
      ContentExporter::SLICE_DATA_TERMS_META_VALUE => $meta->meta_value,
    ];
  }, $termmeta);
}

function _posts(array $post_ids, int $chunk_max_items): array
{
  global $wpdb;
  global $wp_query;
  // Fake being in the loop.
  $wp_query->in_the_loop = true;

  $chunks = [];

  // Fetch $chunk_max_items posts at a time rather than loading the entire table into memory.
  while ($next_posts = array_splice($post_ids, 0, $chunk_max_items)) {
    $where = 'WHERE ID IN (' . implode(',', $next_posts) . ')';
    $posts = $wpdb->get_results("SELECT * FROM {$wpdb->posts} $where");

    $chunk = [];

    // Begin Loop.
    foreach ($posts as $post) {
      \setup_postdata($post);

      $item = [
        ContentExporter::SLICE_DATA_POSTS_TITLE => \apply_filters('the_title_export', $post->post_title), // @TODO: should we do it (adapted from wp importer) ? it makes actually no sense since the given data is different ...
        ContentExporter::SLICE_DATA_POSTS_LINK => \the_permalink_rss(), // @TODO: link is not used / imported - should we export it nevertheless ?
        ContentExporter::SLICE_DATA_POSTS_PUBDATE => mysql2date('D, d M Y H:i:s +0000', \get_post_time('Y-m-d H:i:s', true), false),
        ContentExporter::SLICE_DATA_POSTS_CREATOR => \get_the_author_meta('login'),
        ContentExporter::SLICE_DATA_POSTS_GUID => \the_guid(),
        'guid_isPermaLink' => false,
        'description' => null,
        ContentExporter::SLICE_DATA_POSTS_CONTENT => \apply_filters('the_content_export', $post->post_content), // @TODO: should we do it (adapted from wp importer) ? it makes actually no sense since the given data is different ...
        ContentExporter::SLICE_DATA_POSTS_EXCERPT => \apply_filters('the_excerpt_export', $post->post_excerpt), // @TODO: should we do it (adapted from wp importer) ? it makes actually no sense since the given data is different ...
        ContentExporter::SLICE_DATA_POSTS_ID => (int) $post->ID,
        ContentExporter::SLICE_DATA_POSTS_DATE => $post->post_date,
        ContentExporter::SLICE_DATA_POSTS_DATE_GMT => $post->post_date_gmt,
        ContentExporter::SLICE_DATA_POSTS_MODIFIED_GMT => $post->post_modified_gmt,
        ContentExporter::SLICE_DATA_POSTS_COMMENT_STATUS => $post->comment_status,
        ContentExporter::SLICE_DATA_POSTS_PING_STATUS => $post->ping_status,
        ContentExporter::SLICE_DATA_POSTS_NAME => $post->post_name,
        ContentExporter::SLICE_DATA_POSTS_STATUS => $post->post_status,
        ContentExporter::SLICE_DATA_POSTS_PARENT => (int) $post->post_parent,
        ContentExporter::SLICE_DATA_POSTS_MENU_ORDER => (int) $post->menu_order,
        ContentExporter::SLICE_DATA_POSTS_TYPE => $post->post_type,
        ContentExporter::SLICE_DATA_POSTS_PASSWORD => $post->post_password,
        ContentExporter::SLICE_DATA_POSTS_IS_STICKY => (int)\is_sticky($post->ID) ? 1 : 0,
        ContentExporter::SLICE_DATA_POSTS_ATTACHMENT_URL => ('attachment' === $post->post_type) ? \wp_get_attachment_url($post->ID) : null,
        ContentExporter::SLICE_DATA_POSTS_TAXONOMY_TERMS => (function () use ($post) {
          $taxonomies = \get_object_taxonomies($post->post_type);

          return array_map(function ($term) {
            return [
              ContentExporter::SLICE_DATA_TERMS_TAXONOMY => $term->taxonomy,
              ContentExporter::SLICE_DATA_TERMS_SLUG => $term->slug,
              ContentExporter::SLICE_DATA_TERMS_NAME => $term->name,
            ];
          }, empty($taxonomies) ? [] : \wp_get_object_terms($post->ID, $taxonomies));
        })(),
        ContentExporter::SLICE_DATA_POSTS_META => (function (array $postmeta): array {
          return array_map(
            function ($meta) {
              return [
                ContentExporter::SLICE_DATA_TERMS_META_KEY => $meta->meta_key,
                ContentExporter::SLICE_DATA_TERMS_META_VALUE => $meta->meta_value,
              ];
            },
            array_filter(
              $postmeta,
              function ($meta) {
                // @TODO: should we do it (adapted from wp importer) ?
                return !\apply_filters('wxr_export_skip_postmeta', false, $meta->meta_key, $meta);
              }
            )
          );
        })($wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->postmeta WHERE post_id = %d", $post->ID))),
        ContentExporter::SLICE_DATA_POSTS_COMMENTS => (function (array $comments) use ($wpdb): array {
          return array_map(function ($comment) use ($wpdb) {
            return [
              ContentExporter::SLICE_DATA_POSTS_COMMENTS_ID => (int) $comment->comment_ID,
              ContentExporter::SLICE_DATA_POSTS_COMMENTS_AUTHOR => $comment->comment_author,
              ContentExporter::SLICE_DATA_POSTS_COMMENTS_AUTHOR_EMAIL => $comment->comment_author_email,
              ContentExporter::SLICE_DATA_POSTS_COMMENTS_AUTHOR_URL => $comment->comment_author_url,
              ContentExporter::SLICE_DATA_POSTS_COMMENTS_AUTHOR_IP => $comment->comment_author_IP,
              ContentExporter::SLICE_DATA_POSTS_COMMENTS_DATE => $comment->comment_date,
              ContentExporter::SLICE_DATA_POSTS_COMMENTS_DATE_GMT => $comment->comment_date_gmt,
              ContentExporter::SLICE_DATA_POSTS_COMMENTS_CONTENT => $comment->comment_content,
              ContentExporter::SLICE_DATA_POSTS_COMMENTS_APPROVED => $comment->comment_approved,
              ContentExporter::SLICE_DATA_POSTS_COMMENTS_TYPE => $comment->comment_type,
              ContentExporter::SLICE_DATA_POSTS_COMMENTS_PARENT => (int) $comment->comment_parent,
              ContentExporter::SLICE_DATA_POSTS_COMMENTS_USER_ID => (int) $comment->user_id,
              ContentExporter::SLICE_DATA_POSTS_COMMENTS_META => (function (array $metadata): array {
                return array_map(
                  function ($meta) {
                    return [
                      ContentExporter::SLICE_DATA_TERMS_META_KEY => $meta->meta_key,
                      ContentExporter::SLICE_DATA_TERMS_META_VALUE => $meta->meta_value,
                    ];
                  },
                  array_filter(
                    $metadata,
                    function ($meta) {
                      return !\apply_filters('wxr_export_skip_commentmeta', false, $meta->meta_key, $meta);
                    }
                  )
                );
              })($wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->commentmeta WHERE comment_id = %d", $comment->comment_ID))),
            ];
          }, $comments);
        })(array_map(
          'get_comment',
          $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->comments WHERE comment_post_ID = %d AND comment_approved <> 'spam'", $post->ID))
        )),
      ];

      $chunk[] = $item;
    }

    $chunks[] = $chunk;
  }

  return $chunks;
}
