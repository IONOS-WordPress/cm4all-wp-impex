<!-- toc -->

# Preface

This books acts currently as a draft. It is not yet complete and may change in the future.

# Usage

This chapter is about the basic setup of the plugin.

## Configuration

```php
$profile = Impex::getInstance()->Export->addProfile('my-profile');
$profile->setDescription('Exports posts/pages/media-assets and plugin data of [cm4all-wordpress,complianz-gdpr,ninja-forms,ultimate-maps-by-supsystic] if these plugins are enabled');

// export pages/posts/comments/block patterns/templates/template parts/reusable blocks
$profile->addTask('wordpress content', ContentExporter::PROVIDER_NAME);

// export uploads
$profile->addTask('wordpress attachments (uploads)', AttachmentsExporter::PROVIDER_NAME);

// export cm4all-wordpress related tables/options if active
if (\is_plugin_active("cm4all-wordpress/plugin.php")) {
  $profile->addTask('cm4all-wordpress wp_options', WpOptionsExporter::PROVIDER_NAME, [WpOptionsExporter::OPTION_SELECTOR => ['myplugin-*',]]);
}

// export complianz related tables/options if active
if (\is_plugin_active("complianz-gdpr/complianz-gpdr.php")) {
  $profile->addTask("complianz-gdpr db tables", DbTablesExporter::PROVIDER_NAME, [DbTablesExporter::OPTION_SELECTOR => 'cmplz_*',]);
  $profile->addTask('complianz-gdpr wp_options', WpOptionsExporter::PROVIDER_NAME, [WpOptionsExporter::OPTION_SELECTOR => ['cmplz_*','complianz_*']]);
}

// export ninja-forms related tables/options if active
if (\is_plugin_active("ninja-forms/ninja-forms.php")) {
  $profile->addTask("ninja-forms db tables (nf3_*)", DbTablesExporter::PROVIDER_NAME, [DbTablesExporter::OPTION_SELECTOR => 'nf3_*',]);
  $profile->addTask('ninja-forms wp_options', WpOptionsExporter::PROVIDER_NAME, [WpOptionsExporter::OPTION_SELECTOR => ['ninja_*', 'nf_*', 'wp_nf_*','widget_ninja_*']]);
}

// export ultimate_maps related tables/options if active
if (\is_plugin_active("ultimate-maps-by-supsystic/ums.php")) {
  $profile->addTask("ultimate_maps db tables (ums_*)", DbTablesExporter::PROVIDER_NAME, [DbTablesExporter::OPTION_SELECTOR => 'ums_*',]);
  $profile->addTask('ultimate_maps wp_options', WpOptionsExporter::PROVIDER_NAME, [WpOptionsExporter::OPTION_SELECTOR => ['ums_*', 'wp_ums_*',]]);
}
```

### API

An example diagram.

```mermaid
graph TD;
    A-->B;
    A-->C;
    B-->D;
    C-->D;
```

- there is

- more to come :-)
