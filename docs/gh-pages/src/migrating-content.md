<!-- toc -->

# Migrating content

Migrating existing content into WordPress is a very common task.

Impex provides tooling support for migrating your content to WordPress.

## Preparation

Impex imports data from a directory containing JSON files grouped in `chunk-\*` sub directories.

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

Impex imports slice files ordered by name. So the slices in sub directory `chunk-0001` will be imported first, then the slices in `chunk-0002` and so on.

Same rule for `slice-*.json` files within the same `chunk-\*` sub directory : `slice-0001.json` will be imported before `slice-0002.json` and so on.

> Know that import order is important. If you import content referencing images/videos in the wrong order, you will get broken links. Impex will rewrite/fix media links in the content if you **import content as first and media afterwards.**

Have a look at this [sample Impex export](https://github.com/IONOS-WordPress/cm4all-wp-impex/tree/develop/impex-cli/tests/fixtures/simple-import) provided by the Impex plugin to get a clue about a minimal working impex export containing content and referencing images.

## Data files

`slice-*.json` files are JSON files containing data.

The data might be anything expressed in textual form. Beside the data itself, each `slice-*.json` file contains some json data describing the content so that Impex knows how to import.

The real data is stored in the `data` property.

An minimal slice file transporting a single Wordpress post looks like this:

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

All other informations are reserved for versioning and content identification.

### Content (aka HTML)

Download JSONSchema definition for content files : [slice-content.json](https://github.com/IONOS-WordPress/cm4all-wp-impex/tree/develop/docs/gh-pages/src/jsonschema/slice-content.json)

```json
{{#include ./jsonschema/slice-content.json}}
```

### Attachments (like Pictures and Videos)

Attachments a binary files like images/videos or anything else stored in the WordPress `uploads` directory.

Download JSONSchema definition for media files : [slice-attachment.json](https://github.com/IONOS-WordPress/cm4all-wp-impex/tree/develop/docs/gh-pages/src/jsonschema/slice-content.json)

```json
{{#include ./jsonschema/slice-attachment.json}}
```

### Other data

Although Impex provides a simple way to import content and media, you may also want to import more advanced data like database tables or settings into WordPress.

Impex can import

- relational data like database tables

- key/value based settings

into WordPress.

@TODO: Add JSONSchema / examples for other data.
