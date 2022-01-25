<?php

namespace cm4all\wp\impex;

use RuntimeException;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit();
}

require_once __DIR__ . '/class-impex.php';
require_once __DIR__ . '/interface-impex-rest-controller.php';
abstract class ImpexProfileRESTController extends \WP_REST_Controller implements ImpexRestController
{
  protected function __construct(protected string $_contextPartName, $rest_base)
  {
    $this->namespace = self::NAMESPACE;

    $this->rest_base = $rest_base;
  }

  protected function getContextPart()
  {
    return Impex::getInstance()->{$this->_contextPartName};
  }

  public function register_routes(): void
  {
    \register_rest_route($this->namespace, $this->rest_base . '/schema', [
      'methods'  => \WP_REST_Server::READABLE,
      'callback' => [$this, 'get_public_item_schema'],
      'permission_callback' => [$this, 'get_item_permissions_check'],
    ]);
    \register_rest_route($this->namespace, $this->rest_base, [
      [
        'methods'             => \WP_REST_Server::READABLE,
        'callback'            => [$this, 'get_items'],
        'permission_callback' => [$this, 'get_items_permissions_check'],
        'args'                => [],
        'schema' => [$this, 'get_public_item_schema'],
      ],
      'schema' => [$this, 'get_public_item_schema'],
    ]);
    \register_rest_route($this->namespace, $this->rest_base . '/(?P<name>[^/]+)', [
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
      'schema' => [$this, 'get_public_item_schema'],
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
    $data = [];
    foreach ($this->getContextPart()->getProfiles() as $profile) {
      $itemdata = $this->prepare_item_for_response($profile, $request);
      $data[] = $this->prepare_response_for_collection($itemdata);
    }

    return new \WP_REST_Response($data, 200);
  }

  /**
   * Get one item from the collection
   *
   * @param WP_REST_Request $request Full data about the request.
   * @return WP_Error|WP_REST_Response
   */
  public function get_item($request)
  {
    $name = $request->get_param('name');

    foreach ($this->getContextPart()->getProfiles() as $profile) {
      if ($profile->name === $name) {
        return new \WP_REST_Response($this->prepare_item_for_response($profile, $request), 200);
      }
    }

    return new \WP_Error('not-found', __(sprintf('could not find %s profile by name', strtolower($this->_contextPartName)), 'cm4all-wp-impex'), ['status' => 404]);
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
    return \current_user_can('import') || \current_user_can('export');
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
   * Prepare the item for the REST response
   *
   * @param mixed $item WordPress representation of the item.
   * @param WP_REST_Request $request Request object.
   * @return mixed
   */
  public function prepare_item_for_response($item, $request)
  {
    // @TODO : added tasks and stuff later on
    return ['name' => $item->name, 'description' => $item->description];
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
      'title'      => $this->rest_base,
      'type'       => 'object',
      'properties' => [
        'name' => [
          'description' => __('Unique identifier for the profile.', 'cm4all-wp-impex'),
          'type'        => 'string',
          'context'     => ['view', 'edit'],
          'readonly'    => true,
        ],
        'description' => [
          'description' => __('Human readable description of the profile.', 'cm4all-wp-impex'),
          'type'        => 'string',
          'context'     => ['view', 'edit'],
          'readonly'    => true,
        ],
      ],
    ];

    $this->schema = $schema;

    return $this->add_additional_fields_schema($this->schema);
  }
}
