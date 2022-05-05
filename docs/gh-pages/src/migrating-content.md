<!-- toc -->

# Migrating content

Migrating existing content into WordPress is a very common task.

ImpEx provides tooling support for migrating data to WordPress.

## Preparation

ImpEx imports data from a directory containing JSON files organized in `chunk-\*` sub-directories.

```
my-exported-website
├── chunk-0001
│   ├── slice-0001.json
│   ├── slice-0002.json
│   ├── slice-0003.json
│   ├── slice-0004.json
│   └── slice-0005.json
├── chunk-0002
│   ├── slice-0001.json
│   ├── slice-0001-wes-walker-unsplash.jpg
│   ├── slice-0002-greysen-johnson-unsplash.jpg
│   ├── slice-0002.json
│   ├── slice-0003-james-wheeler-unsplash.jpg
│   └── slice-0003.json
...
```

> Why that _chunk-\*_ sub directory structure ?
>
> Organizing thousands of content documents and hundreds of images/videos in a single directory slows down file managers like Windows Explorer. That's the one and only reason for `chunk-\*` sub directories.

Both _chunk-\*_ sub directories and the JSON files are suffixed by a 4 digit number.

ImpEx imports slice files ordered by name. So the slices in sub directory `chunk-0001` will be imported first, then the slices in `chunk-0002` and so on.

Same rule for `slice-*.json` files within the same `chunk-\*` sub directory : `slice-0001.json` will be imported before `slice-0002.json` and so on.

> Knowing that import order is important. If you import content referencing images/videos in the wrong order, you will get broken links in your posts. ImpEx will rewrite/fix media links in the content if you **import content as first and media afterwards.**

