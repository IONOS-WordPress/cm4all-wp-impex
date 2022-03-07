<?php

namespace cm4all\wp\impex;

use cm4all\wp\impex\ImpexSet;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit();
}

require_once __DIR__ . '/trait-impex-set.php';
require_once __DIR__ . '/class-impex-provider.php';
require_once __DIR__ . '/class-impex-profile.php';

abstract class ImpexPart
{
  protected /* ImpexSet */ $_providers;

  protected /* ImpexSet */ $_profiles;

  public function __construct(protected string $_db_chunks_tablename)
  {
    $this->_providers = new class implements \IteratorAggregate
    {
      use ImpexSet;
    };

    $this->_profiles = new class implements \IteratorAggregate
    {
      use ImpexSet;
    };
  }

  /* providers */
  protected abstract function _createProvider(string $name, callable $cb): ImpexProvider;

  public function addProvider(string $name, callable $cb): ImpexProvider
  {
    $provider = $this->_createProvider($name, $cb);
    $this->_providers->add($provider);
    return $provider;
  }

  public function hasProvider(string $name): bool
  {
    return $this->_providers->has($name);
  }

  public function getProvider(string $name): ImpexProvider|null
  {
    return $this->_providers->get($name);
  }

  /**
   * @return \Generator 
   */
  public function getProviders()
  {
    return $this->_providers;
  }

  public function removeProvider(string $name): ImpexProvider|null
  {
    return $this->_providers->remove($name);
  }

  /* profiles */
  protected abstract function _createProfile(string $name, ImpexPart $context): ImpexProfile;

  public function addProfile(string $name)
  {
    $profile = $this->_createProfile($name, $this);
    $this->_profiles->add($profile);
    return $profile;
  }

  public function hasProfile(string $name): bool
  {
    return $this->_profiles->has($name);
  }

  /**
   * @return \Generator 
   */
  public function getProfiles()
  {
    return $this->_profiles;
  }

  public function getProfile(string $name): ImpexProfile|null
  {
    return $this->_profiles->get($name);
  }

  public function removeProfile(string $name): ImpexProfile|null
  {
    return $this->_profiles->remove($name);
  }

  /*
    make it not serializable 
  */
  protected function __clone()
  {
  }
  public function __sleep()
  {
  }
  public function __wakeup()
  {
  }

  /**
   * compute $options overrides
   */
  protected static function _computeOptions(ImpexProfileTask $task, array $options)
  {

    $_options = $task->options;

    foreach ($options as $name => $value) {
      if (str_starts_with($name, $task->name)) {
        $name = substr($name, strlen($task->name));
      } else if (str_starts_with($name, $task->provider->name)) {
        $name = substr($name, strlen($task->provider->name));
      }

      $_options[$name] = $value;
    }

    return $_options;
  }
}
