<?php

namespace cm4all\wp\impex\example;

use cm4all\wp\impex\AttachmentsExporter;
use cm4all\wp\impex\ContentExporter;
use cm4all\wp\impex\Impex;
use cm4all\wp\impex\WpOptionsExporter;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit();
}

$profile = Impex::getInstance()->Export->addProfile('base');
$profile->setDescription('Exports posts/pages including media assets');
// export pages/posts/comments/block patterns/templates/template parts/reusable blocks
$profile->addTask('wordpress content', ContentExporter::PROVIDER_NAME, []);

// export uploads
$profile->addTask('wordpress attachments (uploads)', AttachmentsExporter::PROVIDER_NAME, []);

// export most common used wp options
$profile->addTask(
  'common wp_options',
  WpOptionsExporter::PROVIDER_NAME,
  [WpOptionsExporter::OPTION_SELECTOR => [
    'page_on_front', // wordpress homepage
    'show_on_front', // what should be shown ('page'| 'post' ...)
    'page_for_posts', // posts homepage

    'blogname',       // aka website title
    'blogdescription',
    'site_logo',       // website logo
    'site_icon',       // website icon
    'wp_attachment_pages_enabled', // enable/disable attachment pages (see https://make.wordpress.org/core/2023/10/16/changes-to-attachment-pages/)
  ],],
);
