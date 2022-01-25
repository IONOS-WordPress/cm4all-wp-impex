<?php

namespace cm4all\wp\impex;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit();
}

interface ImpexRestController
{
  const VERSION = '1';

  const NAMESPACE = 'cm4all-wp-impex/v' . self::VERSION;

  const BASE_URI = '/' . self::NAMESPACE;
}
