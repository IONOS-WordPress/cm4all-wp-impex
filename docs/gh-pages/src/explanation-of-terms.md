<!-- toc -->

# Explanation of terms

ImpEx documentation refers often to special terms.

This page will clarify their meaning.

## ImpEx

The name of the plugin stands for **Imp**ort / **Ex**port => in short **ImpEx**.

## Provider

ImpEx Provider are basically parameterized named callback functions that are called by ImpEx.

A Provider gets registered with a unique name. These name is used to reference providers in a [ImpEx profile](#profile).

There are two types of providers: Import and Export provider.

### Export Provider

Export provider expose data as [slices](#slice) to ImpEx.

Because an Export Provider may need to produce more than one [slice](#slice) per call, the callback function is expected to be a [generator function](https://www.php.net/manual/en/language.generators.overview.php).

In other words, an Export Provider callback function gets called with a configuration and [yields](https://www.php.net/manual/en/language.generators.syntax.php) as many slices as needed.

> Take a look at the [`__WpOptionsExporterProviderCallback`](https://github.com/IONOS-WordPress/cm4all-wp-impex/blob/develop/plugins/cm4all-wp-impex/inc/impex-export-extension-wp-options.php) to see an example.

### Import Provider

Import provider consume [slices](#slice) from ImpEx.

Import Provider work similarly to Export Provider. They get called with a configuration and a [slice](#slice) as parameters.

Since ImpEx does not know about how to handle a [slice](#slice) by itself, it delegates this task to the Import Provider.

So an Import Provider checks if it "knows" how to handle a [slice](#slice) by introspecting the [slice](#slice) metadata. If it does, it handles the [slice](#slice) data and returns `true` if it was successful. _In all other cases it returns `false`._

> See [`__WpOptionsImportProviderCallback`](https://github.com/IONOS-WordPress/cm4all-wp-impex/blob/develop/plugins/cm4all-wp-impex/inc/impex-import-extension-wp-options.php) as an example.

If a Import Provider returns `false`, ImpEx will try the next Import Provider.

## Profile

A Profile consists of a list of _tasks_ that are executed in the order they are listed.

Each _task_ will reference a _provider_ and it's _configuration_.

> See [`base` Export profile](https://github.com/IONOS-WordPress/cm4all-wp-impex/blob/develop/plugins/cm4all-wp-impex/profiles/export-profile-base.php) as example.

Profiles are used to _compose_ the data to export (Export Profile) or to _consume_ the data to import (Export Profile).

Furthermore a profile can configure event handler to be triggered in certain situations like when the import was finished.

> See [`ImpexImport::EVENT_IMPORT_END` event usage](https://github.com/IONOS-WordPress/cm4all-wp-impex/blob/develop/plugins/cm4all-wp-impex-example/inc/impex-import-profile-example.php) for example.

## Chunk

When exporting a huge WordPress site with hundreds of posts and images, it would result in a directory with hundreds of files. To prevent File managers of crashing due to the amount of files, ImpEx splits the exported [slices](#slice) into sub-directory chunks.

A chunk is sub-directory keeping a configurable unit of [slices](#slice) below the top level export directory. See [an example export directory layout](migrating-content.html#preparation).

## Slice

A slice is a self-containing JSON data structure of both data and its description (aka metadata).

> An example: a slice of WordPress posts will contain the posts (=> data) and the information thats needed to import the posts (=> metadata).

The slice metadata consists of a static part (semantic version, type of slice, ...) and a dynamic part contributed by the [Export provider](#provider) (entity type, any other meta data relevant for importing the data).

> There is one _special_ exception to the rule that a slice is self-contained : Attachment slices. Attachment slices are not self-contained because they contain binary data which cannot effectively stored within JSON.

The slice data are completely contributed by the [Export provider](#provider).

See the [Data files](migrating-content.html#data-files) chapter for more information about the structure of a slice.

## TransformationContext

TransformationContext transports contextual information's about the current import/export process. It's used to pass information from ImpEx to the [Provider](#provider).
