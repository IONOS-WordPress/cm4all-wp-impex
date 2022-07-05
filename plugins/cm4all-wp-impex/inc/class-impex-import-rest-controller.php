<?php

namespace cm4all\wp\impex;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit();
}

require_once __DIR__ . '/class-impex.php';
require_once __DIR__ . '/interface-impex-rest-controller.php';

class ImpexImportRESTController extends \WP_REST_Controller implements ImpexRestController
{
  const WP_FILTER_IMPORT_REST_SLICE_UPLOAD = 'impex-import_filter_rest_slice_upload';

  const REST_BASE = '/import';

  public function __construct()
  {
    $this->namespace = self::NAMESPACE;
    $this->rest_base = self::REST_BASE;
  }

  public function register_routes(): void
  {
    \register_rest_route($this->namespace, self::REST_BASE . '/schema', [
      'methods'  => \WP_REST_Server::READABLE,
      'callback' => [$this, 'get_public_item_schema'],
      'permission_callback' => [$this, 'get_item_permissions_check'],
    ]);
    \register_rest_route($this->namespace, self::REST_BASE, [
      [
        'methods'             => \WP_REST_Server::READABLE,
        'callback'            => [$this, 'get_items'],
        'permission_callback' => [$this, 'get_items_permissions_check'],
        'args'                => [],
        'schema' => [$this, 'get_public_item_schema'],
      ],
      [
        'methods'             => \WP_REST_Server::CREATABLE,
        'callback'            => [$this, 'create_item'],
        'permission_callback' => [$this, 'create_item_permissions_check'],
        'args'                => $this->get_endpoint_args_for_item_schema(\WP_REST_Server::CREATABLE),
      ],
      'schema' => [$this, 'get_public_item_schema'],
    ]);
    \register_rest_route($this->namespace, self::REST_BASE . '/(?P<id>[^/]+)', [
      [
        'methods'             => \WP_REST_Server::READABLE,
        'callback'            => [$this, 'get_item'],
        'permission_callback' => [$this, 'get_item_permissions_check'],
        'args'                => [
          'context' => [
            'default' => 'view',
          ],
        ],
      ],
      [
        'methods'             => \WP_REST_Server::EDITABLE,
        'callback'            => [$this, 'update_item'],
        'permission_callback' => [$this, 'update_item_permissions_check'],
        'args'                => $this->get_endpoint_args_for_item_schema(\WP_REST_Server::EDITABLE),
      ],
      [
        'methods'             => \WP_REST_Server::DELETABLE,
        'callback'            => [$this, 'delete_item'],
        'permission_callback' => [$this, 'delete_item_permissions_check'],
        'args'                => [
          'force' => [
            'default' => false,
          ],
        ],
      ],
      'schema' => [$this, 'get_public_item_schema'],
    ]);
    \register_rest_route($this->namespace, self::REST_BASE . '/(?P<id>[^/]+)/slice', [
      [
        'methods'             => \WP_REST_Server::CREATABLE,
        'callback'            => [$this, 'upsert_item_slice'],
        'permission_callback' => [$this, 'create_item_permissions_check'],
        // @TODO: attach the correct schema for the post body
        'args'                => [
          'position' => [
            'description'       => 'slice position column in database',
            'type'              => 'integer',
            'default'           => 0,
            'sanitize_callback' => 'absint',
            'required'          => true
          ],
        ],
      ],
    ]);
    \register_rest_route($this->namespace, self::REST_BASE . '/(?P<id>[^/]+)/consume', [
      [
        'methods'             => \WP_REST_Server::EDITABLE,
        'callback'            => [$this, 'consume'],
        'permission_callback' => [$this, 'consume_permissions_check'],
        // @TODO: attach the correct schema for the post body
        'args'                => [
          /*
          'profile' => [
            'description'       => 'import profile to use',
            'type'              => 'string',
            'required'          => true,
            'validate_callback' => function ($value, $request, $key) {
              $profile = Impex::getInstance()->Import->getProfile($value);
              return $profile !== null;
            },
          ],
          */
          'offset' => [
            'description'       => 'Offset at which to start consuming',
            'type'              => 'integer',
            'default'           => 0,
            'sanitize_callback' => 'absint',
          ],
          'limit' => [
            'description'       => 'Lmit at which to end consuming',
            'type'              => 'integer',
            'default'           => PHP_INT_MAX,
            'sanitize_callback' => 'absint',
          ]
        ],
      ],
    ]);
  }

