<?php

namespace cm4all\wp\impex;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit();
}

require_once __DIR__ . '/interface-impex-named-item.php';
require_once __DIR__ . '/class-impex-runtime-exception.php';

trait ImpexSet /* implements \IteratorAggregate */
{
  protected array $_items = [];

  protected function _indexByName(string $name): int
  {
    foreach ($this->_items as $index => $item) {
      if ($item->name === $name) {
        return $index;
      }
    }

    return -1;
  }

  public function add(ImpexNamedItem $item): self
  {
    if ($this->has($item->name)) {
      throw new ImpexRuntimeException(sprintf('Cannot add item : An item named "%s" already exists', $item->name));
    }

    $this->_items[] = $item;

    return $this;
  }

  public function has(string $name): bool
  {
    return $this->_indexByName($name) !== -1;
  }

  public function get(string $name): ImpexNamedItem|null
  {
    $index = $this->_indexByName($name);
    return $index !== -1 ? $this->_items[$index] : null;
  }

  public function remove(string $name): ImpexNamedItem|null
  {
    $index = $this->_indexByName($name);
    if ($index !== -1) {
      $item = $this->_items[$index];
      array_splice($this->_items, $index, 1);
      return $item;
    }

    return null;
  }

  public function move(string $name, int $newIndex): bool
  {
    $index = $this->_indexByName($name);
    if ($index !== -1) {
      $namedItem = array_splice($this->_items, $index, 1);
      array_splice($this->_items, $newIndex, 0, $namedItem);
    }

    return $index !== -1;
  }

  /**
   * @see \IteratorAggregate
   */
  public function getIterator(): \Iterator
  {
    return new \ArrayIterator(array_values($this->_items));
  }
}
