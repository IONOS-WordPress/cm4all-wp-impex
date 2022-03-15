# PHPUnit Docker Container.
FROM alpine:latest as builder
LABEL mantainer="Lars Gersmann <lars.gersmann@cm4all.com>"

WORKDIR /tmp

RUN apk --no-cache add \
  bash \
  ca-certificates \
  curl \
  git \
  php8 \
  php8-bcmath \
  php8-ctype \
  php8-curl \
  php8-dom \
  php8-exif \
  php8-fileinfo \
  php8-json \
  php8-mbstring \
  php8-pecl-mcrypt \
  php8-mysqli \
  php8-opcache \
  php8-openssl \
  php8-pcntl \
  php8-pdo \
  php8-pdo_mysql \
  php8-pdo_pgsql \
  php8-pdo_sqlite \
  php8-phar \
  php8-session \
  php8-simplexml \
  php8-soap \
  php8-tokenizer \
  php8-xdebug \
  php8-xml \
  php8-xmlreader \
  php8-xmlwriter \
  php8-zip \
  php8-zlib \
  unzip 

# composer expects command "php" available in path
RUN ln -s /usr/bin/php8 /usr/bin/php

# install composer
RUN wget https://getcomposer.org/installer -O composer-setup.php && php composer-setup.php --install-dir=/usr/bin --filename=composer && rm composer-setup.php

# install phpunit 
RUN composer require "phpunit/phpunit:^9.5" --prefer-source --no-interaction 
# RUN composer require "phpunit/php-invoker" --prefer-source --no-interaction 
RUN ln -s /tmp/vendor/bin/phpunit /usr/local/bin/phpunit 

# Enable X-Debug
RUN sed -i 's/\;z/z/g' /etc/php8/conf.d/*xdebug.ini 
# RUN php -m | grep -i xdebug

# pear support
RUN wget https://pear.php.net/go-pear.phar -O go-pear.phar && echo 'Y' | php go-pear.phar && rm go-pear.phar
# add your pear packages to be installed using --build-arg in the docker run command
ARG PEAR_PACKAGES=""
ONBUILD RUN \
  { \
  [ "${PEAR_PACKAGES}" != "" ]; \
  } || exit 0 && pear install ${PEAR_PACKAGES}

# see https://phpunit.readthedocs.io/en/9.5/installation.html#recommended-php-configuration
RUN echo $'memory_limit=-1\n\
  error_reporting=-1\n\
  log_errors_max_len=0\n\
  zend.assertions=1\n\
  assert.exception=1\n\
  xdebug.show_exception_trace=0\n' >> /etc/php8/php.ini

#####################################################################################################

FROM alpine:latest
# simple trick to get rid of intermediate layers
# https://stackoverflow.com/questions/56117261/how-to-merge-dockers-layers-of-image-and-slim-down-the-image-file
COPY --from=builder / /

VOLUME ["/workdir"]
WORKDIR /workdir

# add a entrypoint script to enable CTRL-C abortion in terminal
# (see https://stackoverflow.com/a/57526365/1554103)
# RUN echo "#!/bin/bash" > /usr/local/bin/docker-entrypoint.sh && \
#   echo '$@' >> /usr/local/bin/docker-entrypoint.sh && \
#   chmod a+x /usr/local/bin/docker-entrypoint.sh

# ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]