  /**
   * Get a collection of items
   *
   * @param WP_REST_Request $request Full data about the request.
   * @return WP_Error|WP_REST_Response
   */
  public function get_items($request)
  {
    $items = \get_option(ImpexImport::WP_OPTION_IMPORTS, []);
    $data = [];
    foreach ($items as $item) {
      $itemdata = $this->prepare_item_for_response($item, $request);
      // @TODO: the prepare_response_for_collection is completely wrong - response is the expected argument !!
      $data[] = $this->prepare_response_for_collection($itemdata);
    }

    return new \WP_REST_Response($data, 200);
  }

  /**
   * @param WP_REST_Request $request Full data about the request.
   * @return WP_Error|WP_REST_Response
   */
  public function upsert_item_slice($request)
  {
    // @TODO: add validation callback to request args to handle this check instead of doing it here manually
    if (!$request->offsetExists('slice')) {
      return new \WP_Error('bad-request', __('Multipart parameter slice is missing', 'cm4all-wp-impex'), ['status' => 400]);
    }

    /** @var wpdb */
    global $wpdb;

    /** @var string */
    $snapshot_id = $request->get_param('id');
    foreach (\get_option(ImpexImport::WP_OPTION_IMPORTS, []) as $import) {
      if ($import['id'] === $snapshot_id) {
        $transformationContext = ImpexImportTransformationContext::fromJson($import);

        $position = $request->get_param('position');

        /** @var array */
        $slice = /*$request->is_json_content_type() ? $request->get_json_params() :*/ json_decode($request->get_param("slice"), JSON_OBJECT_AS_ARRAY);
        if (!is_array($slice)) {
          return new \WP_Error('bad-request', __('Multipart parameter "slice" is expected to contain json', 'cm4all-wp-impex'), ['status' => 400]);
        }

        $slice = \apply_filters(self::WP_FILTER_IMPORT_REST_SLICE_UPLOAD, $slice, $transformationContext, $request);

        $success = Impex::getInstance()->Import->_upsert_slice($snapshot_id, $position, $slice);

        if ($success === false) {
          throw new ImpexImportRuntimeException(sprintf('failed to insert/update jsonized slice(=%s) to database : %s', $slice, $wpdb->last_error));
        }

        $response = new \WP_REST_Response(true, 200);
        return $response;
      }
    }

    return new \WP_Error('not-found', __('could not find import by id', 'cm4all-wp-impex'), ['status' => 404]);
  }

  public function consume($request)
  {
    //$profile = Impex::getInstance()->Import->getProfile($request->get_param('profile'));
    $snapshot_id = $request->get_param('id');
    //$options = $request->get_json_params();

    $limit = $request->get_param('limit');
    $offset = $request->get_param('offset');

    foreach (\get_option(ImpexImport::WP_OPTION_IMPORTS, []) as $import) {
      if ($import['id'] === $snapshot_id) {
        $transformationContext = ImpexImportTransformationContext::fromJson($import);

        $notConsumedSlices = Impex::getInstance()->Import->consume($transformationContext, $limit, $offset);

        $notConsumedSlices = $this->prepare_item_for_response($notConsumedSlices, $request);

        $response = new \WP_REST_Response(['notConsumedSlices' => $notConsumedSlices, 'log' => $transformationContext->log], 200);
        return $response;
      }
    }

    return new \WP_Error('not-found', __('could not find import by id', 'cm4all-wp-impex'), ['status' => 404]);
  }

