<?php

namespace cm4all\wp\impex;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit();
}

require_once __DIR__ . '/interface-impex-named-item.php';
require_once __DIR__ . '/class-impex-part.php';
require_once __DIR__ . '/class-impex-runtime-exception.php';
require_once __DIR__ . '/class-impex-profile-task.php';
require_once __DIR__ . '/class-impex-events.php';
/**
 * @property-read string $name
 */
abstract class ImpexProfile implements ImpexNamedItem
{
  protected string $_name;
  protected string|null $_description = null;

  /** @var ImpexSet */
  protected \IteratorAggregate $_tasks;

  protected ImpexEvents $_events;

  protected ImpexPart $_context;

  public function __construct($name, ImpexPart $context)
  {
    $this->_name = $name;
    $this->_context = $context;
    $this->_tasks = new class implements \IteratorAggregate
    {
      use ImpexSet;
    };

    $this->_events = new class() extends ImpexEvents
    {
    };
  }

  public function __get($property)
  {
    return match ($property) {
      'name' => $this->_name,
      'description' => $this->_description,
      default => throw new ImpexRuntimeException(sprintf('abort getting invalid property "%s"', $property)),
    };
  }

  public function events(string $name): ImpexEvent
  {
    return call_user_func($this->_events, $name);
  }

  public function setDescription(string $description): static
  {
    // @TODO: add validation
    $this->_description = $description;

    return $this;
  }

  protected function _createTask(string $name, ImpexProvider $impexProvider, $options = []): ImpexProfileTask
  {
    return new class($name, $impexProvider, $options) extends ImpexProfileTask
    {
      public function __construct($name, $impexProvider, $options)
      {
        parent::__construct($name, $impexProvider, $options);
      }
    };
  }

  public function addTask(string $name, string $providerName, $options = []): ImpexProfileTask
  {
    $task = $this->_createTask($name, $this->_context->getProvider($providerName), $options);
    $this->_tasks->add($task);
    return $task;
  }

  public function hasTask(string $name): bool
  {
    return $this->_tasks->has($name);
  }

  public function getTask(string $name): ImpexProfileTask
  {
    return $this->_tasks->get($name);
  }

  /**
   * @return \Generator<ImpexProfileTask>
   */
  public function getTasks()
  {
    return $this->_tasks;
  }

  public function removeTask(string $name): ImpexProfileTask
  {
    return $this->_tasks->remove($name);
  }

  public function moveTask(string $name, int $newIndex): bool
  {
    return $this->_tasks->move($name, $newIndex);
  }
}
