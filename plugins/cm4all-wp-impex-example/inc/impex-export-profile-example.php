<?php

namespace cm4all\wp\impex\example;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit();
}

use cm4all\wp\impex\AttachmentsExporter;
use cm4all\wp\impex\ContentExporter;
use cm4all\wp\impex\DbTablesExporter;
use cm4all\wp\impex\Impex;
use cm4all\wp\impex\WpOptionsExporter;

require_once(ABSPATH . 'wp-admin/includes/plugin.php');

$profile = Impex::getInstance()->Export->addProfile('impex-export-profile-example');
$profile->setDescription('Exports posts/pages/media-assets and plugin data of [cm4all-wordpress,complianz-gdpr,ninja-forms,ultimate-maps-by-supsystic] if these plugins are enabled');

// export pages/posts/comments/block patterns/templates/template parts/reusable blocks
$profile->addTask('wordpress content', ContentExporter::PROVIDER_NAME,);

// export uploads
$profile->addTask('wordpress attachments (uploads)', AttachmentsExporter::PROVIDER_NAME,);

// export cm4all-wordpress related tables/options if active
$task = $profile->addTask('cm4all-wordpress wp_options', WpOptionsExporter::PROVIDER_NAME, [WpOptionsExporter::OPTION_SELECTOR => ['cm4all-*',]]);
$task->disabled = !\is_plugin_active("cm4all-wordpress/plugin.php");

// export complianz related tables/options if active
/*
global $wpdb;

$table_names = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}cmplz\_%'");
if ($table_names !== false) {
  foreach ($table_names as $table_name) {
    $table_name = str_replace($wpdb->prefix, '', $table_name);
    $profile->addTask("complianz table '$table_name'", DbTablesExporter::PROVIDER_NAME, [DbTablesExporter::OPTION_SELECTOR => $table_name,]);
  }
}*/
$plugin_complianz_disabled = !\is_plugin_active("complianz-gdpr/complianz-gpdr.php");
$profile->addTask("complianz-gdpr db tables", DbTablesExporter::PROVIDER_NAME, [DbTablesExporter::OPTION_SELECTOR => 'cmplz_*',])
  ->disabled = $plugin_complianz_disabled;
$profile->addTask('complianz-gdpr wp_options', WpOptionsExporter::PROVIDER_NAME, [WpOptionsExporter::OPTION_SELECTOR => ['cmplz_*', 'complianz_*']])
  ->disabled = $plugin_complianz_disabled;


// export ninja-forms related tables/options if active
/*
global $wpdb;

$table_names = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}nf3\_%'");
if ($table_names !== false) {
  foreach ($table_names as $table_name) {
    $table_name = str_replace($wpdb->prefix, '', $table_name);
    $profile->addTask("ninja-forms table '$table_name'", DbTablesExporter::PROVIDER_NAME, [DbTablesExporter::OPTION_SELECTOR => $table_name,]);
  }
}
*/
$plugin_ninjaforms_disabled = !\is_plugin_active("ninja-forms/ninja-forms.php");
$profile->addTask("ninja-forms db tables (nf3_*)", DbTablesExporter::PROVIDER_NAME, [DbTablesExporter::OPTION_SELECTOR => 'nf3_*',])
  ->disabled = $plugin_ninjaforms_disabled;
$profile->addTask('ninja-forms wp_options', WpOptionsExporter::PROVIDER_NAME, [WpOptionsExporter::OPTION_SELECTOR => ['ninja_*', 'nf_*', 'wp_nf_*', 'widget_ninja_*']])
  ->disabled = $plugin_ninjaforms_disabled;

// export ultimate_maps related tables/options if active
/*
global $wpdb;

$table_names = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}ums\_%'");
if ($table_names !== false) {
  foreach ($table_names as $table_name) {
    $table_name = str_replace($wpdb->prefix, '', $table_name);
    $profile->addTask("ultimate_maps table '$table_name'", DbTablesExporter::PROVIDER_NAME, [DbTablesExporter::OPTION_SELECTOR => $table_name,]);
  }
}
*/
$plugin_ultimatemaps_disabled = !\is_plugin_active("ultimate-maps-by-supsystic/ums.php");
$profile->addTask("ultimate_maps db tables (ums_*)", DbTablesExporter::PROVIDER_NAME, [DbTablesExporter::OPTION_SELECTOR => 'ums_*',])
  ->disabled = $plugin_ultimatemaps_disabled;
$profile->addTask('ultimate_maps wp_options', WpOptionsExporter::PROVIDER_NAME, [WpOptionsExporter::OPTION_SELECTOR => ['ums_*', 'wp_ums_*',]])
  ->disabled = $plugin_ultimatemaps_disabled;
