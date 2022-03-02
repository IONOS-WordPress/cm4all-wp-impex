<!-- toc -->

# CLI

Impex provides a commandline tool to interact with the Impex plugin remotely using Wordpress HTTP REST API.

Using this tool you can import and export data from and to a remote wordpress installation.

> impex-cli works also fine at _most_ managed wordpress installations since it does'nt need direct Wordpress access like [wp-cli](https://wp-cli.org/).

## Syntax

impex-cli.php `operation` `sub-operation?` `-rest-url=[wordpress-restapi-url]` [options] [arguments]?

## Common Options and Flags

Some of the impex-cli options are common to all operations.

### Options

Options are commandline arguments consisting of key and value.

The value can be wrapped within `"` or `'`.

#### `rest-url`

The `rest-url` option as **always required for all impex-cli operations (except `help`)** since it specifies the remote wordpress installation to interact with.

A typical `rest-url` value in a [wp-env development environment](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/) is `http://example.com/wp-json/`.

> Please ensure that the remote wordpress installation has the REST API enabled. Otherwise impex-cli is unable to communicate with the installation.

#### `username` and `password`

If you can access your wordpress installation using `username` and `password` credentials and HTTP Basic Auth, you can use the `--username` and `--password` options to specify them.

Example:

```sh
impex-cli.php export-profile \
-username=admin \
-password='password' \
-rest-url=http://example.com/wp-json
```

> By providing the credentials via `username` and `password` options impex-cli will authenticate against the remote wordpress installation using the HTTP BASIC AUTH method.

#### HTTP Headers

You can provide as many HTTP headers as you like to impex-cli. All HTTP headers are passed to every request as is.

Example:

```sh
impex-cli.php export-profile \
-H="X-foo=bar" \
-H="X-myrealm=cheers" \
-username=admin -password='password' \
-rest-url=http://example.com/wp-json
```

### Flags

Flags are commandline arguments consisting of just a name.

#### `verbose`

Enable verbose log output.

Example:

```sh
impex-cli.php export-profile \
-verbose \
-username=admin -password='password' \
-rest-url=http://example.com/wp-json
```

#### `CURLOPT_VERBOSE`

Enable verbose CURL output.

Example:

```sh
impex-cli.php export-profile \
-CURLOPT_VERBOSE \
-username=admin -password='password' \
-rest-url=http://example.com/wp-json
```

## Authentication

If your wordpress installation does not use HTTP Basic Auth, you need to authenticate using HTTP headers.

Since impex-cli supports additional header options you're a lucky winner.

Example (doing HTTP Basic Auth using plain HTTP headers):

```sh
impex-cli.php export-profile \
-H='Authorization: Basic YWRtaW46cGFzc3dvcmQ=' \
-rest-url=http://example.com/wp-json
```

## Operations

### `export`

The `export` operation exports and downloads export data using the Impex plugin on the remote wordpress installation.

A Impex export results in a directory structure containing

- JSON Files for structured data

  Wordpress content will be stored in JSON files. This gives you also the option to tranform the content locally before re-importing them somewehere else.

- Blobs for attachments/media

  Attachments and media will be saved "as is" to the local filesystem. So if you have a `jpg` attachment in your wordpress installation it gets exported also as a `jpg`.

Example:

```sh
impex-cli.php export \
-username=admin -password='password' \
-rest-url=http://localhost:8888/wp-json \
-overwrite \
-profile=base \
~/tmp
```

After calling this script our target directory should contain a newly created directory containing the exported data:

```
export-cm4all-wordpress-created-
├── chunk-0001
│   ├── slice-0000.json
│   ├── slice-0001.json
│   ├── slice-0001-logo-fabrics.png
│   ├── slice-0002-johny-goerend-ou-GkKJm3fc-unsplash.jpg
│   ├── slice-0002.json
│   ├── slice-0003.json
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

> All export data files are split over so called `chunk-*` directories to prevent getting a single directory containing too much files slowing downfile
> managers like Windows Explorer.

#### `profile` option

An Impex export profile defines what data should be exported. It can be a predefined profile or a custom profile.

> To get a list of available Impex export profiles see impex-cli operation [`export-profiles`](#export-profiles)

You will usually use the predefined 'base' export profile exporting pages/posts/attachments and all FSE data like templates/reusable blocks and stuff.

#### `overwrite` flag

The export operation will abort in case of an exiting impex export directory. Using the `overwrite` flag you can force to delete the existing directory and before export.

#### `directory` argument

The `directory` argument specifies the directory where the export data should be stored.
The `export` operation will create a toplevel directory in the specified directory and stores everything else in subdirectories.

### `import`

The `import` operation imports Impex export data from the specified directory into the remote wordpress installation.

> If you repeat the `import` operation again and again you may want to cleanup your wordpress content in between. If you may use [wp-cli](https://wp-cli.org/) on your wordpress installation you can execute
>
> ```sh
> wp post delete --force $(wp post list --post_type=attachment --format=ids)
> wp post delete --force $(wp post list --post_type=page --format=ids)
> wp post delete --force $(wp post list --post_type=post --format=ids)
> ```
>
> to delete all pages/posts and attachments.

Example usage:

```sh
impex-cli.php import \
-username=admin -password='password' \
-rest-url=http://localhost:8888/wp-json \
-profile=all \
~/tmp/my-export
```

This call will read and upload the whole export data in the directory and imports them using the `all` profile at the wordpress installation.

#### `profile` option

An Impex import profile defines what data should be imported.

> To get a list of available Impex import profiles see impex-cli operation [`import-profiles`](#export-profiles)

You will usualy use the predefined `all` import profile.

#### `directory` argument

The `directory` argument specifies the directory where the import data resides.

> The directory argument takes the directory created by the `export` operation.

### `export-profiles`

#### `list`

Lists all available export profiles in JSON format.

Example usage:

```sh
impex-cli.php export-profile list \
-username=admin -password='password' \
-rest-url=http://localhost:8888/wp-json
```

Example output (_may vary for your installation_):

```
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
impex-cli.php import-profile list \
-username=admin -password='password' \
-rest-url=http://localhost:8888/wp-json
```

Example output (_may vary for your installation_):

```
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
