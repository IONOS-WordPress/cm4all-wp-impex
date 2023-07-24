<?php

namespace cm4all\wp\impex\tests\phpunit;

use cm4all\wp\impex\ContentImporter;
use cm4all\wp\impex\ContentExporter;
use cm4all\wp\impex\Impex;
use cm4all\wp\impex\ImpexImportRuntimeException;

use function cm4all\wp\impex\__registerContentExportProvider;
use function cm4all\wp\impex\__registerContentImportProvider;

class TestImpexImportExtensionContent extends ImpexUnitTestcase
{
  function setUp()
  {
    parent::setUp();

    \add_filter(
      'import_allow_create_users',
      '__return_true',
    );

    global $wpdb;
    // crude but effective: make sure there's no residual data in the main tables
    foreach (['posts', 'postmeta', 'comments', 'terms', 'term_taxonomy', 'term_relationships', 'users', 'usermeta'] as $table) {
      // phpcs:ignore WordPress.DB.PreparedSQL
      $wpdb->query("DELETE FROM {$wpdb->$table}");
    }

    /*
    * @TODO: remove when testcase is working !
    */
    // in case of broken phpunit calls old uploads may exist within $ignored_files (populated in setUp)
    // to ensure these will be cleaned up properly we force to forget about these intermediate file uploads
    self::$ignore_files = [];

    // remove all uploads
    $this->remove_added_uploads();

    // prevent wordpress creating various image derivates
    \add_filter('intermediate_image_sizes_advanced', function ($sizes, $metadata) {
      return [];
    }, 10, 2);
  }

  function tearDown()
  {
    parent::tearDown();

    \remove_filter('import_allow_create_users', '__return_true');

    // in case of broken phpunit calls old uploads may exist within $ignored_files (populated in setUp)
    // to ensure these will be cleaned up properly we force to forget about these intermediate file uploads
    self::$ignore_files = [];

    // remove all uploads
    $this->remove_added_uploads();

    parent::tearDown();
  }

  /*
    https://themecoder.de/2018/01/25/aufbau-und-tabellenstruktur-der-wordpress-datenbank/
    http://dev.intern.cm-ag/nova/homepolder/cm4all-files/-/blob/develop/howto/HOWTO_TERMS_DB.md
    https://code.tutsplus.com/tutorials/understanding-and-working-with-relationships-between-data-in-wordpress--cms-20632
  */
  function _createImpexImportTransformationContextMock()
  {
    if (!Impex::getInstance()->Import->hasProfile('dummy_profile')) {
      Impex::getInstance()->Import->addProfile('dummy_profile');
    }

    $importContext = Impex::getInstance()->Import->create(Impex::getInstance()->Import->getProfile('dummy_profile'));

    // copy images to uploads_subpath of this import
    $IMPORT_PATH = $importContext->path . '/2021/08';
    $success = \wp_mkdir_p($IMPORT_PATH);

    $this->assertTrue(copy(__DIR__ . '/fixtures/uploads/images/wood.jpg', $IMPORT_PATH . '/wood.jpg'));
    $this->assertTrue(copy(__DIR__ . '/fixtures/uploads/images/wood.jpg', $IMPORT_PATH . '/wood-1.jpg'));

    return $importContext;
  }

  function testContentImporterProvider(): void
  {
    $Import = Impex::getInstance()->Import;
    $this->assertEmpty(iterator_to_array($Import->getProviders()), 'there should be no import providers registered');

    $provider = __registerContentImportProvider();

    $importProviders = iterator_to_array($Import->getProviders());
    $this->assertEquals(1, count($importProviders), 'there should be exactly one import provider registered');
    $this->assertEquals($provider, $importProviders[0], ContentImporter::PROVIDER_NAME . ' should be the registered provider');
    $this->assertEquals($provider->name, $importProviders[0]->name, ContentImporter::PROVIDER_NAME . ' should be the name of the provider');
  }

