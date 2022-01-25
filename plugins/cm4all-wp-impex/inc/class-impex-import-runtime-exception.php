<?php

namespace cm4all\wp\impex;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit();
}

require_once __DIR__ . '/class-impex-runtime-exception.php';

class ImpexImportRuntimeException extends ImpexRuntimeException
{
  protected $context;

  public function __construct($message, $context = null, $code = 0, \Throwable $previous = null,)
  {
    parent::__construct($message, $context, $code, $previous);
  }
}
