<!-- toc -->

# Usage

You can use this plugin **out of the box** to export/import

- content (posts/pages/nav_menu and media assets)

- FSE content (Full Site Editing assets like block patterns, templates, template parts, reusable blocks and theme settings)

For everything else (third-party plugin settings for example) you can provide custom export/import profile(s).

## Configuration

ImpEx import and export can be customized by providing **Profiles**.

An **Export profile** defines the WordPress data to export.

An **Import profile** declares how and which export data can be imported.

## Custom profile configuration

**Profiles** are configured and registered using a custom ImpEx [WordPress action](https://developer.wordpress.org/plugins/hooks/actions/) :

```php
\add_action('cm4all_wp_impex_register_profiles', function () {
  // your ImpEx profile registration goes here
  ...
});
```

Using a [WordPress action](https://developer.wordpress.org/plugins/hooks/actions/) for ImpEx profile registration guarantees that the action callback is only executed if the ImpEx plugin is activated.

> Implementing a ImpEx profile in your WordPress code does not make ImpEx a required dependency for your plugin since the code is only executed if ImpEx is installed and active.

### Export

Let's say you want to export the core WordPress contents (pages/posts/attachments and stuff) but also

- [Ninja Forms Contact Form](https://wordpress.org/plugins/ninja-forms/) contents and settings

- [Ultimate Maps by Supsystic](https://wordpress.org/plugins/ultimate-maps-by-supsystic/) contents and settings

- [Complianz GDPR](https://wordpress.org/plugins/complianz-gdpr/) contents and settings

- and a few options of your own plugin

To do so you need to create a **Export profile**.

Fortunately ImpEx already provides some low level building blocks called **ExportProvider** to make our ImpEx Profile declaration piece of cake :

```php
\add_action('cm4all_wp_impex_register_profiles', function () {
  // ensure admin plugin functions are available
  require_once(ABSPATH . 'wp-admin/includes/plugin.php');

  // register a new export profile with a unique name
  $profile = Impex::getInstance()->Export->addProfile('impex-export-profile-example');
  // give the profile a senseful description
  $profile->setDescription('Exports posts/pages/media-assets and plugin data of [cm4all-wordpress,complianz-gdpr,ninja-forms,ultimate-maps-by-supsystic]');

  // export pages/posts/comments/block patterns/templates/template parts/reusable blocks
  $profile->addTask(
    'wordpress content',
    cm4all\wp\impex\ContentExporter::PROVIDER_NAME
  );

  // export media
  $profile->addTask(
    'wordpress attachments (uploads)',
    cm4all\wp\impex\AttachmentsExporter::PROVIDER_NAME
  );

  // export ninja-forms related tables/options if active
  $plugin_ninjaforms_disabled = !is_plugin_active("ninja-forms/ninja-forms.php");
  $profile->addTask(
    "ninja-forms db tables (nf3_*)",
    cm4all\wp\impex\DbTablesExporter::PROVIDER_NAME,
    // ninja-forms stores data in custom tables prefixed with 'nf_3'
    [cm4all\wp\impex\DbTablesExporter::OPTION_SELECTOR => 'nf3_*',]
  )->disabled = $plugin_ninjaforms_disabled;
  $profile->addTask(
    'ninja-forms wp_options',
    cm4all\wp\impex\WpOptionsExporter::PROVIDER_NAME,
    // ninja-forms stores it options prefixed with 'ninja_*', 'nf_*', 'wp_nf_*', 'widget_ninja_*'
    [cm4all\wp\impex\WpOptionsExporter::OPTION_SELECTOR => ['ninja_*', 'nf_*', 'wp_nf_*', 'widget_ninja_*']]
  )->disabled = $plugin_ninjaforms_disabled;

  // export ultimate_maps related tables/options
  $plugin_ultimatemaps_disabled = !is_plugin_active("ultimate-maps-by-supsystic/ums.php");
  $profile->addTask(
    "ultimate_maps db tables (ums_*)",
    cm4all\wp\impex\DbTablesExporter::PROVIDER_NAME,
    // ultimate maps stores data in custom tables prefixed with 'ums_'
    [cm4all\wp\impex\DbTablesExporter::OPTION_SELECTOR => 'ums_*',]
  )->disabled = $plugin_ultimatemaps_disabled;
  $profile->addTask(
    'ultimate_maps wp_options',
    cm4all\wp\impex\WpOptionsExporter::PROVIDER_NAME,
    // ultimate maps stores data in wp options prefixed with 'ums_*', 'wp_ums_*'
    [cm4all\wp\impex\WpOptionsExporter::OPTION_SELECTOR => ['ums_*', 'wp_ums_*',]]
  )->disabled = $plugin_ultimatemaps_disabled;

  // export complianz related tables/options
  $plugin_complianz_disabled = !is_plugin_active("complianz-gdpr/complianz-gpdr.php");
  $profile->addTask(
    "complianz-gdpr db tables",
    cm4all\wp\impex\DbTablesExporter::PROVIDER_NAME,
    // complianz stores data in custom tables prefixed with 'cmplz_*'
    [DbTablesExporter::OPTION_SELECTOR => 'cmplz_*',]
  )->disabled = $plugin_complianz_disabled;
  $profile->addTask(
    'complianz-gdpr wp_options',
    cm4all\wp\impex\WpOptionsExporter::PROVIDER_NAME,
    // complianz stores data in wp options prefixed with 'cmplz_*', 'complianz_*'
    [cm4all\wp\impex\WpOptionsExporter::OPTION_SELECTOR => ['cmplz_*', 'complianz_*']]
  )->disabled = $plugin_complianz_disabled;

  // export our own plugin uses wp_options starting with `foo-` or `bar*`
  $profile->addTask(
    'custom plugin options',
    cm4all\wp\impex\WpOptionsExporter::PROVIDER_NAME,
    [cm4all\wp\impex\WpOptionsExporter::OPTION_SELECTOR => ['foo-*','bar-*']]
  )->disabled = !is_plugin_active("cm4all-wordpress/plugin.php");
});
```

> Caveat: Do not provide the WordPress database table prefix (default is `wp_`) to the `cm4all\wp\impex\WpOptionsExporter::OPTION_SELECTOR` option since it may vary on installations. the `cm4all\wp\impex\WpOptionsExporter` will take care of that.

That's it !

Now you can trigger the export using this ImpEx export configuration in the ImpEx screen at WP dashboard (or even using the [ImpEx CLI](./impex-cli.md))

### Import

Thanks to ImpEx architecture you normally don't need to define a custom import configuration.

ImpEx sports a generic `all` import provider and profile importing **anything** exported using the ImpEx building blocks.

> As long as you use the **Exporter** provided by ImpEx in your custom Export profile, you don't need to define an matching custom import profile.

In rare cases you need to execute PHP code after the import to get everything working.

In our export profile example above we implemented support for [Ninja Forms Contact Form](https://wordpress.org/plugins/ninja-forms/). Unfortunately the forms will be in maintenance mode after importing them.

To fix this we need to execute some PHP code (`WPN_Helper::set_forms_maintenance_mode(0)` from the [Ninja Forms Contact Form Plugin](https://wordpress.org/plugins/ninja-forms/)) after the import.

ImpEx provides **Events** for exactly that purpose :

```php
\add_action('cm4all_wp_impex_register_profiles', function () {
  // ensure admin plugin functions are available
  require_once(ABSPATH . 'wp-admin/includes/plugin.php');

  // get the 'all' profile
  $profile = Impex::getInstance()->Import->getProfile('all');

  // attach a listener callback for the `EVENT_IMPORT_END` event
  $profile->events(ImpexImport::EVENT_IMPORT_END)->addListener(
    'reset ninja forms mainentance mode',
    fn () => method_exists('WPN_Helper', 'set_forms_maintenance_mode') && WPN_Helper::set_forms_maintenance_mode(0)
  );
});
```

Tada - thats it !

There ist just one caveat ... what if the ImpEx 'all' profile gets disabled by someone else ?
To work around this we can also introduce a custom import profile utilizing the 'all' import provider:

```php
\add_action('cm4all_wp_impex_register_profiles', function () {
  // ensure admin plugin functions are available
  require_once(ABSPATH . 'wp-admin/includes/plugin.php');

  // create a new import profile
  $profile = Impex::getInstance()->Import->addProfile('impex-import-profile-example');
  $profile->setDescription('Import everything example with event listener');

  // reuse the 'all' import provider registered by the 'all' import profile
  $profile->addTask('main', Impex::getInstance()->Import->getProvider('all')->name);

  // attach a listener callback for the `EVENT_IMPORT_END` event
  $profile->events(ImpexImport::EVENT_IMPORT_END)->addListener(
    'reset ninja forms mainentance mode',
    fn () => method_exists('WPN_Helper', 'set_forms_maintenance_mode') && WPN_Helper::set_forms_maintenance_mode(0)
  );
});
```
