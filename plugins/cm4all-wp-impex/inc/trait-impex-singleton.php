<?php

namespace cm4all\wp\impex;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit();
}

trait ImpexSingleton
{
  private static $instance;

  protected function __construct()
  {
  }

  public static function getInstance(): static
  {
    if (!self::$instance) {
      // new self() will refer to the class that uses the trait
      self::$instance = new self();
    }

    return self::$instance;
  }

  protected function __clone()
  {
  }
  public function __sleep()
  {
  }
  public function __wakeup()
  {
  }
}
