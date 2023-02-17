<?php

namespace cm4all\wp\impex;

use Traversable;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit();
}

require_once __DIR__ . '/interface-impex-named-item.php';
require_once __DIR__ . '/trait-impex-set.php';
require_once __DIR__ . '/class-impex-eventlistener.php';

/**
 * @property-read string $name
 */
abstract class ImpexEvent implements ImpexNamedItem
{
  protected string $_name;

  /** @var ImpexSet */
  protected \IteratorAggregate $_listeners;

  public function __construct(string $name)
  {
    $this->_name = $name;

    $this->_listeners = new class implements \IteratorAggregate
    {
      use ImpexSet;
    };
  }

  public function __get($property)
  {
    return match ($property) {
      'name' => $this->_name,
      default => throw new ImpexRuntimeException(sprintf('abort getting invalid property "%s"', $property)),
    };
  }

  public function addListener(string $name, callable $callback): self
  {
    $this->_listeners->add(new class($name, $callback) extends ImpexEventListener
    {
      public function __construct(string $name, callable $callback)
      {
        parent::__construct($name, $callback);
      }
    });
    return $this;
  }

  public function removeListener(string $name, callable $callback): self
  {
    $this->_listeners->remove($name);
    return $this;
  }

  function __invoke(ImpexImportTransformationContext $transformationContext, array $options)
  {
    foreach ($this->getListeners() as $listener) {
      $listener($transformationContext, $options);
    }
  }

  /**
   * @return Traversable<ImpexEventListener>
   */
  public function getListeners(): \Traversable
  {
    return $this->_listeners->getIterator();
  }
}