  function testContentImporterUsers()
  {
    $slices = (function (): array {
      $provider = __registerContentExportProvider();

      // create post with mapped user
      \wp_set_current_user(self::factory()->user->create(['role' => 'editor', 'user_login' => 'exported_user']));
      self::factory()->post->create(['post_title'   => "post from exported_user",]);

      \wp_set_current_user(self::factory()->user->create(['role' => 'editor', 'user_login' => 'exported_user2']));
      self::factory()->post->create(['post_title'   => "post from exported_user2",]);

      // create post with existing user
      \wp_set_current_user(self::factory()->user->create(['role' => 'editor', 'user_login' => 'existing_user']));
      self::factory()->post->create(['post_title'   => "post from existing_user",]);

      // create post with user that does not exist when import
      $non_existing_user_id = self::factory()->user->create(['role' => 'editor', 'user_login' => 'non_existing_user']);
      \wp_set_current_user($non_existing_user_id);
      self::factory()->post->create(['post_title'   => "post from non_existing_user",]);

      // create post from user with different id on target system
      $user_with_different_id = self::factory()->user->create(['role' => 'editor', 'user_login' => 'user_with_different_id']);
      \wp_set_current_user($user_with_different_id);
      self::factory()->post->create(['post_title'   => "post from user_with_different_id",]);

      $exportGenerator = call_user_func($provider->callback, [], $this->createImpexExportTransformationContextMock(),);

      $slices = iterator_to_array($exportGenerator);

      // delete created user
      \wp_delete_user($non_existing_user_id);

      // delete user and recreate with different id
      \wp_delete_user($user_with_different_id);
      $user_with_different_id = self::factory()->user->create(['role' => 'editor', 'user_login' => 'user_with_different_id']);

      \_delete_all_posts();

      return $slices;
    })();

    $provider = __registerContentImportProvider();
    $importContext = $this->_createImpexImportTransformationContextMock();

    // test import with filter'import_allow_create_users' = false
    try {
      \add_filter('import_allow_create_users', '__return_false');
      call_user_func($provider->callback, $slices[0], [], $importContext,);
      $this->fail('user import mapping with a non existent user shouild fail');
    } catch (\Throwable $t) {
      $this->assertIsObject($t);
      \remove_filter('import_allow_create_users', '__return_false');
    }

    // test import with invalid option import user name
    try {
      call_user_func($provider->callback, $slices[0], [
        'users' => [
          'exported_user' => 'bogus_user',
        ]
      ], $importContext,);
      $this->fail('user import mapping with a non existent user shouild fail');
    } catch (\Throwable $t) {
      $this->assertIsObject($t);
    }

    // test import with invalid option import user id
    try {
      call_user_func($provider->callback, $slices[0], [
        'users' => [
          'exported_user' => PHP_INT_MAX,
        ]
      ], $importContext,);
      $this->fail('user import mapping with a non existent user id shouild fail');
    } catch (\Throwable $t) {
      $this->assertIsObject($t);
    }

    // test import with invalid option import user id
    try {
      call_user_func($provider->callback, $slices[0], [
        'users' => [
          'exported_user' => 0.815,
        ]
      ], $importContext,);
      $this->fail('user import mapping with a invalid user mapping value should fail');
    } catch (\Throwable $t) {
      $this->assertIsObject($t);
    }

    // test import with valid user options
    $new_id_exported_user2 = self::factory()->user->create(['role' => 'editor', 'user_login' => 'new_id_exported_user2']);
    $users_before_import = \get_users();
    $this->assertCount(6, $users_before_import);
    $retVal = call_user_func($provider->callback, $slices[0], [
      'users' => [
        // assign imported posts created by user 'exported_user' to user 'existing_user'
        'exported_user' => 'existing_user',
        'exported_user2' => $new_id_exported_user2,
      ]
    ], $importContext,);
    $this->assertTrue($retVal, 'importer callback expected to return successful');

    $users_after_import = \get_users();
    $this->assertCount(7, $users_after_import);

    // ensure that a user was created for nooexistent user 'non_existing_user'
    $this->assertEmpty(array_filter($users_before_import, fn ($user) => $user->data->user_login === 'non_existing_user'));
    $this->assertCount(1, array_filter($users_after_import, fn ($user) => $user->data->user_login === 'non_existing_user'));

    // test imported post is attached to user
    // $post = \get_page_by_title('post from exported_user', \OBJECT, 'post');
    // \get_page_by_title iss deprecated since wp 6.2
    $posts = \get_posts([
      'post_type'              => 'post',
      'title'                  => 'post from exported_user',
      'post_status'            => 'all',
      'numberposts'            => 1,
    ]);
    $post = ! empty( $posts ) ? $post = $posts[0] : null;

    $this->assertNotNull($post, 'post should be recreated by import');
    // we have assigned the post to user 'existing_user' in the import options
    $post_user = \get_user_by('login', $post->post_author);
    $this->assertEquals($post->post_author, \get_user_by('login', 'existing_user')->ID);

    //$post = \get_page_by_title('post from exported_user2', \OBJECT, 'post');
    // \get_page_by_title iss deprecated since wp 6.2
    $posts = \get_posts([
      'post_type'              => 'post',
      'title'                  => 'post from exported_user2',
      'post_status'            => 'all',
      'numberposts'            => 1,
    ]);
    $post = ! empty( $posts ) ? $post = $posts[0] : null;

    $this->assertNotNull($post, 'post should be recreated by import');
    // we have assigned the post to user 'new_id_exported_user2' in the import options
    $this->assertEquals($post->post_author, $new_id_exported_user2);

    //$post = \get_page_by_title('post from existing_user', \OBJECT, 'post');
    // \get_page_by_title iss deprecated since wp 6.2
    $posts = \get_posts([
      'post_type'              => 'post',
      'title'                  => 'post from existing_user',
      'post_status'            => 'all',
      'numberposts'            => 1,
    ]);
    $post = ! empty( $posts ) ? $post = $posts[0] : null;

    $this->assertNotNull($post, 'post should be recreated by import');
    $this->assertEquals($post->post_author, \get_user_by('login', 'existing_user')->ID);

    // $post = \get_page_by_title('post from non_existing_user', \OBJECT, 'post');
    // \get_page_by_title iss deprecated since wp 6.2
    $posts = \get_posts([
      'post_type'              => 'post',
      'title'                  => 'post from non_existing_user',
      'post_status'            => 'all',
      'numberposts'            => 1,
    ]);
    $post = ! empty( $posts ) ? $post = $posts[0] : null;
    $this->assertNotNull($post, 'post should be recreated by import');
    $this->assertEquals($post->post_author, \get_user_by('login', 'non_existing_user')->ID);

    // $post = \get_page_by_title('post from user_with_different_id', \OBJECT, 'post');
    // \get_page_by_title iss deprecated since wp 6.2
    $posts = \get_posts([
      'post_type'              => 'post',
      'title'                  => 'post from user_with_different_id',
      'post_status'            => 'all',
      'numberposts'            => 1,
    ]);
    $post = ! empty( $posts ) ? $post = $posts[0] : null;

    $this->assertNotNull($post, 'post should be recreated by import');
    $this->assertEquals($post->post_author, \get_user_by('login', 'user_with_different_id')->ID);
  }

