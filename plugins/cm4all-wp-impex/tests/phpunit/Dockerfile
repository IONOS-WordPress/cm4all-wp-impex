FROM wordpressdevelop/phpunit:9-php-8.0-fpm

LABEL maintainer Lars Gersmann <lars.gersmann@cm4all.com>

RUN apt-get -qy install $PHPIZE_DEPS \
  && pecl install xdebug \
  && docker-php-ext-enable xdebug

# client_host 172.12.0.1 works only using linux (see https://github.com/WordPress/gutenberg/issues/20636#issuecomment-666335104) 
# xdebug.remote_host=172.17.0.1 should be xdebug.remote_host=host.docker.internal on Win or Mac
RUN echo "xdebug.mode=debug" >> /usr/local/etc/php/conf.d/xdebug.ini && \
  echo "xdebug.start_with_request =1" >> /usr/local/etc/php/conf.d/xdebug.ini && \
  echo "xdebug.client_port=9004" >> /usr/local/etc/php/conf.d/xdebug.ini && \
  echo "xdebug.idekey=VSCODE" >> /usr/local/etc/php/conf.d/xdebug.ini && \
  echo "xdebug.client_host=172.17.0.1" >> /usr/local/etc/php/conf.d/xdebug.ini && \
  echo "xdebug.discover_client_host=0" >> /usr/local/etc/php/conf.d/xdebug.ini

LABEL xdebug_enabled="true"