  public function consume_permissions_check($request)
  {
    // @TODO: check a more specific permission
    return \current_user_can('import');
  }

  /**
   * Get one item from the collection
   *
   * @param WP_REST_Request $request Full data about the request.
   * @return WP_Error|WP_REST_Response
   */
  public function get_item($request)
  {
    $id = $request->get_param('id');

    foreach (\get_option(ImpexImport::WP_OPTION_IMPORTS, []) as $import) {
      if ($import['id'] === $id) {
        return new \WP_REST_Response($this->prepare_item_for_response($import, $request), 200);
      }
    }

    return new \WP_Error('not-found', __('could not find import by id', 'cm4all-wp-impex'), ['status' => 404]);
  }

  /**
   * Create one item from the collection
   *
   * @param WP_REST_Request $request Full data about the request.
   * @return WP_Error|WP_REST_Response
   */
  public function create_item($request)
  {
    $item = $this->prepare_item_for_database($request);

    if (!is_array($item)) {
      return new \WP_Error('cant-create', __('body expected to be a json object', 'cm4all-wp-impex'), ['status' => 500]);
    }

    $profile = Impex::getInstance()->Import->getProfile($item['profile']);
    if (!$profile) {
      return new \WP_Error('cant-create', sprintf('could not find import profile(name="")', $item['profile']), ['status' => 500]);
    }

    $transformationResult = Impex::getInstance()->Import->create(
      profile: $profile,
      options: $item['options'] ?? [],
      name: $item['name'] ?? '',
      description: $item['description'] ?? ''
    );
    return new \WP_REST_Response($transformationResult->jsonSerialize());
  }

  /**
   * Update one item from the collection
   *
   * @param WP_REST_Request $request Full data about the request.
   * @return WP_Error|WP_REST_Response
   */
  public function update_item($request)
  {
    $id = $request->get_param('id');
    $item = (array)$this->prepare_item_for_database($request);

    $result = Impex::getInstance()->Import->update($id, $item);
    if (is_array($result)) {
      return new \WP_REST_Response($result, 200);
    }

    return new \WP_Error('not-found', __('Failed to update export', 'cm4all-wp-impex'), ['status' => 404]);
  }

  /**
   * Delete one item from the collection
   *
   * @param WP_REST_Request $request Full data about the request.
   * @return WP_Error|WP_REST_Response
   */
  public function delete_item($request)
  {
    $id = $request->get_param('id');

    $result = Impex::getInstance()->Import->remove($id);

    if ($result !== false) {
      return new \WP_REST_Response(true, 200);
    }

    return new \WP_Error('cant-delete', __('removing impex export failed', 'cm4all-wp-impex'), ['status' => 500]);
  }

  /**
   * Check if a given request has access to get items
   *
   * @param WP_REST_Request $request Full data about the request.
   * @return WP_Error|bool
   */
  public function get_items_permissions_check($request)
  {
    // TODO: make permission more explicit
    return \current_user_can('import');
  }

  /**
   * Check if a given request has access to get a specific item
   *
   * @param WP_REST_Request $request Full data about the request.
   * @return WP_Error|bool
   */
  public function get_item_permissions_check($request)
  {
    return $this->get_items_permissions_check($request);
  }

  /**
   * Check if a given request has access to create items
   *
   * @param WP_REST_Request $request Full data about the request.
   * @return WP_Error|bool
   */
  public function create_item_permissions_check($request)
  {
    // TODO: make permission more explicit
    return \current_user_can('import');
  }

  /**
   * Check if a given request has access to update a specific item
   *
   * @param WP_REST_Request $request Full data about the request.
   * @return WP_Error|bool
   */
  public function update_item_permissions_check($request)
  {
    // TODO: make permission more explicit
    return $this->create_item_permissions_check($request);
  }

  /**
   * Check if a given request has access to delete a specific item
   *
   * @param WP_REST_Request $request Full data about the request.
   * @return WP_Error|bool
   */
  public function delete_item_permissions_check($request)
  {
    // TODO: make permission more explicit
    return $this->create_item_permissions_check($request);
  }

