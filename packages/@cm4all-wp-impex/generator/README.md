This package provides a foundation of JavaScript functions/classes for transforming almost any kind of data into WordPress content.

[`@cm4all-wp-impex/generator`](https://www.npmjs.com/@cm4all-wp-impex/generator) is especially useful for converting bare HTML content and website-builder/CMS generated HTML into WordPress content.

The framework does not require a WordPress instance. It rather offers an extensible platform for generating WordPress content consumable by the [ImpEx WordPress plugin](https://github.com/IONOS-WordPress/cm4all-wp-impex).

> [ImpEx](https://github.com/IONOS-WordPress/cm4all-wp-impex) is a [Open Source WordPress plugin for importing / exporting WordPress data](https://github.com/IONOS-WordPress/cm4all-wp-impex).
> _[`@cm4all-wp-impex/generator`](https://www.npmjs.com/@cm4all-wp-impex/generator) is part of the [ImpEx WordPress plugin](https://github.com/IONOS-WordPress/cm4all-wp-impex) project._

[Watch the tutorial on YouTube](https://img.youtube.com/vi/pjG69RmULYo/2.jpg):

[![Watch the video](https://img.youtube.com/vi/pjG69RmULYo/2.jpg)](https://www.youtube.com/watch?v=pjG69RmULYo)

# Details

The [ImpEx WordPress plugin](https://github.com/IONOS-WordPress/cm4all-wp-impex) specifies a [JSON](https://www.json.org/) file [based import/export format for WordPress content](https://ionos-wordpress.github.io/cm4all-wp-impex/migrating-content.html)).

[`@cm4all-wp-impex/generator`](https://www.npmjs.com/@cm4all-wp-impex/generator) provides

- functionality for transforming data into [Gutenberg block annotated HTML](https://developer.wordpress.org/block-editor/explanations/architecture/data-flow/#the-anatomy-of-a-serialized-block)

- support for creating ImpEx JSON files containing [WordPress content](https://ionos-wordpress.github.io/cm4all-wp-impex/migrating-content.html#content-aka-wordpress-postspages) (like pages/posts/templates/blocks/ patterns and so on) and [WordPress attachments](https://ionos-wordpress.github.io/cm4all-wp-impex/migrating-content.html#attachments-like-pictures-and-videos) like images

- functions to [create file/folder structure expected](https://ionos-wordpress.github.io/cm4all-wp-impex/migrating-content.html) by the [ImpEx WordPress plugin](https://github.com/IONOS-WordPress/cm4all-wp-impex)

Last but not least [`@cm4all-wp-impex/generator`](https://www.npmjs.com/@cm4all-wp-impex/generator) includes a [full featured example transforming a complete static website into WordPress content](https://github.com/IONOS-WordPress/cm4all-wp-impex/tree/develop/packages/%40cm4all-wp-impex/generator/examples/impex-complete-static-homepage-conversion) consumable by [ImpEx WordPress plugin](https://github.com/IONOS-WordPress/cm4all-wp-impex). The [example](https://github.com/IONOS-WordPress/cm4all-wp-impex/tree/develop/packages/%40cm4all-wp-impex/generator/examples/impex-complete-static-homepage-conversion) is the perfect starting point for creating your own WordPress content generator.

# Installation

`npm install @cm4all-wp-impex/generator`

# Development

- clone [ImpEx WordPress plugin Git repository](https://github.com/IONOS-WordPress/cm4all-wp-impex) project : `git clone https://github.com/IONOS-WordPress/cm4all-wp-impex.git`

- cd into the `@cm4all-wp-impex/generator` sub-project : `cd packages/@cm4all-wp-impex/generator`

- ensure the correct NodeJS Version (see https://github.com/IONOS-WordPress/cm4all-wp-impex/blob/develop/.nvmrc) is installed : `nvm install`

- install package dependencies : `npm ci`

- run the tests : `npm run test`

# Usage

[`@cm4all-wp-impex/generator`](https://www.npmjs.com/@cm4all-wp-impex/generator) exposes an API for generating WordPress content.

## API

To use the API just import the exposed API into your code.

```js
import { ImpexTransformer, traverseBlocks, ImpexSliceFactory } from `@cm4all-wp-impex/generator`;
```

### Transforming data into WordPress content

Data transformation into [Gutenberg block annotated HTML](https://developer.wordpress.org/block-editor/explanations/architecture/data-flow/#the-anatomy-of-a-serialized-block)
is done by the [`ImpexTransformer`](https://github.com/IONOS-WordPress/cm4all-wp-impex/blob/develop/packages/%40cm4all-wp-impex/generator/src/impex-content-transform.js) singleton.

[`ImpexTransformer`](https://github.com/IONOS-WordPress/cm4all-wp-impex/blob/develop/packages/%40cm4all-wp-impex/generator/src/impex-content-transform.js) can be configured by calling it's `setup(...)` function supporting various hooks for customizing the transformation.

`ImpexTransformer.transform(data)` transforms the content provided in the `data` argument into [Gutenberg block annotated HTML](https://developer.wordpress.org/block-editor/explanations/architecture/data-flow/#the-anatomy-of-a-serialized-block).

#### `ImpexTransformer.setup({/* options */})`

##### Options

- `verbose` (boolean, default : `false`) enables verbose output for debugging purposes

- `onLoad(data : any) : string` (function, default : undefined) callback executed by `transform(...)` function. `data` argument is the initial data to transform.

  This callback is intended to be used for converting the initial data into HTML.

  Example: If your initial `data` is markdown content this callback should transform it to HTML:

  ```js
  ...
  ImpexTransformer.setup({
    onLoad(data) {
      return markdown.toHTML(data);
    }
  });
  ...
  ```

  If `onLoad` is not defined the `transform` function will assume the `data` argument is valid HTML.

- `onDomReady(Document : document) : void` (function, default : undefined) callback executed when HTML is loaded and the DOM is ready.

  At this stage, you can use the HTML DOM manipulation API (`querySelector` for example) to rearrange the HTML DOM the way you need.

  > The `Transformer` uses [JSDOM](https://www.npmjs.com/package/global-jsdom) to provide DOM capabilities to NodeJS. So you can use everything you know about DOM manipulation in NodeJS.

  See [tests](https://github.com/IONOS-WordPress/cm4all-wp-impex/blob/develop/packages/%40cm4all-wp-impex/generator/tests/test-impex-10-transform-hooks.js) for example usage.

- `onRegisterCoreBlocks() : boolean` (function, default : undefined) callback to register Gutenberg blocks.

  This callback is the power horse transforming HTML to [Gutenberg block annotated HTML](https://developer.wordpress.org/block-editor/explanations/architecture/data-flow/#the-anatomy-of-a-serialized-block).

  **Most transformation work is delegated to the [Gutenberg Block Transforms API](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-transforms/). This API processes the given DOM and applies the Gutenberg Block transformations of all registered blocks. The result is valid [Gutenberg block annotated HTML](https://developer.wordpress.org/block-editor/explanations/architecture/data-flow/#the-anatomy-of-a-serialized-block) as we want it.**

  Using the `onRegisterCoreBlocks` callback you can register your own Gutenberg blocks (including their [transform rules](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-transforms/)) or attach additional [transform rules](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-transforms/) to existing core Gutenberg blocks utilizing [Gutenberg filter '`blocks.registerBlockType`'](https://developer.wordpress.org/block-editor/reference-guides/filters/block-filters/#blocks-registerblocktype).

  If your `onRegisterCoreBlocks` callback returns `true`, the [core Gutenberg blocks](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-library/#registercoreblocks) transform rules will be reset to its defaults.

  If `onRegisterCoreBlocks` is not given, `transform(...)` will assume that the [core Gutenberg blocks](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-library/#registercoreblocks) should be used as-is.

  See [tests](https://github.com/IONOS-WordPress/cm4all-wp-impex/blob/develop/packages/%40cm4all-wp-impex/generator/tests/test-impex-10-transform-hooks.js) for example usage.

- `onSerialize(blocks : array) : array` (function, default : undefined) callback executed after the [Gutenberg block](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-library/#registercoreblocks) transform rules have been applied.

  The resulting array of Gutenberg blocks is passed to the callback. The callback can modify the blocks array and is expected to return them.

  Example transforming all [Gutenberg Image block](https://wordpress.org/support/article/image-block/) attributes into caption block attribute. This will result in a `<figcaption>` element inside the block output:

  ```js
  ImpexTransformer.setup({
    onSerialize(blocks) {
      // takeover img[@title] as figcaption in every block
      for (const block of traverseBlocks(blocks)) {
        if (block.name === "core/image") {
          block.attributes.caption = block.attributes.title;
          delete block.attributes.title;
        }
      }

      return blocks;
    },
  });
  ```

  > `traverseBlocks` is a helper function exposed by this package to traverse the Gutenberg block hierarchy like a flat array.

  See [tests](https://github.com/IONOS-WordPress/cm4all-wp-impex/blob/develop/packages/%40cm4all-wp-impex/generator/tests/test-impex-10-transform-hooks.js) for example usage.

#### `ImpexTransformer.transform(data : any) : string`

The `transform` function transforms the given `data` into [Gutenberg block annotated HTML](https://developer.wordpress.org/block-editor/explanations/architecture/data-flow/#the-anatomy-of-a-serialized-block).

The `data` argument can be anything. All hooks configured using `ImpexTransformer.setup(...)` will take effect by executing this function.

The returned string is valid [Gutenberg block annotated HTML](https://developer.wordpress.org/block-editor/explanations/architecture/data-flow/#the-anatomy-of-a-serialized-block).

### Encapsulate Gutenberg block annotated HTML in ImpEx slice JSON data structure

To import the generated [Gutenberg block annotated HTML](https://developer.wordpress.org/block-editor/explanations/architecture/data-flow/#the-anatomy-of-a-serialized-block) into WordPress we need to generate [ImpEx WordPress plugin](https://github.com/IONOS-WordPress/cm4all-wp-impex) conform JSON files wrapping the content with WordPress meta-data.

Class `ImpexSliceFactory` provides a simple way to generate [WordPress ImpEx Slice JSON structures](https://ionos-wordpress.github.io/cm4all-wp-impex/migrating-content.html#data-files).

At first we need to create an instance of `ImpexSliceFactory`:

```js
const sliceFactory = new ImpexSliceFactory({
  /* options */
});
```

There is just one (optional) option `next_post_id : integer` (default : 1) which might be used to provide a individual start `post_id`. `next_post_id` is only taken into account when creating [content slices for WordPress content](https://ionos-wordpress.github.io/cm4all-wp-impex/migrating-content.html#data-files) like `posts`/`pages` or media.

_The [ImpEx WordPress plugin](https://github.com/IONOS-WordPress/cm4all-wp-impex) supports some more slice types (for exporting whole database tables and more) but in these cases `next_post_id` is not in use._

Using the `ImpexSliceFactory` instance we've created we can now generate [WordPress ImpEx Slice JSON structures](https://ionos-wordpress.github.io/cm4all-wp-impex/migrating-content.html#data-files) for WordPress content or media by calling function `createSlice(sliceType : string, callback(factory, sliceJson : any) : any)`.

The `sliceType` argument is the type of the slice to be created.

The `callback` function is called with the `ImpexSliceFactory` instance and the generated slice JSON structure as parameters .

#### Encapsulate WordPress content into [ImpEx JSON](https://ionos-wordpress.github.io/cm4all-wp-impex/migrating-content.html#data-files)

Creating the JSON for a WordPress `post` is dead simple :

```js
const slice = sliceFactory.createSlice("content-exporter", (factory, slice) => {
  slice.data.posts[0].title = "Hello";
  slice.data.posts[0]["wp:post_content"] =
    "<!-- wp:paragraph --><p>my friend</p><!-- /wp:paragraph -->";
  return slice;
});
```

Creating a WordPress `page` with some additional WordPress meta-data works the same way:

```js
const slice = sliceFactory.createSlice("content-exporter", (factory, slice) => {
  slice.data.posts[0].title = "Hello";
  slice.data.posts[0]["wp:post_type"] = "page";
  slice.data.posts[0]["wp:post_excerpt"] = "A page about my friend";
  slice.data.posts[0]["wp:post_content"] =
    "<!-- wp:paragraph --><p>Hello my my friend</p><!-- /wp:paragraph -->";
  return slice;
});
```

#### Encapsulate WordPress attachments like images into [ImpEx JSON](https://ionos-wordpress.github.io/cm4all-wp-impex/migrating-content.html#data-files)

Creating the JSON for a WordPress `attachment` is even dead simple :

```js
// declares a attachment for image './foo.jpg'
const slice = sliceFactory.createSlice("attachment", (factory, slice) => {
  slice.data = "./foo.jpg";

  return slice;
});
```

In most cases, our imported content (aka posts/pages) will reference the media in various ways like `/image/foo.jpg` or `../../images/foo.jpg` and so on.

[ImpEx WordPress plugin](https://github.com/IONOS-WordPress/cm4all-wp-impex) will take care about replacing image references in [ Gutenberg block annotated HTML](https://developer.wordpress.org/block-editor/explanations/architecture/data-flow/#the-anatomy-of-a-serialized-block) if we provide a replacement hint `impex:post-references` (see [Attachments (like Pictures and Videos)](https://ionos-wordpress.github.io/cm4all-wp-impex/migrating-content.html#attachments-like-pictures-and-videos) for details).

```js
const slice = sliceFactory.createSlice("attachment", (factory, slice) => {
  slice.data = "./foo.jpg";
  // will result in replacing all matching references in posts of the WordPress instance with the link to the imported image
  slice.meta["impex:post-references"] = [
    "/image/foo.jpg",
    "../../images/foo.jpg",
  ];

  return slice;
});
```

### Generate filenames for the JSON slice data in [ImpEx Export format](https://ionos-wordpress.github.io/cm4all-wp-impex/migrating-content.html#preparation)

The [ImpEx WordPress plugin](https://github.com/IONOS-WordPress/cm4all-wp-impex) imports and exports data into a directory structure according to the [ImpEx Export format](https://ionos-wordpress.github.io/cm4all-wp-impex/migrating-content.html).

[`@cm4all-wp-impex/generator`](https://www.npmjs.com/@cm4all-wp-impex/generator) supports creating the correct paths by providing a static generator function `SliceFactory.PathGenerator()`.

This function returns a [Generator](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Generator) function yielding a new relative path each time it's `next()` function is called.

The optional `SliceFactory.PathGenerator(max_slices_per_chunk : integer = 10)` function parameter may be used to limit the number of slices per chunk directory to a custom value.

```js
import { ImpexSliceFactory } from "@cm4all-wp-impex/generator";
...

const pathGenerator = ImpexSliceFactory.PathGenerator();
...

// 2  => only 2 slice files per chunk directory
const gen = SliceFactory.PathGenerator(2);

console.log(gen.next().value); // => "chunk-0001/slice-0001.json"
console.log(gen.next().value); // => "chunk-0001/slice-0002.json");
console.log(gen.next().value); // => "chunk-0002/slice-0001.json");
console.log(gen.next().value); // => "chunk-0002/slice-0002.json");
console.log(gen.next().value); // => "chunk-0003/slice-0001.json");
console.log(gen.next().value); // => "chunk-0003/slice-0002.json");
...

```

See [tests](https://github.com/IONOS-WordPress/cm4all-wp-impex/blob/develop/packages/%40cm4all-wp-impex/generator/tests/test-impex-30-slice-factory-pathgenerator.js) and [static website transformation example](https://github.com/IONOS-WordPress/cm4all-wp-impex/blob/develop/packages/%40cm4all-wp-impex/generator/examples/impex-complete-static-homepage-conversion/index.js) for real world usage.
