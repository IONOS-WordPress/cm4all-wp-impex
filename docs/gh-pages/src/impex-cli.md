<!-- toc -->

# CLI

ImpEx provides a command-line tool to interact with the ImpEx plugin remotely using WordPress HTTP REST API.

Using this tool you can import and export data from and to a remote WordPress installation.

> impex-cli works also fine at _most_ managed WordPress installations since it does not need direct WordPress access like [wp-cli](https://wp-cli.org/).

# Prerequisites

ImpEx CLI requires PHP 7.4 or higher and the `php-curl` extension.

## Installation

ImpEx CLI is available at the [ImpEx release page](https://github.com/IONOS-WordPress/cm4all-wp-impex/releases/latest).

Download the 'ImpEx CLI' archive and extract its contents.

The ImpEx CLI is provided in 2 flavors :

- `impex-cli.php` needs at least PHP 8.0

- `impex-cli-php7.4.0.php` is transpiled to be PHP 7.4 compatible

> Linux/MacOS Users may mark the impex-cli files as executable by running `chmod +x *.php` for better usability.

If you do not have the right PHP version installed on your machine but want to play with the ImpEx CLI you can give the official PHP Docker image a try
_(Assuming your working dir contains the extracted impex-cli php files and impex-cli options needs to be adjusted to your needs)_ :

Using the official PHP 7.4 Docker image :

```sh
docker run \
  -it \
  --network host \
  --rm \
  -v "$PWD":/usr/src/myapp \
  --workdir /usr/src/myapp \
  php:7.4-cli \
  php \
  impex-cli-php7.4.0.php \
    export-profile \
    list \
    -username=<adminuser> \
    -password=<password> \
    -rest-url=http://localhost:8888/wp-json
```

Alternatively using the PHP 8.0 image:

```sh
docker run \
  -it \
  --network host \
  --rm \
  -v "$PWD":/usr/src/myapp \
  --workdir /usr/src/myapp \
  php:8.0-cli \
  php \
    impex-cli.php \
    export-profile \
    list \
    -username=<adminuser> \
    -password=<password> \
    -rest-url=http://localhost:8888/wp-json
```

## Syntax

impex-cli.php `operation` `sub-operation?` `-rest-url=[wordpress-restapi-url]` [options] [arguments]?

## Common options and flags

Some of the impex-cli options are common to all operations.

### Options

Options are command-line arguments consisting of key and value.

The value can be wrapped within `"` or `'`.

#### `rest-url`

The `rest-url` option as **always required for all impex-cli operations (except `help`)** since it specifies the remote WordPress installation to interact with.

A typical `rest-url` value in a [wp-env development environment](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/) is `http://example.com/wp-json/`.

> Please ensure that the remote WordPress installation has the REST API enabled. Otherwise impex-cli is unable to communicate with the installation.

#### `username` and `password`

If you can access your WordPress installation using `username` and `password` credentials via HTTP Basic Auth, you can use the `--username` and `--password` options to specify them.

Example:

```sh
impex-cli.php export-profile \
  -username=<adminuser> \
  -password='<password>' \
  -rest-url=http://example.com/wp-json
```

> By providing the credentials via `username` and `password` options impex-cli will authenticate against the remote WordPress installation using the HTTP BASIC AUTH method.

#### HTTP Headers

You can provide as many HTTP headers as you like to impex-cli. All HTTP headers are passed to every request as is.

Example:

```sh
impex-cli.php export-profile \
  -H="X-foo=bar" \
  -H="X-myrealm=cheers" \
  -username=<adminuser> -password='<password>' \
  -rest-url=http://example.com/wp-json
```

### Flags

Flags are command-line arguments consisting of just a name.

#### `verbose`

Enable verbose log output.

Example:

```sh
impex-cli.php export-profile \
  -verbose \
  -username=<adminuser> -password='<password>' \
  -rest-url=http://example.com/wp-json
```

#### `CURLOPT_VERBOSE`

Enable verbose CURL output.

> This flag will result in a lot of output and is therefore not recommended for normal use.

Example:

```sh
impex-cli.php export-profile \
  -CURLOPT_VERBOSE \
  -username=<adminuser> -password='<password>' \
  -rest-url=http://example.com/wp-json
```

## Authentication

If your WordPress installation does not use HTTP Basic Auth, you need to authenticate using HTTP headers.

Since impex-cli supports additional header options you're a lucky winner.

Example (doing HTTP Basic Auth using plain HTTP headers):

```sh
impex-cli.php export-profile \
  -H='Authorization: Basic YWRtaW46cGFzc3dvcmQ=' \
  -rest-url=http://example.com/wp-json
```

## Operations

### `export`

The `export` operation exports and downloads data using the ImpEx plugin of the WordPress installation.

A ImpEx export results in a directory structure containing

- JSON Files for structured data

  WordPress content will be stored in plain JSON files called [slices](explanation-of-terms.html#slice). _This gives you also the option to transform the content locally before re-importing them somewhere else._

- Blobs for attachments/media

  Attachments and media will be saved "as is" to the local filesystem. So if you have a `jpg` attachment in your WordPress installation it gets exported also as a `jpg` file beside its [slice](explanation-of-terms.html#slice).

Example:

```sh
impex-cli.php export \
  -username=<adminuser> -password='<password>' \
  -rest-url=http://localhost:8888/wp-json \
  -overwrite \
  -profile=base \
  ~/tmp
```

After execution the target directory contains a new directory with the exported data:

```
export-cm4all-wordpress-created-
├── chunk-0001
│   ├── slice-0000.json
│   ├── slice-0001.json
│   ├── slice-0001-logo-fabrics.png
│   ├── slice-0002-johny-goerend-ou-GkKJm3fc-unsplash.jpg
│   ├── slice-0002.json
│   └── slice-0003.json
...
├── chunk-0006
│   ├── slice-0000.json
│   ├── slice-0001.json
│   ├── slice-0002.json
│   ├── slice-0003.json
│   ├── slice-0004.json
│   ├── slice-0005.json
│   ├── slice-0006.json
│   ├── slice-0007.json
│   ├── slice-0008.json
│   └── slice-0009.json
└── chunk-0007
    ├── slice-0000.json
    └── slice-0001.json
```

> All export data files are split over `chunk-*` directories) to prevent getting a single directory containing too much files slowing down file
> managers like Windows Explorer.

#### `profile` option

An ImpEx export profile defines what data should be exported.

> To get a list of available ImpEx export profiles see impex-cli operation [`export-profiles`](#export-profiles)

You will usually use the predefined 'base' export profile exporting pages/posts/attachments and all FSE data like templates/reusable blocks and stuff.

#### `overwrite` flag

The export operation will abort in case of an existing ImpEx export directory. Using the `overwrite` flag you can force deletion of the existing directory before export.

#### `directory` argument

The `directory` argument specifies the export target directory.
The `export` operation will create a top-level directory in the specified directory and stores everything else in [chunk](explanation-of-terms.html#chunk) subdirectories.

### `import`

The `import` operation imports an ImpEx export from the specified directory into the remote WordPress installation.

Example usage:

```sh
impex-cli.php import \
  -username=<adminuser> -password='<password>' \
  -rest-url=http://localhost:8888/wp-json \
  -options='{"impex-import-option-cleanup_contents" : true}'
  ~/tmp/my-export
```

This snippet will 

- upload the whole exported data in the directory 
- import them using the `all` profile at the WordPress installation.
  - the `impex-import-option-cleanup_contents` option will cleanup existing post, page, media, block pattern, nav_menu and reusable block items right before starting the import.

#### `profile` option

An ImpEx import profile defines what data should be imported. If not provided, the import will fallback to default import profile `all`.

> To get a list of available ImpEx import profiles see impex-cli operation [`import-profiles`](#export-profiles)

#### `directory` argument

The `directory` argument specifies the directory where the import data resides.

> The directory argument takes the directory path created by the `export` operation.

#### `options` argument

The `options` argument let's you provide ImpEx import options. The `options` value is expected to be an associative JSON object. 

Valid options are : 

- `impex-import-option-cleanup_contents`
  
  You may want to cleanup your WordPress content right before import. That's what the `impex-import-option-cleanup_contents` option is made for. If this option is set to `true` ImpEx will remove any 

  - post
  - page 
  - media
  - block pattern
  - nav_menu
  - reusable block 
  
  item right before starting the import.

### `export-profiles`

#### `list`

Lists all available export profiles in JSON format.

Example usage:

```
impex-cli.php \
  export-profile \
  list \
  -username=<adminuser> -password='<password>' \
  -rest-url=http://localhost:8888/wp-json
```

Example output (_may vary for your installation_):

```json
[
  {
    "name": "base",
    "description": "Exports posts/pages including media assets"
  },
  {
    "name": "cm4all-wordpress",
    "description": "Exports posts/pages/media-assets and plugin data of [cm4all-wordpress,complianz-gdpr,ninja-forms,ultimate-maps-by-supsystic] if these plugins are enabled"
  },
  {
    "name": "impex-export-profile-example",
    "description": "Exports posts/pages/media-assets and plugin data of [cm4all-wordpress,complianz-gdpr,ninja-forms,ultimate-maps-by-supsystic] if these plugins are enabled"
  }
]
```

### `import-profiles`

#### `list`

Lists all available import profiles in JSON format.

Example usage:

```sh
impex-cli.php \
  import-profile \
  list \
  -username=<adminuser> -password='<password>' \
  -rest-url=http://localhost:8888/wp-json
```

Example output (_may vary for your installation_):

```json
[
  {
    "name": "all",
    "description": "Import everything"
  },
  {
    "name": "impex-import-profile-example",
    "description": "Import everything example with event listener"
  }
]
```

### `help`

Prints the impex-cli help.
