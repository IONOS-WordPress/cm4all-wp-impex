<?php

namespace cm4all\wp\impex;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit();
}

require_once __DIR__ . '/interface-impex-named-item.php';
require_once __DIR__ . '/class-impex-event.php';

/**
 * @property-read string $name
 */
abstract class ImpexEvents implements \IteratorAggregate
{
  use ImpexSet;

  /** @var ImpexSet<ImpexEvent> */
  protected \IteratorAggregate $_handlers;

  public function __construct()
  {
    $this->_handlers = new class implements \IteratorAggregate
    {
      use ImpexSet;
    };
  }

  function __invoke(string $name): ImpexEvent
  {
    $eventHandler = $this->_handlers->get($name);
    if ($eventHandler === null) {
      $this->_handlers->add($eventHandler = new class($name) extends ImpexEvent
      {
        public function __construct(string $name)
        {
          parent::__construct($name);
        }
      });
    }
    return $eventHandler;
  }

  public function get(): \IteratorAggregate
  {
    return $this->_handlers;
  }
}
