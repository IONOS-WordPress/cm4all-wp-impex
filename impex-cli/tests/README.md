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

# Usage

`docker run -it -v $(pwd)/..:/workdir --rm cm4all-wp-impex/impex-cli-phpunit phpunit .`
