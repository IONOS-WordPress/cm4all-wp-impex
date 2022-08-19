<?php

namespace cm4all\wp\impex\example;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit();
}

/**
 * example export profile filter hiding the default base profile
 */
\add_filter( 
  'impex_export_filter_profiles', 
  fn( $profiles ) => array_filter($profiles, fn($profile) => $profile->name !== 'base'),
);

/**
 * example import profile filter hiding the default "all" profile
 */
/*
\add_filter( 
  'impex_import_filter_profiles', 
  fn( $profiles ) => array_filter($profiles, fn($profile) => $profile->name !== 'all'),
);
*/