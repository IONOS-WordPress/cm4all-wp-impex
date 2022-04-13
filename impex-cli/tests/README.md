# About

a tiny (alpine based) docker image for phpunit testing.

## Features

- php 8

  - including xdebug support

- phpunit 9.5

# Development

build : `DOCKER_BUILDKIT=1 docker build -t cm4all-wp-impex/impex-cli-phpunit .`

- (optional) you can add `--build-arg PEAR_PACKAGES="<space-separated-list-of-pear-packages>"` to auto install pear packages into the image

jump into : `docker run -ti --rm cm4all-wp-impex/impex-cli-phpunit bash`

php.ini : `/etc/php8/php.ini`

## phpunit

`docker run --add-host=host.docker.internal:host-gateway -it -v $(pwd)/..:/workdir --rm cm4all-wp-impex/impex-cli-phpunit phpunit .`

## impex-cli

To import the sample export fixture (see `.impex-cli/tests/fixtures/simple-import`) into `wp-env` simply run:

```
// (optional) cleanup wordpress wp-env instance
make wp-env-clean

// start the import
./impex-cli/impex-cli.php import -username=admin -password=password -rest-url=http://localhost:8888/wp-json -profile=all ./impex-cli/tests/fixtures/simple-import
```

# Usage