  function testContentImporterCategories()
  {
    /*
      setup test data
    */
    $slices = (function (): array {
      $provider = __registerContentExportProvider();

      // create post
      \wp_set_current_user(self::factory()->user->create(['role' => 'editor', 'user_login' => 'editor']));
      $post_id = self::factory()->post->create(['post_title' => "post with simple category",]);

      // create categories
      $simple_category_id = self::factory()->category->create(['name' => 'simple',]);

      $ne_category_id = self::factory()->category->create(['name' => 'ne',]);
      $a_category_id = self::factory()->category->create(['name' => 'a',]);
      $ab_category_id = self::factory()->category->create(['name' => 'ab', 'parent' => $a_category_id]);
      $bc_category_id = self::factory()->category->create(['name' => 'bc', 'parent' => $ab_category_id]);

      $categories_to_delete_after_export = [$ne_category_id, $a_category_id, $ab_category_id, $bc_category_id];

      // attach categories to post
      $ids = self::factory()->category->add_post_terms($post_id, [$ne_category_id, $simple_category_id, $bc_category_id], 'category');

      $exportGenerator = call_user_func($provider->callback, [], $this->createImpexExportTransformationContextMock(),);
      $slices = iterator_to_array($exportGenerator);

      // delete categories to force recreation by import
      foreach ($categories_to_delete_after_export as $category_id) {
        \wp_delete_category($category_id);
      }

      \_delete_all_posts();

      return $slices;
    })();

    $provider = __registerContentImportProvider();
    $importContext = $this->_createImpexImportTransformationContextMock();

    // ensure the no posts and just the simple category exists
    $categories =  \get_categories(['hide_empty' => false,]);
    $this->assertCount(1, $categories);
    $this->assertTrue(in_array('simple', array_column($categories, 'name')), 'only category "simple" should exist');
    $this->assertEmpty(\get_posts(), 'no posts should exist');

    // execute import
    $retVal = call_user_func($provider->callback, $slices[0], [], $importContext,);
    $this->assertTrue($retVal, 'importer callback expected to return successful');

    // ensure the all exported categories exist
    $categories =  \get_terms('category', ['hide_empty' => false,]);

    $this->assertCount(5, $categories, 'the not existent category should be recreated');
    $this->assertTrue(in_array('ne', array_column($categories, 'name')), 'the not existent category should be recreated');

    $posts = \get_posts();
    $this->assertCount(1, $posts);

    $post_category_names = array_column(\wp_get_post_categories($posts[0]->ID, ['fields' => 'all', ['orderby' => 'name']]), 'name');
    $this->assertCount(3, $post_category_names);

    $this->assertEquals(['bc', 'ne', 'simple'], $post_category_names);
  }

