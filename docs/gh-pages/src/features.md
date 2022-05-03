<!-- toc -->

# Features

There are a lot of WordPress plugins offering Import Export capabilities.

ImpEx stands out with some unique features making it the perfect match for WordPress data management.

There ist built-in support for exporting/importing

- WordPress posts, pages, comments, categories, tags, custom post types, custom fields, users, taxonomies, navigation menus and more

- FSE relates content like block patterns, global styles, templates and template parts

- WordPress media

For exporting additional data like third-party WordPress plugin settings or any other data, you can use the ImpEx API to configure your own [Export profiles](api/configuration.html#custom-profile-configuration).

## JSON export data format

ImpEx exports data in a [JSON](https://www.json.org/) format.

The data structure can be validated using the provided [ImpEx JSON Schema](https://json-schema.org/) definitions (can be found [here](https://github.com/IONOS-WordPress/cm4all-wp-impex/tree/develop/docs/gh-pages/src/jsonschema)).

### Semantic versioning support

The ImpEx JSON Schema format has built-in semantic versioning support providing upgrade capabilities for older exported content.

### JSON Schema

The data format of ImpEx exports is described in the [ImpEx JSON Schema definition files](https://github.com/IONOS-WordPress/cm4all-wp-impex/tree/develop/docs/gh-pages/src/jsonschema).

There are a variety of tools for working with [JSON Schema](https://json-schema.org/).

## Extensibility

ImpEx provides a foundation of core functionality to easily integrate custom WordPress data into the export

### Third-party data integration

Third-party WordPress plugins or themes often persist data in custom database tables, `wp_options` or other data sources.

ImpEx provides [WordPress actions](https://developer.wordpress.org/plugins/hooks/actions/) and [WordPress filters](https://developer.wordpress.org/plugins/hooks/filters/) to integrate custom data into the export.

Third-party plugins can easily contribute their own data provider to the export by registering [actions](https://developer.wordpress.org/plugins/hooks/actions/) and [filters](https://developer.wordpress.org/plugins/hooks/filters/) with ImpEx.

> A ImpEx Data Provider is a import /export definition exposes plugin data to be used by other developers to integrate the data in their own export.

### API

Custom export profiles can be defined using the [ImpEx API](api/configuration.html).

That does not mean that your own code depends on the ImpEx plugin to be installed and active. ImpEx uses [WordPress actions](https://developer.wordpress.org/plugins/hooks/actions/) and [WordPress filters](https://developer.wordpress.org/plugins/hooks/filters/) your plugin will work as is also if ImpEx is not installed.

The ImpEx API provides an interface for

- Data provider

  Data provider expose data (Export) or consume data (Import). Typically a third-party plugin will register a Import Provider and Export Provider to ImpEx. Theses providers can be used by ImpEx profiles to to integrate the plugin data into export and import.

- Profiles

  A ImpEx profile is a set of configuration options to define what data to export and/or import.

Furthermore ImpEx provides a foundation of helpers to create custom ImpEx profiles within a few lines for post types, while database tables or wp_options.

## Managed WordPress support

If you operate your WordPress website using a managed WordPress hosting service, you often have limited or not direct access to the WordPress database and installation.

In these scenarios most WordPress Import / Export plugins does not work as expected.

ImpEx only needs access to the WordPress REST API an works like a charm in almost any managed WordPress hosting environment.

## Commandline interface (CLI)

[wp-cli](https://wp-cli.org/) is a great tool for managing WordPress installations. Unfortunately it needs direct access to the WordPress installation and database.

Thus ImpEx provides a [CLI interface](impex-cli.html) to interact with a WordPress installation using pure WordPress REST API. The [ImpEx CLI](impex-cli.html) works perfectly on managed WordPress instances.

## REST API support

ImPEx provides a [WordPress REST API](https://developer.wordpress.org/rest-api/) extension for importing and exporting data. [All ImpEx functions are available as REST API endpoints](index.html#rest-api).

## Scalability

It's easy to export a WordPress website with a few pages. But what if you have a lot of pages and hundreds of images ?

ImpEx is designed to be scalable. It can can handle hundreds of pages/posts and media files from scratch.

### Streaming API

ImpEx API is designed to import/export a WordPress website in _chunks_ of _slices_.

A chunk is a part of the export data. It basically results in a directory containing a number of exported slices. Each slice is a json file describing (and most times also containing) data like post(s) or any other WordPress data.

The API ist designed to be resumable. A aborted export (and even import) can be resumed at any time.

## Import Framework for websites from third-party website builders

ImpEx is also a great choice to import a website from a third-party website builder or even a static website.

ImpEx provides a [Framework to convert any existing website to ImpEx](cm4all-wp-impex-generator/index.html).

Using [@cm4all-wp-impex/generator](cm4all-wp-impex-generator/index.html) you can convert html pages to valid WordPress content.

Since this [package](<[@cm4all-wp-impex/generator](cm4all-wp-impex-generator/index.html)>) provides an API you can easily add custom transformations to third-party site builder specialties into valid ImpEx export data.

# Generating custom content

Even more, [@cm4all-wp-impex/generator](cm4all-wp-impex-generator/index.html) allows you to transform **any** data from anywhere into WordPress content.

There ist actually no limit - everything can be transformed into WordPress content using [@cm4all-wp-impex/generator](cm4all-wp-impex-generator/index.html). See the [Static website conversion tutorial](./cm4all-wp-impex-generator/static-website-tutorial.html).
