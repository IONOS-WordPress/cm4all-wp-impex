<?php

namespace cm4all\wp\impex;

use JsonSerializable;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit();
}

require_once __DIR__ . '/class-impex-part.php';
require_once __DIR__ . '/class-impex-runtime-exception.php';

/**
 * ImpexTransformationContext is a superset of ImpexImportTransformationContext and ImpexExportTransformationContext
 * 
 * @property-read string $id
 * @property-read string $name
 * @property-read string $description
 * @property-read string $created
 * @property-read \WP_User $user
 * @property-read string $path
 * @property-read string $url
 * @property-read array $options
 * @property-read ImpexProfile $profile
 */
abstract class ImpexTransformationContext implements \JsonSerializable
{
  protected string $_id;
  protected string $_name;
  protected string $_description;
  protected string $_created;
  protected string $_user_login;
  protected string $_uploads_subpath;
  protected string $_profile_name;
  protected array $_options;
  protected bool $_isExportPart;

  protected function __construct(
    ImpexPart $part,
    string $profile_name,
    string|null $id = null,
    string|null $name = null,
    string|null $description = null,
    string|null $created = null,
    string|null $user_login = null,
    array|null $options = null,
  ) {
    $this->_isExportPart = Impex::getInstance()->Export === $part;

    // ensure profile exists
    if (null === Impex::getInstance()->{$this->_isExportPart ? 'Export' : 'Import'}->getProfile($profile_name))
      throw new ImpexRuntimeException(sprintf('Profile "%s" does not exist', $profile_name));
    $this->_profile_name = $profile_name;

    $this->_id = $id ?? \wp_generate_uuid4();
    // we take this date format to be wordpress rest api (format='date-time') compliant 
    $this->_created = $created ?? date(DATE_RFC3339);

    $this->_user_login ??= $user_login ?? \wp_get_current_user()->user_login;
    // ensure user exists
    if (false === \get_user_by('login', $this->_user_login))
      throw new ImpexRuntimeException(sprintf('user "%s" not found', $this->_user_login));

    if ($name === null || $name === '') {
      $this->_name = ($this->_isExportPart ? 'Export' : 'Import') . " '{$profile_name}' created by user '{$this->_user_login}' at {$this->_created}";
    } else {
      $this->_name = $name;
    }
    $this->_description = $description ?? '';
    $this->_uploads_subpath ??= 'impex/snapshots/' . $this->_id;

    $this->_options = $options ?? [];

    global $wp_filesystem;
    \WP_Filesystem();

    // ensure uploads_subpath path  exists
    $uploads_subpath = \wp_get_upload_dir()['basedir'] . '/' . $this->_uploads_subpath;
    $wp_filesystem->exists($uploads_subpath) || \wp_mkdir_p($uploads_subpath);
  }

  public function __get($property)
  {
    return match ($property) {
      'id' => $this->_id,
      'name' => $this->_name,
      'description' => $this->_description,
      'created' => $this->_created,
      'user' => \get_user_by('login', $this->_user_login),
      'path' => \wp_get_upload_dir()['basedir'] . '/' . $this->_uploads_subpath,
      'url' => \wp_get_upload_dir()['baseurl'] . '/' . $this->_uploads_subpath,
      'profile' => Impex::getInstance()->{$this->_isExportPart ? 'Export' : 'Import'}->getProfile($this->_profile_name),
      'options' => $this->_options,

      default => throw new ImpexRuntimeException(sprintf('abort getting invalid property "%s"', $property)),
    };
  }

  /**
   * @see JsonSerializable::jsonSerialize()
   */
  public function jsonSerialize(): mixed
  {
    return [
      "id" => $this->_id,
      "name" => $this->_name,
      "description" => $this->_description,
      "created" => $this->_created,
      "user" => $this->_user_login,
      "profile" => $this->_profile_name,
      "options" => $this->_options,
    ];
  }

  abstract static function fromJson(array $json): static;
}

class ImpexExportTransformationContext extends ImpexTransformationContext
{
  public function __construct(
    string $profile_name,
    string|null $id = null,
    string|null $name = null,
    string|null $description = null,
    string|null $created = null,
    string|null $user_login = null,
    array|null $options = null,
  ) {
    parent::__construct(Impex::getInstance()->Export, $profile_name, $id, $name, $description, $created, $user_login, $options);
  }

  public static function fromJson(array $json): static
  {
    return new static(
      profile_name: $json['profile'],
      id: $json['id'],
      name: $json['name'],
      description: $json['description'],
      created: $json['created'],
      user_login: $json['user'],
      options: $json['options'],
    );
  }
}

class ImpexImportTransformationContext extends ImpexTransformationContext
{
  public function __construct(
    string $profile_name,
    string|null $id = null,
    string|null $name = null,
    string|null $description = null,
    string|null $created = null,
    string|null $user_login = null,
    array|null $options = null,
  ) {
    parent::__construct(Impex::getInstance()->Import, $profile_name, $id, $name, $description, $created, $user_login, $options);
  }

  public static function fromJson(array $json): static
  {
    return new static(
      profile_name: $json['profile'],
      id: $json['id'],
      name: $json['name'],
      description: $json['description'],
      created: $json['created'],
      user_login: $json['user'],
      options: $json['options'],
    );
  }
}
