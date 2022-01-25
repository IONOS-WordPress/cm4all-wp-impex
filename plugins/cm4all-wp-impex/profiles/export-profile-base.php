<?php

namespace cm4all\wp\impex\example;

use cm4all\wp\impex\AttachmentsExporter;
use cm4all\wp\impex\ContentExporter;
use cm4all\wp\impex\Impex;

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