  /**
   * Prepare the item for create or update operation
   *
   * @param WP_REST_Request $request Request object
   * @return WP_Error|object $prepared_item
   */
  protected function prepare_item_for_database($request)
  {
    return $request->is_json_content_type() ? $request->get_json_params() : $request->get_params();
  }

  /**
   * Prepare the item for the REST response
   *
   * @param mixed $item WordPress representation of the item.
   * @param WP_REST_Request $request Request object.
   * @return mixed
   */
  public function prepare_item_for_response($item, $request)
  {
    return $item;
  }

  /**
   * Get the query params for collections
   */
  public function get_collection_params(): array
  {
    $collection_params = parent::get_collection_params();

    $collection_params['context']['default'] = 'view';

    $collection_params['offset'] = [
      'description'       => 'Offset at which to start retrieving',
      'type'              => 'integer',
      'default'           => 0,
      'sanitize_callback' => 'absint',
    ];

    unset($collection_params['search']);

    return $collection_params;
  }

  /**
   * @TODO: finalize schema configuration using https://wordpress.org/plugins/wp-api-swaggerui/
   * 
   * @link https://wp-plugin-erstellen.de/ebook/rest-api/erweitern/controller-klassen/wp-rest-controller/
   * @link https://github.com/WordPress-Plugin-Programmierer/ebook-snippets/blob/master/20.4.6-track-external-links/inc/rest.php
   */
  public function get_item_schema(): array
  {
    if ($this->schema) {
      return $this->add_additional_fields_schema($this->schema);
    }

    $schema = [
      '$schema'    => 'http://json-schema.org/draft-04/schema#',
      'title'      => self::REST_BASE,
      'type'       => 'object',
      'properties' => [
        'id' => [
          'description' => __('Unique identifier for the import.', 'cm4all-wp-impex'),
          'type'        => 'string',
          'format'      => 'uuid',
          'context'     => ['view', 'edit'],
          'readonly'    => true,
        ],
        'options' => [
          'description' => __('The options used to create the import.', 'cm4all-wp-impex'),
          // its actually an array butt needs to be declared as object 
          // according to https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/#type-juggling
          'type'        => 'object',
          'context'     => ['view', 'edit'],
          'default'     => [],
        ],
        'profile' => [
          'description' => __('The name of the import profile to use.', 'cm4all-wp-impex'),
          'type'        => 'string',
          'context'     => ['view', 'edit'],
          /*
          // @TODO: example doesnt work that perfect in swagger ... 
          'example'     => \wp_list_pluck( iterator_to_array(Impex::getInstance()->Import->getProfiles()), 'name)),
          */
        ],
        'created' => [
          'description' => __('The date of the import.', 'cm4all-wp-impex'),
          'type'        => 'string',
          'format'      => 'date-time',
          'context'     => ['view', 'edit'],
          'readonly'    => true,
        ],
        'user' => [
          'description' => __('The user login of the user creating the import.', 'cm4all-wp-impex'),
          'type'        => 'string',
          'context'     => ['view', 'edit'],
          'readonly'    => true,
        ],
        'name' => [
          'description' => __('The name of the import', 'cm4all-wp-impex'),
          'type'        => 'string',
          'context'     => ['view', 'edit'],
          'minLength'   => 1,
          'pattern'     => '[a-zA-Z_\-]+',
        ],
        'description' => [
          'description' => __('The description of the import', 'cm4all-wp-impex'),
          'type'        => 'string',
          'context'     => ['view', 'edit'],
          'default'     => '',
        ],
      ],
      'additionalProperties' => true,
    ];

    $this->schema = $schema;

    return $this->add_additional_fields_schema($this->schema);
  }
}

\add_action(
  'rest_api_init',
  function () {
    $controller = new ImpexImportRESTController();
    $controller->register_routes();
  },
);
