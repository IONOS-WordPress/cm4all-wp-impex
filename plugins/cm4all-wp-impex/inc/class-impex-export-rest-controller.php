<?php

namespace cm4all\wp\impex;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit();
}

require_once __DIR__ . '/class-impex.php';
require_once __DIR__ . '/interface-impex-rest-controller.php';

class ImpexExportRESTController extends \WP_REST_Controller implements ImpexRestController
{
  const REST_BASE = '/export';

  const WP_FILTER_EXPORT_SLICE_REST_MARSHAL = 'impex_exports_filter_rest_marshal';

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
        'methods'             => \WP_REST_Server::READABLE,
        'callback'            => [$this, 'get_item_slices'],
        'permission_callback' => [$this, 'get_item_permissions_check'],
        'args'                => $this->get_collection_params(),
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
    $items = \get_option(ImpexExport::WP_OPTION_EXPORTS, []);
    $data = [];
    foreach ($items as $item) {
      $itemdata = $this->prepare_item_for_response($item, $request);
      $data[] = $this->prepare_response_for_collection($itemdata);
    }

    return new \WP_REST_Response($data, 200);
  }

  public function get_item_slices($request)
  {
    $id = $request->get_param('id');

    $page = $request['page'] - 1;
    $offset = $request['offset'];
    $per_page = $request['per_page'];

    foreach (\get_option(ImpexExport::WP_OPTION_EXPORTS, []) as $export) {
      if ($export['id'] === $id) {
        $transformationContext = ImpexExportTransformationContext::fromJson($export);

        global $wpdb;

        $rows = $wpdb->get_results(
          $wpdb->prepare("SELECT * from {$wpdb->prefix}" . ImpexExport::DB_CHUNKS_TABLENAME . ' WHERE export_id=%s ORDER BY position LIMIT %d OFFSET %d', $id, $per_page, $page * $per_page + $offset)
        );
        $data = [];
        foreach ($rows as $row) {
          $serialized_slice = json_decode($row->slice, JSON_OBJECT_AS_ARRAY);
          $serialized_slice = \apply_filters(ImpexExportRESTController::WP_FILTER_EXPORT_SLICE_REST_MARSHAL, $serialized_slice, $transformationContext);
          $itemdata = $this->prepare_item_for_response($serialized_slice, $request);
          $data[] = $this->prepare_response_for_collection($itemdata);
        }

        $total = absint($wpdb->get_var(
          $wpdb->prepare("SELECT COUNT(*) from {$wpdb->prefix}" . ImpexExport::DB_CHUNKS_TABLENAME . ' WHERE export_id=%s', $id)
        ));
        $total_pages = ceil($total / $per_page);

        $response = new \WP_REST_Response($data, 200);
        $response->header('X-WP-Total', $total);
        $response->header('X-WP-TotalPages', $total_pages);
        return $response;
      }
    }

    return new \WP_Error('not-found', __('could not find export by id', 'cm4all-wp-impex'), ['status' => 404]);
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

    foreach (\get_option(ImpexExport::WP_OPTION_EXPORTS, []) as $export) {
      if ($export['id'] === $id) {
        return new \WP_REST_Response($this->prepare_item_for_response($export, $request), 200);
      }
    }

    return new \WP_Error('not-found', __('could not find export by id', 'cm4all-wp-impex'), ['status' => 404]);
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

    $profile = Impex::getInstance()->Export->getProfile($item['profile']);
    if ($profile === null) {
      return new \WP_Error('cant-create', __('export profile not found', 'cm4all-wp-impex'), ['status' => 404]);
    }

    $transformationResult = Impex::getInstance()->Export->save(
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

    $result = Impex::getInstance()->Export->update($id, $item);
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

    $result = Impex::getInstance()->Export->remove($id);

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
    return \current_user_can('export');
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
    return \current_user_can('export');
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
          'description' => __('Unique identifier for the export.', 'cm4all-wp-impex'),
          'type'        => 'string',
          'format'      => 'uuid',
          'context'     => ['view', 'edit'],
          'readonly'    => true,
        ],
        'options' => [
          'description' => __('The options used to create the export.', 'cm4all-wp-impex'),
          // its actually an array butt needs to be declared as object 
          // according to https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/#type-juggling
          'type'        => 'object',
          'context'     => ['view', 'edit'],
          'default'     => [],
        ],
        'profile' => [
          'description' => __('The name of the export profile to use.', 'cm4all-wp-impex'),
          'type'        => 'string',
          'context'     => ['view', 'edit'],
          /*
          // @TODO: example doesnt work that perfect in swagger ... 
          'example'     => \wp_list_pluck( iterator_to_array(Impex::getInstance()->Export->getProfiles()), 'name' ),
          */
        ],
        'created' => [
          'description' => __('The date of the export.', 'cm4all-wp-impex'),
          'type'        => 'string',
          'format'      => 'date-time',
          'context'     => ['view', 'edit'],
          'readonly'    => true,
        ],
        'user' => [
          'description' => __('The user login of the user creating the export.', 'cm4all-wp-impex'),
          'type'        => 'string',
          'context'     => ['view', 'edit'],
          'readonly'    => true,
        ],
        'name' => [
          'description' => __('The human readable name of the export', 'cm4all-wp-impex'),
          'type'        => 'string',
          'context'     => ['view', 'edit'],
          'minLength'   => 1,
          'pattern'     => '[a-zA-Z_\-]+',
        ],
        'description' => [
          'description' => __('The human readable description of the export', 'cm4all-wp-impex'),
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
  hook_name: 'rest_api_init',
  callback: function () {
    $controller = new ImpexExportRESTController();
    $controller->register_routes();
  },
);
