<!-- toc -->

# Explanation of terms

ImpEx documentation often refers to special terms.

This page will clarify their meaning.

## ImpEx

The name of the plugin stands for **Imp**ort / **Ex**port => in short **ImpEx**.

## Snapshot

A snapshot is a copy of the content to be imported/exported.

It resides in a separate WordPress database table managed by the ImpEx plugin.

> Attachments/media will be saved in a private sub-directory of the WordPress uploads folder.

You can imagine a snapshot like a server side copy of the content.

You can import / export as many snapshots as you want.

## Provider

Provider handle content for ImpEx. They are used to load (import) or extract (export) data for ImpEx.

There exist a set of standard providers brought by ImpEx for common WordPress data like `posts`, `attachments`, `wp_options` and `database tables`. Custom providers can be registered by the user.

ImpEx provider are basically callback functions called by ImpEx.

A provider gets registered with a unique name. The name is used to reference providers in a [ImpEx profile](#profile).

There are two types of providers: Import and Export provider.

### Export Provider

Export provider expose data as [slices](#slice) to ImpEx.

Because an Export provider may need to produce more than one [slice](#slice) per execution, the callback interface is designed as [generator function](https://www.php.net/manual/en/language.generators.overview.php).

In other words, an Export provider callback function gets called with a configuration and [yields](https://www.php.net/manual/en/language.generators.syntax.php) as many slices as needed. The configuration controls _which_ data should be exported.

_Take a look at the [`**WpOptionsExporterProviderCallback`](https://github.com/IONOS-WordPress/cm4all-wp-impex/blob/develop/plugins/cm4all-wp-impex/inc/impex-export-extension-wp-options.php) to see an example._

### Import Provider

Import provider consume [slices](#slice) from ImpEx.

They work similarly to Export provider and get called with a configuration (controling _what_ data should be imported from a [slice](#slice)) and a [slice](#slice) as parameter.

Since ImpEx does not know about how to handle a [slice](#slice) by itself, it delegates this task to the Import provider.

So an Import Provider checks if it can handle a [slice](#slice) by introspecting the [slice](#slice) meta-data. If it does, it handles the [slice](#slice) data and returns `true` if the slice was successfully imported.

_In all other cases it returns `false`._ In this case ImpEx will take the next Import provider in charge.

_See [`__WpOptionsImportProviderCallback`](https://github.com/IONOS-WordPress/cm4all-wp-impex/blob/develop/plugins/cm4all-wp-impex/inc/impex-import-extension-wp-options.php) as an example_

## Profile

A profile consists of a list of _tasks_ that are executed in the order they are registered.

Each _task_ will reference a _provider_ and it's _configuration_.

> See [`base` Export profile](https://github.com/IONOS-WordPress/cm4all-wp-impex/blob/develop/plugins/cm4all-wp-impex/profiles/export-profile-base.php) as example.

Profiles are used to _programmatically compose_ the data to export (Export Profile) or to _consume_ the data to import (Import Profile).

Furthermore a profile can configure event handler to be triggered in certain situations (like when the import was finished).

_See [`ImpexImport::EVENT_IMPORT_END` event usage](https://github.com/IONOS-WordPress/cm4all-wp-impex/blob/develop/plugins/cm4all-wp-impex-example/inc/impex-import-profile-example.php) for example_

## Chunk

When exporting a huge WordPress site with hundreds of posts and images, the result would be a directory with hundreds of files. To prevent File managers of crashing due to the amount of files, ImpEx splits the exported [slice](#slice) files into sub-directory chunks.

A chunk is sub-directory keeping a configurable unit of [slices](#slice) below the top level export directory. See [an example export directory layout](migrating-content.html#preparation).

## Slice

A slice is a self-containing JSON data structure of both data and its description (aka meta-data).

> An example: a slice of WordPress posts will contain the posts (=> data) and the information thats needed to import the posts (=> meta-data like post title, author, ...).

The slice meta-data consist of a static part (semantic version, type of slice, ...) and a content related part contributed by the [Export provider](#provider) (entity type, any other meta data relevant for importing the data).

> There is one _special_ exception to the rule that a slice is self-contained : Attachment slices. Attachment slices are not self-contained because they contain binary data which cannot effectively stored within JSON.

The slice data are completely contributed by the [Export provider](#provider).

See the [Data files](migrating-content.html#data-files) chapter for more information about the structure of a slice.

_Slice files are strongly typed JSON structures. See the [JSON schema definitions for standard ImpEx slice types in the ImpEx GitHub repository](https://github.com/IONOS-WordPress/cm4all-wp-impex/tree/develop/docs/gh-pages/src/jsonschema)._

## TransformationContext

TransformationContext transports contextual information's about the current import/export process. It's used to pass information from ImpEx to the [Provider](#provider).

_A TransformationContext is an internal data structure._