  function testContentImporterTerms()
  {
    /*
      setup test data
    */
    $slices = (function (): array {
      $provider = __registerContentExportProvider();

      /*
        taxonomy "products" structure
          term "vegetables"
            term "carrots"
            term "potatoes"
          term "fruits"
            term "apples"
            term "pears"
      */

      $TAXONOMY_NAME = 'products';

      // create post
      \wp_set_current_user(self::factory()->user->create(['role' => 'editor', 'user_login' => 'editor']));
      $post_id = self::factory()->post->create(['post_title' => "post with custom terms",]);

      // create custom taxonomy
      $taxonomy = \register_taxonomy($TAXONOMY_NAME, 'post', ['hierarchical' => true,]);

      // create custom taxonomy terms
      $food_id = self::factory()->term->create(['name' => 'food', 'taxonomy' => $taxonomy->name,]);
      $vegetables_id = self::factory()->term->create(['name' => 'vegetables', 'taxonomy' => $taxonomy->name, 'parent' => $food_id]);
      $carrots_id = self::factory()->term->create(['name' => 'carrots', 'taxonomy' => $taxonomy->name, 'parent' => $vegetables_id]);
      $potatoes_id = self::factory()->term->create(['name' => 'potatoes', 'taxonomy' => $taxonomy->name, 'parent' => $vegetables_id]);

      $fruits_id = self::factory()->term->create(['name' => 'fruits', 'taxonomy' => $taxonomy->name, 'parent' => $food_id]);
      $apples_id = self::factory()->term->create(['name' => 'apples', 'taxonomy' => $taxonomy->name, 'parent' => $fruits_id]);
      $pears_id = self::factory()->term->create(['name' => 'pears', 'taxonomy' => $taxonomy->name, 'parent' => $fruits_id]);

      $terms_to_delete_after_export = [$carrots_id, $vegetables_id, $potatoes_id,];

      // attach terms to post
      self::factory()->term->add_post_terms($post_id, [$carrots_id, $potatoes_id, $apples_id, $pears_id], $TAXONOMY_NAME);

      $exportGenerator = call_user_func($provider->callback, [], $this->createImpexExportTransformationContextMock(),);
      $slices = iterator_to_array($exportGenerator);

      // delete terms to force recreation by import
      foreach ($terms_to_delete_after_export as $term_id) {
        \wp_delete_term($term_id, $TAXONOMY_NAME);
      }

      \_delete_all_posts();

      return $slices;
    })();

    $provider = __registerContentImportProvider();
    $importContext = $this->_createImpexImportTransformationContextMock();

    // ensure the no posts and just the "fruity" terms exist
    $terms =  \get_terms(['hide_empty' => false, 'taxonomy' => 'products',]);
    $this->assertCount(4, $terms, 'only terms "food", "fruits", "apples" and "pears" should exist');
    $this->assertEmpty(\get_posts(), 'no posts should exist');

    // execute import
    $retVal = call_user_func($provider->callback, $slices[0], [], $importContext,);
    $this->assertTrue($retVal, 'importer callback expected to return successful');

    // ensure the all exported terms exist
    $terms = \get_terms(['taxonomy' => 'products', 'hide_empty' => false,]);

    $this->assertCount(7, $terms, 'the not existent "veggie" terms should be recreated');

    $posts = \get_posts(['post_type' => 'post', 'post_status' => 'any',]);
    $this->assertCount(1, $posts);

    // delete all terms
    foreach ($terms as $term) {
      \wp_delete_term($term->term_id, $term->taxonomy);
    }

    \_delete_all_posts();

    // delete taxonomy
    $success = \unregister_taxonomy('products');
    $this->assertTrue($success, 'taxonomy "products" should be unregistered');

    // execute import
    $retVal = call_user_func($provider->callback, $slices[0], [], $importContext,);
    $this->assertTrue($retVal, 'importer callback expected to return successful');

    list($post) = \get_posts();

    $attached_product_terms = \wp_get_post_terms($post->ID, 'products', ['fields' => 'all', ['orderby' => 'name']]);
    $attached_product_term_names = array_column($attached_product_terms, 'name');

    $this->assertEquals(['apples', 'carrots', 'pears', 'potatoes'], $attached_product_term_names);

    // ensure term hierarchy is restored correctly
    $product_terms = \get_terms(['taxonomy' => 'products', 'hide_empty' => false,]);

    $product_terms_byName = array_combine(array_column($product_terms, 'name'), $product_terms);

    $this->assertEquals(
      $product_terms_byName['carrots']->parent,
      $product_terms_byName['vegetables']->term_id
    );

    $this->assertEquals(
      $product_terms_byName['potatoes']->parent,
      $product_terms_byName['vegetables']->term_id
    );

    $this->assertEquals(
      $product_terms_byName['vegetables']->parent,
      $product_terms_byName['food']->term_id
    );

    $this->assertEquals(
      $product_terms_byName['apples']->parent,
      $product_terms_byName['fruits']->term_id
    );

    $this->assertEquals(
      $product_terms_byName['pears']->parent,
      $product_terms_byName['fruits']->term_id
    );

    $this->assertEquals(
      $product_terms_byName['fruits']->parent,
      $product_terms_byName['food']->term_id
    );

    $this->assertEquals(
      $product_terms_byName['food']->parent,
      0
    );
  }