Have a look at this [sample ImpEx export](https://github.com/IONOS-WordPress/cm4all-wp-impex/tree/develop/impex-cli/tests/fixtures/simple-import) provided by the ImpEx plugin to get a clue about a minimal working ImpEx export containing content and referencing images.

## Data files

`slice-*.json` files are JSON files containing data.

The real data is stored in the `data` property.

The data might be anything expressed in textual form. Beside the data itself, each `slice-*.json` file contains some meta-data describing the contained data so that ImpEx knows how to import.

An minimal slice file transporting a single WordPress post looks like this:

```json
{
  "version": "1.0.0",
  "type": "php",
  "tag": "content-exporter",
  "meta": {
    "entity": "content-exporter"
  },
  "data": {
    "posts": [
      {
        "wp:post_id": 1,
        "wp:post_content": "<!-- wp:paragraph -->\n<p>Hello from first imported post !</p>\n<!-- /wp:paragraph -->",
        "title": "Hello first post!"
      }
    ]
  }
}
```

As you can see the real content is located in the `data` property.

Everything except the `data` property ist used for versioning and content identification.

### Content (aka WordPress posts/pages)

Content slice files wrap regular WordPress _posts_ and _pages_.

Content slices may also transport further content like comments, custom fields, terms, taxonomies, categories, FSE templates/template-parts, global styles and so on. But that's another story.

> To get a clue about the power of content slices by exporting a FSE enabled WordPress instance and inspecting the resulting `slice-_.json` files.

Below is the [JSON Schema](https://json-schema.org/) describing the content slice file format.

Download [JSON Schema](https://json-schema.org/) definition for content slices : [slice-content.json](https://github.com/IONOS-WordPress/cm4all-wp-impex/tree/develop/docs/gh-pages/src/jsonschema/slice-content.json)

```json
{{#include ./jsonschema/slice-content.json}}
```

A content slice may contain any number of WordPress posts/pages/etc.

> When generating a content slice file, it's best to embed only a single page/post per `slice-_.json` file

Each content document is identified by a unique `wp:post_id` property.

The `title` property is used as the title.

`wp:post_content` transports the content.

_See the Content slice [JSONSchema definition](https://github.com/IONOS-WordPress/cm4all-wp-impex/tree/develop/docs/gh-pages/src/jsonschema/slice-content.json) for all supported properties._

Since WordPress expects [block-annotated HTML](https://developer.wordpress.org/block-editor/explanations/architecture/data-flow/#the-anatomy-of-a-serialized-block) you need to transform your HTML content into [block-annotated HTML](https://developer.wordpress.org/block-editor/explanations/architecture/data-flow/#the-anatomy-of-a-serialized-block).

There are 2 options to do that :

- **The gold solution** : annotate almost every HTML tag with the matching [Gutenberg block](https://wordpress.com/support/wordpress-editor/blocks/custom-html-block/).

  ```html
  <!-- wp:paragraph -->
  <p>A bit of custom html utilizing the Gutenberg html block</p>
  <!-- /wp:paragraph -->

  <!-- wp:list -->
  <ul>
    <li>hi</li>
    <li>ho</li>
    <li>howdy</li>
  </ul>
  <!-- /wp:list -->

  <!-- wp:image -->
  <figure class="wp-block-image">
    <img src="./greysen-johnson-unsplash.jpg" />
    <figcaption>Fly fishing</figcaption>
  </figure>
  <!-- /wp:image -->
  ```

- the quick and dirty solution : wrap the whole html content into a WordPress [Custom HTML block](https://wordpress.com/support/wordpress-editor/blocks/custom-html-block/) :

  ```html
  <!-- wp:html -->
  <p>A bit of custom html utilizing the Gutenberg html block</p>
  <ul>
    <li>hi</li>
    <li>ho</li>
    <li>howdy</li>
  </ul>
  <figure>
    <img src="./greysen-johnson-unsplash.jpg" />
    <figcaption>Fly fishing</figcaption>
  </figure>
  <!-- /wp:html -->
  ```

  _Why is this solution dirty ?_

  _=> If you open up a page/post containing a - the quick and dirty solution : wrap the whole html content into a WordPress [Custom HTML block](https://wordpress.com/support/wordpress-editor/blocks/custom-html-block/) in the Gutenberg editor, you will see just the HTML content but its not rendered_. So the quick and dirty solution is actually a no-go from a designers perspective.

The HTML content must be encoded as JSON string in the slice file. See [this example content slice](https://github.com/IONOS-WordPress/cm4all-wp-impex/blob/develop/impex-cli/tests/fixtures/simple-import/chunk-0001/slice-0005.json).

See [Attachments (like Pictures and Videos)](#attachments-like-pictures-and-videos) for importing referenced media files.

### Attachments (like Pictures and Videos)

Attachments a binary files like images/videos or anything else stored in the WordPress `uploads` directory.

Such binary data is handled a bit differently than textual - because it cannot be easily embedded into a JSON file.

Below is a [JSON Schema](https://json-schema.org/) describing the attachment slice file format.

Download [JSON Schema](https://json-schema.org/) definition for media files : [slice-attachment.json](https://github.com/IONOS-WordPress/cm4all-wp-impex/tree/develop/docs/gh-pages/src/jsonschema/slice-content.json)

```json
{{#include ./jsonschema/slice-attachment.json}}
```

Let's say you have a reference to an image in your content :

```html
<img src="./greysen-johnson-unsplash.jpg" />
```

So you need to import the image into your WordPress instance. To do so, you need to

- create a `slice-*json` file (let's name it `slice-0002.json`) declaring the attachment :

  ```json
  {
    "version": "1.0.0",
    "type": "resource",
    "tag": "attachment",
    "meta": {
      "entity": "attachment"
    },
    "data": "./greysen-johnson-unsplash.jpg"
  }
  ```

  As you can see, there is actually only the `data` property referencing the image. Rest of the slice file is just meta-data.

- provide the image in the same chunk directory as it's slice json file and **prefixed** with the slice json file name (`slice-0002.json`) :

  ```
  slice-0002-greysen-johnson-unsplash.jpg
  ```

If you import the slice file using ImpEx, the image will appear in the WordPress `uploads` directory and in the WordPress media page. If you referenced the image in your content, it will also appear in your imported pages/posts.

> Remember: Content slices referencing media files should **ALWAYS** be imported **before** the attachment slices.
>
> This can be achieved by naming content slicing with a lower number than the media slices or - much simpler - keeping the content slices in a lower numbered `chunk-*` directory than the attachments.
>
> See [simple-import](https://github.com/IONOS-WordPress/cm4all-wp-impex/tree/develop/impex-cli/tests/fixtures/simple-import) example for a full featured manually written import at the [ImpEx WordPress plugin GitHub repository](https://github.com/IONOS-WordPress/cm4all-wp-impex).

#### Adjusting attachment urls

If you import posts referencing an image using relative paths, you will need to adjust the image url in your imported posts to the newly imported attachment.

Suppose you have various posts referencing an image in different ways :

```html
<!-- sub/page-one.html -->
...
<img src="../images/greysen-johnson-unsplash.jpg" />

<!-- page-two.html -->
...
<img src="/images/greysen-johnson-unsplash.jpg" />

<!-- page-tree.html -->
...
<img src="./images/greysen-johnson-unsplash.jpg" />
```

After importing generated pages will reference exactly the same IMG `src` attribute, but the url of the imported image attachment will be different.

In this case you can configure replacing the original with the url of the imported image using slice `meta` property `impex:post-references`. This property tells ImpEx that the given references should be replaced with the url of the imported attachment file.

```json
{
  "version": "1.0.0",
  "type": "resource",
  "tag": "attachment",
  "meta": {
    "entity": "attachment",
    "impex:post-references": [
      "../images/greysen-johnson-unsplash.jpg",
      "./images/greysen-johnson-unsplash.jpg"
      "/images/greysen-johnson-unsplash.jpg",
    ]
  },
  "data": "./greysen-johnson-unsplash.jpg"
}
```

### Other data

Although ImpEx provides a simple way to import content and media, you may also want to import more advanced data like database tables or settings into WordPress.

ImpEx provides built-in support for further data :

- relational data like database tables

- key/value based settings (aka `wp_options`)

@TODO: Add JSONSchema / examples for other data.
