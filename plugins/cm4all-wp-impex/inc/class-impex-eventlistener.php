<?php

namespace cm4all\wp\impex;

use Generator;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit();
}

require_once __DIR__ . '/interface-impex-named-item.php';

/**
 * @property-read string $name
 */
abstract class ImpexEventListener implements ImpexNamedItem
{
  protected string $_name;

  // @TODO : type callback declaration cannot be used in php <= 8
  protected /* callable */ $_callback;

  public function __construct(string $name, callable $callback)
  {
    $this->_name = $name;

    $this->_callback = $callback;
  }

  public function __get($property)
  {
    return match ($property) {
      'name' => $this->_name,
      default => throw new ImpexRuntimeException(sprintf('abort getting invalid property "%s"', $property)),
    };
  }

  function __invoke(...$args)
  {
    call_user_func_array($this->_callback, $args);
  }
}