  function testContentImporterTags()
  {
    /*
      setup test data
    */
    $slices = (function (): array {
      $provider = __registerContentExportProvider();

      // create post
      \wp_set_current_user(self::factory()->user->create(['role' => 'editor', 'user_login' => 'editor']));
      $post_id = self::factory()->post->create(['post_title' => "post with tags",]);

      // create tags
      $alpha_id = self::factory()->tag->create(['name' => 'alpha',]);
      $beta_id = self::factory()->tag->create(['name' => 'beta',]);
      $gamma_id = self::factory()->tag->create(['name' => 'gamma',]);
      $delta_id = self::factory()->tag->create(['name' => 'delta',]);

      $terms_to_delete_after_export = [$gamma_id, $delta_id,];

      // attach terms to post
      self::factory()->tag->add_post_terms($post_id, [$alpha_id, $beta_id, $gamma_id, $delta_id,], 'post_tag');

      $exportGenerator = call_user_func($provider->callback, [], $this->createImpexExportTransformationContextMock(),);
      $slices = iterator_to_array($exportGenerator);

      // delete categories to force recreation by import
      foreach ($terms_to_delete_after_export as $term_id) {
        \wp_delete_term($term_id, 'post_tag');
      }

      \_delete_all_posts();

      return $slices;
    })();

    $provider = __registerContentImportProvider();
    $importContext = $this->_createImpexImportTransformationContextMock();

    // ensure the no posts and just 2 tags exist
    $tags =  \get_tags(['hide_empty' => false,]);
    $this->assertCount(2, $tags, 'only terms "alpha", and "beta" should exist');

    // execute import
    $retVal = call_user_func($provider->callback, $slices[0], [], $importContext,);
    $this->assertTrue($retVal, 'importer callback expected to return successful');

    // ensure the all exported categories exist
    $terms = \get_tags(['hide_empty' => false,]);

    $this->assertCount(4, $terms, 'the not existent "veggie" terms should be recreated');

    $posts = \get_posts();
    $this->assertCount(1, $posts);

    $post_tag_names = array_column(wp_get_post_tags($posts[0]->ID, ['orderby' => 'name']), 'name');
    $this->assertEquals(['alpha', 'beta', 'delta', 'gamma',], $post_tag_names);
  }

