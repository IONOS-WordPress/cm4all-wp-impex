<?php

namespace cm4all\wp\impex;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit();
}

class ImpexRuntimeException extends \RuntimeException
{
  protected $context;

  public function __construct($message, $context = null, $code = 0, \Throwable $previous = null)
  {
    parent::__construct($message, $code, $previous);
    $this->context = $context;
  }
}