  function testContentImporterNavMenu(): void
  {
    /*
      setup test data
    */
    $slices = (function (): array {
      $provider = __registerContentExportProvider();

      // create a user
      $user = self::factory()->user->create(['user_login' => 'editor']);
      \wp_set_current_user($user);

      // create a nav menu
      $navmenu_id = \wp_create_nav_menu('my navmenu');

      // create some posts and attach them to the nav menu
      $post_ids = self::factory()->post->create_many(5, ['post_type' => 'post',]);

      foreach ($post_ids as $post_id) {
        \wp_update_nav_menu_item(
          $navmenu_id,
          0,
          [
            'menu-item-object-id' => $post_id,
            'menu-item-object'    => 'post',
            'menu-item-type' => 'post_type',
            'menu-item-status' => 'publish',
          ]
        );
      }

      // add a category to nav menu
      \wp_update_nav_menu_item(
        $navmenu_id,
        0,
        [
          'menu-item-type'      => 'taxonomy',
          'menu-item-object'    => 'category',
          'menu-item-object-id' => self::factory()->category->create(['name' => 'my category',]),
          'menu-item-status'    => 'publish',
        ]
      );

      // add a tag to nav menu
      \wp_update_nav_menu_item(
        $navmenu_id,
        0,
        [
          'menu-item-type'      => 'taxonomy',
          'menu-item-object'    => 'post_tag',
          'menu-item-object-id' => self::factory()->tag->create(['name' => 'my tag',]),
          'menu-item-status'    => 'publish',
        ]
      );

      $menu_items = \wp_get_nav_menu_items($navmenu_id);

      $exportGenerator = call_user_func($provider->callback, [], $this->createImpexExportTransformationContextMock(),);
      $slices = iterator_to_array($exportGenerator);

      \_delete_all_data();

      $menu_items = \wp_get_nav_menu_items($navmenu_id);

      return $slices;
    })();

    $provider = __registerContentImportProvider();
    $importContext = $this->_createImpexImportTransformationContextMock();

    // execute import
    $retVal = call_user_func($provider->callback, $slices[0], [], $importContext,);
    $this->assertTrue($retVal, 'importer callback expected to return successful');

    // test user imported
    $user = \get_users(['user_login' => 'editor',]);
    $this->assertCount(1, $user);
    $this->assertEquals('editor', $user[0]->user_login);

    // test category imported
    $categories = \get_categories(['hide_empty' => false,]);
    $this->assertCount(1, $categories);
    $this->assertEquals('my category', $categories[0]->name);

    // test category imported
    $tags = \get_tags(['hide_empty' => false,]);
    $this->assertCount(1, $tags);
    $this->assertEquals('my tag', $tags[0]->name);

    // test nav menu imported
    $nav_menus = \wp_get_nav_menus();
    $this->assertCount(1, $nav_menus);
    $this->assertEquals('my navmenu', $nav_menus[0]->name);

    // test posts imported
    $posts = \get_posts();
    $this->assertCount(5, $posts);

    $nav_menu_items = \wp_get_nav_menu_items($nav_menus[0]->term_id);
    $this->assertCount(7, $nav_menu_items);

    $this->assertCount(
      1,
      array_filter($nav_menu_items, fn ($wpPost) => $wpPost->object === 'post_tag' && $wpPost->object_id == $tags[0]->term_id),
      'nav_menu should be associated with tag'
    );

    $this->assertCount(
      1,
      array_filter($nav_menu_items, fn ($wpPost) => $wpPost->object === 'category' && $wpPost->object_id == $categories[0]->term_id),
      'nav_menu should be associated with category'
    );

    // test nav menu - posts association
    //\wp_get_nav_menu_object($nav_menus[0]->term_id);
    $menu_items = \wp_get_nav_menu_items($nav_menus[0]->term_id);

    $this->assertCount(
      5,
      array_filter($menu_items, fn ($wpPost) => $wpPost->object === 'post'),
      'each post should be associated with the nav_menu'
    );
  }

  /**
   * @doesNotPerformAssertions
   */
  function testContentImporterCustomPostType(): void
  {
  }

  /**
   * @doesNotPerformAssertions
   */
  function testContentImporterFull(): void
  {
  }
}
