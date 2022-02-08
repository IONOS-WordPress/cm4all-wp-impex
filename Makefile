#!/usr/bin/env make

# useful make links:
#   https://tech.davis-hansson.com/p/make/
#   https://www.olioapps.com/blog/the-lost-art-of-the-makefile/
#   https://gist.github.com/aprell/2b019eb37315e074821318aa14428c4a

# make output less verbose
# MAKEFLAGS += --silent

# ensure make is supporting .RECIPEPREFIX
ifeq ($(origin .RECIPEPREFIX), undefined)
  $(error This Make does not support .RECIPEPREFIX. Please use GNU Make 4.0 or later)
endif
# make to use > as the block character
.RECIPEPREFIX = >

# alwas use bash as shell (to get <<< and stuff working), otherwise sh would be used by default
SHELL := bash
# use bash strict mode o that make will fail if a bash statement fails
.SHELLFLAGS := -eu -o pipefail -c
# disable default rules enabled by default (build yacc, cc and stuff)
MAKEFLAGS += --no-builtin-rules
# warn if unused variables in use
MAKEFLAGS += --warn-undefined-variables
# --

# path to nvm
NVM_SH := ${HOME}/.nvm/nvm.sh
# path to .nvmrc file
_NVMRC := .nvmrc

# abort if no .nvmrc is available
ifeq (,$(wildcard $(_NVMRC)))
  $(error Could not find file '$(_NVMRC)')
endif

_NVMRC_NODE_VERSION := $(file < $(_NVMRC))
CURRENT_NODE_VERSION := $(shell node --version 2>/dev/null || echo 'not installed')
# if current node version does not matches nvmrc noted version
ifneq ($(_NVMRC_NODE_VERSION), $(CURRENT_NODE_VERSION)) 
  $(error expected node version "$(_NVMRC_NODE_VERSION)" not installed (detected version: "$(CURRENT_NODE_VERSION)". Consider calling "nvm use" :-))
endif

# ensure npm is available
ifeq (,$(shell which npm))
  $(error 'Could not find npm')
endif

# prepend [project]/bin, node_modules/.bin and composer/bin PATH environment 
PATH := ./bin:$(shell npm bin):./plugins/cm4all-wp-impex/vendor/bin:$(PATH)

# needs to be exported to be automatically available in wp-env calls
export WP_ENV_HOME := ./wp-env-home

# the docker image used to execute phpunit tests
DOCKER_PHPUNIT_IMAGE := wordpressdevelop/phpunit:9-php-8.0-fpm

MDBOOK_SOURCES := $(wildcard docs/gh-pages/src/*.md)
MDBOOK_TARGETS := $(subst /src/,/book/,$(MDBOOK_SOURCES))

DOCKER_MDBOOK_IMAGE := cm4all-wp-impex/mdbook
DOCKER_MDBOOK := docker run --rm -v $$(pwd)/docs/gh-pages:/data -u $$(id -u):$$(id -g) -i $(DOCKER_MDBOOK_IMAGE) mdbook

IMPEX_PLUGIN_NAME := cm4all-wp-impex
IMPEX_PLUGIN_DIR := ./plugins/$(IMPEX_PLUGIN_NAME)

# javascript 
SCRIPT_SOURCES := $(wildcard $(IMPEX_PLUGIN_DIR)/src/*.mjs)
SCRIPT_TARGETS := $(subst /src/,/dist/,$(SCRIPT_SOURCES:.mjs=.js))

PHP_SOURCES := $(shell find $(IMPEX_PLUGIN_DIR)/ -not -path "*/vendor/*" -not -path "*/tests/*" -name "*.php")

I18N_DIRECTORY := $(IMPEX_PLUGIN_DIR)/languages

PO_SOURCES := $(wildcard $(I18N_DIRECTORY)/*.po)
MO_TARGETS := $(PO_SOURCES:.po=.mo)

# not used right now
# # css
# CSS_SOURCES := $(wildcard plugins/cm4all-wp-impex/src/*.?css)
# CSS_TARGETS := $(subst /src/,/dist/,$(CSS_SOURCES:.scss=.css))

DOCS_MARKDOWN_FILES := docs/*.md
DOCS_MARKDOWN_TARGETS := $(patsubst %.md,dist/%.pdf,$(wildcard $(DOCS_MARKDOWN_FILES)))

DOCS_MERMAID_FILES := docs/**/*.mmd
DOCS_MERMAID_TARGETS := $(patsubst %.mmd,%.mmd.svg,$(wildcard $(DOCS_MERMAID_FILES)))

.PHONY: all 
#HELP: * build sources and spin up wp-env instance
all: $(WP_ENV_HOME) build 

PHONY: build
#HELP: build sources 
build: $(SCRIPT_TARGETS) 
# plugins/cm4all-wp-impex/vendor/autoload.php 

plugins/cm4all-wp-impex/vendor/autoload.php : tmp/composer.phar
# wordpress unit testing with php 8 ist broken right now : https://core.trac.wordpress.org/ticket/46149#comment:49
# since we mashed phpunit 7.5.* into composer{.json,.lock} (required by wp unit testcase) which is incompatible with php 8 we need to 
# tell composer to ignore the platform requirements (--ignore-platform-reqs)
# install phunit 7 into php 8 environment without hassle: 
# php ../../tmp/composer.phar require --dev --ignore-platform-reqs --no-interaction --no-scripts --update-with-all-dependencies phpunit/phpunit:^7.5
> cd plugins/cm4all-wp-impex && php ../../tmp/composer.phar install --no-interaction --ignore-platform-reqs --quiet

tmp/composer.phar : 
> mkdir -p $(@D)
> cd $(@D) && curl -sS https://getcomposer.org/installer | php

plugins/cm4all-wp-impex/dist/%.js : plugins/cm4all-wp-impex/src/%.mjs
# create variable at execution time : (see https://stackoverflow.com/questions/1909188/define-make-variable-at-rule-execution-time)
> $(eval $@_GLOBAL_NAME := $(basename $(notdir $@)))
# development version
> esbuild-bundle.mjs --debug --global-name='$($@_GLOBAL_NAME)' $< $@
# production version
> esbuild-bundle.mjs --global-name='$($@_GLOBAL_NAME)' $< $(@:.js=.min.js)
> touch -m $@ $(@:.js=.min.js)

# not used right now
# plugins/cm4all-wp-impex/dist/%.css : plugins/cm4all-wp-impex/src/%.scss
# # production version
# >  sass --style=compressed --no-source-map $< $(@:.css=-min.css)
# # development version
# >  sass --embed-sources --embed-source-map $< $@

.ONESHELL:
$(WP_ENV_HOME): node_modules 
> mkdir -p dist/cm4all-wp-impex-php7.4.0
> cat << EOF > dist/cm4all-wp-impex-php7.4.0/plugin.php
> <?php
> /**
>  * dummy plugin placeholder for wp-env
>  * Plugin Name: cm4all-wp-impex-php7.4.0
>  **/
> EOF
# if a executable "Makefile-wp-env.preinit.sh" exists, call it now
> [ -x ./Makefile-wp-env.preinit.sh ] && WP_ENV_HOME="$(WP_ENV_HOME)" ./Makefile-wp-env.preinit.sh
> wp-env start --update --xdebug
# skip further wp-env configuration if target was started in github action context
> echo "GITHUB_ACTIONS=$${GITHUB_ACTIONS:-false}"
> if [ "$${GITHUB_ACTIONS:-false}" == "true" ]; then
>   exit 0
> fi
# ensure correct settings of .htaccess file due to required permalinks ("Pretty" permalinks)
# @see https://wordpress.org/support/article/using-permalinks/#using-pretty-permalinks
> WORDPRESS_DIR="$$(echo wp-env-home/*)/WordPress"
> mkdir -p "$$WORDPRESS_DIR/wp-content/upgrade"
> cat >$$WORDPRESS_DIR/.htaccess <<'EOL'
> # file generated my Makefile target wp-env-setup
> 
> # BEGIN WordPress
> php_value upload_max_filesize 64M
> php_value post_max_size 64M
> # The directives (lines) between `BEGIN WordPress` and `END WordPress` are
> # dynamically generated, and should only be modified via WordPress filters.
> # Any changes to the directives between these markers will be overwritten.
> <IfModule mod_rewrite.c>
> RewriteEngine On
> RewriteBase /
> RewriteRule ^index\.php$ - [L]
> RewriteCond %{REQUEST_FILENAME} !-f
> RewriteCond %{REQUEST_FILENAME} !-d
> RewriteRule . /index.php [L]
> </IfModule>
> 
> # END WordPress
> 
> EOL
#
> for instance_prefix in '' 'tests-' ; do
>   CLI_CONTAINER="${instance_prefix}cli" 
>   WORDPRESS_CONTAINER="${instance_prefix}wordpress" 
>
>   wp-env run $$CLI_CONTAINER rewrite flush
>   wp-env run $$CLI_CONTAINER rewrite structure '/%postname%'
>  
>   wp-env run $$WORDPRESS_CONTAINER 'bash -c "chown www-data wp-content/{upgrade,themes,plugins}"'
> done
>
> WP_ENV_OVERRIDE_MAPPINGS=$$(test -f .wp-env.override.json && jq '.mappings' .wp-env.override.json)
> if [ "$$WP_ENV_OVERRIDE_MAPPINGS" != "null" ]; then
# - the sed command deletes the trailing lines (containing a "{" or "}" 
#   and substitute '"wp-content' with '"/var/www/html/wp-content'
>   WP_ENV_OVERRIDE_MAPPINGS="$$(echo "$$WP_ENV_OVERRIDE_MAPPINGS" | sed '/{/d;/}/d;s/"wp-content/\t\t\t"\/var\/www\/html\/wp-content/g;'),"
> else
>  @WP_ENV_OVERRIDE_MAPPINGS=""
> fi
# generate .vscode/launch.json and with path mappings matching the current wp-env instance
> WP_ENV_OVERRIDE_MAPPINGS="$$WP_ENV_OVERRIDE_MAPPINGS" WP_ENV_ROOT="$$(basename $(WP_ENV_HOME))/$$(ls $(WP_ENV_HOME))" envsubst '$$WP_ENV_ROOT $$WP_ENV_OVERRIDE_MAPPINGS' < .vscode/launch.json.template > .vscode/launch.json
> echo "(re)generated '.vscode/launch.json' based on '.vscode/launch.json.template'"
# if a executable "Makefile-wp-env.postinit.sh" exists, call it now
> [ -x ./Makefile-wp-env.postinit.sh ] && WP_ENV_HOME="$(WP_ENV_HOME)" ./Makefile-wp-env.postinit.sh
# tell make that this directory is up to date
> touch -m $(WP_ENV_HOME)

.PHONY: resume
#HELP: * start / resume wp-env instance
resume: node_modules
> wp-env start --xdebug

node_modules: package-lock.json
> npm -q ci
# fixes repeated installation of node_modules again and again 
# (https://gist.github.com/hallettj/29b8e7815b264c88a0a0ee9dcddb6210)
> @touch -m node_modules

package-lock.json: package.json
> npm install --no-fund --package-lock-only

docs/gh-pages/book $(MDBOOK_TARGETS): $(MDBOOK_SOURCES) $(DOCKER_MDBOOK_IMAGE)
> $(DOCKER_MDBOOK) build
# configure github to bypass jekyll processing on github pages
> touch docs/gh-pages/book/.nojekyll
> @touch -m docs/gh-pages/book

HELP: build docs
dist/docs : node_modules $(DOCS_MERMAID_TARGETS) $(DOCS_MARKDOWN_TARGETS) docs/gh-pages/book

.ONESHELL:
dist/cm4all-wp-impex-gh-pages: docs/gh-pages/book/
> rsync -rupE $< $@/

$(DOCS_MARKDOWN_TARGETS): $(DOCS_MARKDOWN_FILES)
# --no-stdin option prevents hanging here when called from a github action (see https://github.com/marp-team/marp-cli/pull/94)
> marp $< --no-stdin --allow-local-files --theme docs/theme/marp-theme-we22.css -o $@ 

$(DOCS_MERMAID_TARGETS) : $(DOCS_MERMAID_FILES)
> mkdir -p $(dir $@) && mmdc -i $< -o $@

.PHONY: clean
.ONESHELL:
#HELP: * clean up wp-env and intermediate files
clean:
> if [ -d "$$WP_ENV_HOME" ] && [ -d "node_modules/" ]; then
>   for instance_prefix in '' 'tests-' ; do
>     WORDPRESS_CONTAINER="$${instance_prefix}wordpress" 
>
>     wp-env run $$WORDPRESS_CONTAINER 'bash -c "chmod -R a+w /var/www/html/wp-content/{themes,plugins,upgrade,uploads} || true"'
>   done
# cleanup wp-env environment (docker images/containers and stuff)   
>   command -v wp-env 2>/dev/null 1>&2 && echo 'y' | wp-env destroy -f || true
> fi
# remove everything matching .gitignore entries (-f is force, you can add -q to suppress command output, exclude node_modules and node_modules/**)
# -ff is for plugins/cm4all-wp-impex/vendor/anthonykgross/dependency-resolver which is a git repository: 
#   => If an untracked directory is managed by a different git repository, it is not removed by default. Use -f option twice if you really want to remove such a directory.
> git clean -Xffd -e '!/Makefile-wp-env.postinit.sh' -e '!/Makefile-wp-env.preinit.sh' -e '!/*.code-workspace' -e '!/node_modules/' -e '!/node_modules/**' -e '!/.wp-env.override.json' -e '!/cm4all-wp-impex.code-workspace'

# delete all files in the current directory (or created by this makefile) that are created by configuring or building the program.
# see https://www.gnu.org/software/make/manual/html_node/Standard-Targets.html 
.PHONY: distclean
#HELP: cleanup node_modules, package-lock.json and docker container/images
distclean: clean
> rm -rf node_modules/ package-lock.json
# remove locally patched phpunit image
> docker image rm $$(docker images -q $(DOCKER_PHPUNIT_IMAGE)) 2>/dev/null || true
> docker image rm $$(docker images -q $(DOCKER_MDBOOK_IMAGE)) 2>/dev/null || true

# not in use 
# .PHONY: lint-php 
# lint-php: plugins/cm4all-wp-impex/vendor/autoload.php ## lint php sources
#   phpcs --runtime-set ignore_warnings_on_exit 1  

# not in use 
# .PHONY: lint-php 
# lint: lint-php ## lint sources
  
# not in use
# .PHONY: lint-fix-php 
# lint-fix-php: plugins/cm4all-wp-impex/vendor/autoload.php ## fix linter problems in php sources 
#   phpcbf --report-summary --report-source

# not in use
# .PHONY: lint-fix 
# lint-fix: lint-fix-php ## fix linter problems in sources

.PHONY: test-phpunit
#HELP: execute phpunit tests\n Example: run filtered tests\n make test-phpunit ARGS='--verbose --filter=TestImpexExportAdapterDb'\n Example : run filtered tests with phpunit debug information\n make test-phpunit ARGS='--debug --filter=TestImpexExportAdapterDb::test_wordpress_and_plugin_are_loaded'
test-phpunit: node_modules $(WP_ENV_HOME) plugins/cm4all-wp-impex/vendor/autoload.php
# test if local phphunit image already has xdebug injected
ifneq ($(shell docker image inspect --format='' $(DOCKER_PHPUNIT_IMAGE) 2> /dev/null | jq '.[0].Config.Labels.xdebug_enabled'),"true") 
> $(info inject xdebug support into $(DOCKER_PHPUNIT_IMAGE))
> docker build plugins/cm4all-wp-impex/tests/phpunit -t $(DOCKER_PHPUNIT_IMAGE)
endif  
# for whatever reason we need to force php to load xdebug by commandline
# (actually xdebug should be configured by `docker-php-ext-enable xdebug` in the Dockerfile but it is'nt ...)
> wp-env --debug run phpunit 'php -dzend_extension=xdebug.so /var/www/html/wp-content/plugins/cm4all-wp-impex/vendor/bin/phpunit -c /var/www/html/wp-content/plugins/cm4all-wp-impex/tests/phpunit/phpunit.xml $(ARGS)'

.PHONY: test-esbuild
#HELP: test run esbuild-bundler
test-esbuild: node_modules
> esbuild-bundle.mjs --debug --global-name='example.versions["1.0"]' tests/esbuild-bundle/screen.mjs tests/esbuild-bundle/dist/screen.js

.PHONY: test
#HELP: * run all tests
test: test-esbuild test-phpunit
  
.PHONY: dev-marp
#HELP: * watch/rebuild marp slides on change
dev-marp: node_modules  
> PORT=5000 marp --allow-local-files --theme docs/theme/marp-theme-we22.css -s docs

.PHONY: $(DOCKER_MDBOOK_IMAGE)
$(DOCKER_MDBOOK_IMAGE): 
# test if local mdbook image already build
ifneq ($(shell docker image inspect --format='' $(DOCKER_MDBOOK_IMAGE) 2> /dev/null | jq '.[0].Config.Labels.impex_customized'),"true") 
> $(info inject mdbook image customization into $(DOCKER_MDBOOK_IMAGE))
> docker build docs/gh-pages -t $(DOCKER_MDBOOK_IMAGE)
# mermaid install returns exitcode != 0 also for warnings which aborts the make process within github actions
> $$(docker run --rm -v $$(pwd)/docs/gh-pages:/data -u $$(id -u):$$(id -g) -it $(DOCKER_MDBOOK_IMAGE) mdbook-mermaid install) || true
endif 

.PHONY: dev-gh-pages
#HELP: * watch/rebuild gh-pages on change
dev-gh-pages: $(DOCKER_MDBOOK_IMAGE)
> docker run --rm -p 3000:3000 -p 3001:3001 -v $$(pwd)/docs/gh-pages:/data -u $$(id -u):$$(id -g) -it $(DOCKER_MDBOOK_IMAGE) mdbook serve -p 3000 -n 0.0.0.0

.PHONY: dev-js
#HELP: * rebuild js/css sources on change
dev-js: node_modules
> find plugins/cm4all-wp-impex/src/ -type f | entr -c /usr/bin/env bash -c "touch -m plugins/cm4all-wp-impex/src/wp.impex.*.{mjs,scss} && make build && dev-refresh-browser.sh"

.PHONY: wp-env-mysql-dump
#HELP: export (diffable) dump from wp-env DB container to "tmp/wp-env-wordpress.sql"\n Example: Dump to a different file\n make wp-env-mysql-export file=tmp/foo.sql"
wp-env-mysql-dump: $(WP_ENV_HOME) ## create a mysql dump (default "tmp/wp-env-wordpress.sql") usable for diff. 
> $(eval file ?= tmp/wp-env-wordpress-dump.sql)
> docker-compose -f $(WP_ENV_HOME)/*/docker-compose.yml exec -T mysql \
>   sh -c 'mysqldump --compact --skip-comments --skip-extended-insert --password="$$MYSQL_ROOT_PASSWORD" $$MYSQL_DATABASE' \
> > "$(file)"

.PHONY: wp-env-mysql-export
#HELP: export dump from wp-env DB container to "tmp/wp-env-wordpress.sql" \n Example: export only a few tables\n make wp-env-mysql-export ARGS='wp_cmplz_cookiebanners wp_cmplz_cookies wp_cmplz_services'\n Example: export to a different file \n make wp-env-mysql-export file=tmp/foo.sql
wp-env-mysql-export: $(WP_ENV_HOME) ## export a mysql dump (default dump file "tmp/wp-env-wordpress.sql") from wordpress db.
> $(eval file ?= tmp/wp-env-wordpress.sql)
> docker-compose -f $(WP_ENV_HOME)/*/docker-compose.yml exec -T mysql \
>   sh -c 'mysqldump --password="$$MYSQL_ROOT_PASSWORD" $$MYSQL_DATABASE $(ARGS)' \
> > "$(file)"

.PHONY: wp-env-mysql-import
#HELP: import dump "tmp/wp-env-wordpress.sql" in wp-env DB container\n Example: import dump from "tmp/foo.sql"\n make wp-env-mysql-import file=tmp/foo.sql"
wp-env-mysql-import: $(WP_ENV_HOME) 
> $(eval file ?= tmp/wp-env-wordpress.sql)
> docker-compose -f $(WP_ENV_HOME)/*/docker-compose.yml exec -T mysql \
>   sh -c 'mysql --password="$$MYSQL_ROOT_PASSWORD" $$MYSQL_DATABASE' \
> < "$(file)"

.PHONY: wp-env-wp-cli
#HELP: execute command in wp-env cli container \n Example: List installed plugins\n make wp-env-wp-cli ARGS='plugin list'
wp-env-wp-cli: $(WP_ENV_HOME) ## execute wp-cli command in wp-env
> wp-env run cli 'wp $(ARGS)'

.PHONY: wp-env-wp-cli-sh
#HELP: open shell in wp-env cli container \n Example: Delete all pages/posts (bypassing trash)\n make wp-env-wp-cli-sh <<< "wp post list --field=ID --post_type=page,post,attachment | xargs wp post delete --force"
wp-env-wp-cli-sh: $(WP_ENV_HOME)
> wp-env run cli 'sh'

# see https://developer.wordpress.org/block-editor/how-to-guides/internationalization/#create-translation-file
$(I18N_DIRECTORY)/$(IMPEX_PLUGIN_NAME).pot: $(WP_ENV_HOME) $(SCRIPT_TARGETS) $(PHP_SOURCES) 
> mkdir -p $(I18N_DIRECTORY) 
# npm run wp-env run cli is called with user www-data, therefore ensure write access
> chmod go+w $(I18N_DIRECTORY) 
# > [ -f "$(I18N_DIRECTORY)/$(IMPEX_PLUGIN_NAME).pot" ] && chmod go+w $(I18N_DIRECTORY)/$(IMPEX_PLUGIN_NAME).pot
> wp-env run cli \
>   '$(SHELL) -c "cd ./wp-content/plugins/$(IMPEX_PLUGIN_NAME) && wp i18n make-pot --debug --exclude=tests/,*-min.js,vendor/ ./ languages/$(IMPEX_PLUGIN_NAME).pot && chmod go+w languages/$(IMPEX_PLUGIN_NAME).pot"'

$(I18N_DIRECTORY)/$(IMPEX_PLUGIN_NAME)-%.po : $(I18N_DIRECTORY)/$(IMPEX_PLUGIN_NAME).pot
# create variable at execution time (see http(s:)//stackoverflow.com/questions/1909188/define-make-variable-at-rule-execution-time))
> $(eval $@_LOCALE := $(shell echo $@ | grep -Po '(\w+)(?=\.po$$)'))
# > $(info $($@_LOCALE))
# create po file or update po file if it already exists
> [ -f "$@" ] && msgmerge --backup=off -U $@ $< ||  msginit -i $< -l $($@_LOCALE) --no-translator -o $@

$(I18N_DIRECTORY)/$(IMPEX_PLUGIN_NAME)-%.mo : $(I18N_DIRECTORY)/$(IMPEX_PLUGIN_NAME)-%.po
> msgfmt -o $@ $<
# create json translations for js localization
# be aware that json files are only created if translations for keys from js files are available
> wp-env run cli 'wp i18n make-json ./wp-content/plugins/$(IMPEX_PLUGIN_NAME)/languages/$$(basename $<) ./wp-content/plugins/$(IMPEX_PLUGIN_NAME)/languages --no-purge --pretty-print'

.PHONY: i18n
#HELP: compile i18n resources
i18n: $(MO_TARGETS) # $(I18N_DIRECTORY)/$(IMPEX_PLUGIN_NAME)-de_DE.mo

.PHONY: wp-env-mysql-shell
#HELP: open shell in wp-env DB container
wp-env-mysql-shell: $(WP_ENV_HOME) 
> docker-compose -f $(WP_ENV_HOME)/*/docker-compose.yml exec mysql sh -c 'mysql --password="$$MYSQL_ROOT_PASSWORD" "$$MYSQL_DATABASE"'

.PHONY: wp-env-tests-mysql-shell
#HELP: open shell in wp-env tests DB container
wp-env-tests-mysql-shell: $(WP_ENV_HOME) ## open mysql shell connected to wp-env database container in terminal
> docker-compose -f $(WP_ENV_HOME)/*/docker-compose.yml exec tests-mysql sh -c 'mysql --password="$$MYSQL_ROOT_PASSWORD" "$$MYSQL_DATABASE"'

.PHONY: dist
#HELP: * produce release artifacts
dist: i18n dist/docs dist/cm4all-wp-impex.zip dist/cm4all-wp-impex-example.zip dist/cm4all-wp-impex-gh-pages.zip dist/cm4all-wp-impex-php7.4.0.zip
> @touch -m '$@'

dist/cm4all-wp-impex-php7.4.0: dist/cm4all-wp-impex tmp/composer.phar
#HELP: * generate PHP 7.4 flavor of impex plugin
> mkdir -p '$@'
> rsync -rc dist/cm4all-wp-impex/ $@/
# rename dummy plugin to cm4all-wp-impex-php7.4.0
> sed -i 's/Plugin Name: cm4all-wp-impex/Plugin Name: cm4all-wp-impex-php7.4.0/g' $@/plugin.php
> sed -i 's/Requires PHP: 8.0/Requires PHP: 7.4/' $@/plugin.php
> mkdir -p 'tmp/rector'
> (cd 'tmp/rector' && php ../composer.phar require rector/rector --dev)
# > tmp/rector/vendor/bin/rector --clear-cache --working-dir ./dist/cm4all-wp-impex-php7.4.0 --config ./tmp/rector/vendor/rector/rector/config/set/downgrade-php80.php --no-progress-bar process .
> tmp/rector/vendor/bin/rector --clear-cache --working-dir ./dist/cm4all-wp-impex-php7.4.0 --config ./rector.php --no-progress-bar process .

.PHONY: deploy-to-wordpress
#HELP: * package and deploy impex plugin to wordpress.org
deploy-to-wordpress: # dist # dist/cm4all-wp-impex-php7.4.0
# see https://github.com/10up/action-wordpress-plugin-deploy/blob/develop/deploy.sh
ifndef SVN_TAG
>	  $(error "$(@) : SVN_TAG is not set")
endif
ifndef SVN_USERNAME
>	  $(error "$(@) : SVN_USERNAME is not set")
endif
ifndef SVN_PASSWORD
>	  $(error "$(@) : SVN_PASSWORD is not set")
endif
ifeq ($(wildcard dist/cm4all-wp-impex-php7.4.0), )
>	  $(error "$(@) : directory dist/cm4all-wp-impex-php7.4.0 doesnt not yet exist. Please run 'make dist' first.")
endif
# next steps requires apt packages ["subversion","librsvg2-bin","imagemagick" (=>convert) to be installed
# checkout just trunk and assets for efficiency
# tagging will be handled on the svn level
> (cd dist && svn checkout --depth immediates https://plugins.svn.wordpress.org/cm4all-wp-impex wordpress.org-svn)
> (cd dist/wordpress.org-svn && svn update --set-depth infinity assets && svn update --set-depth infinity trunk)
> rsync -rc docs/wordpress.org-svn/ dist/wordpress.org-svn/assets/ --delete
# create wordpress png icons from svg
> (cd dist/wordpress.org-svn/assets && convert -density 300 -define icon:auto-resize=128 -background none icon.svg icon-128x128.png)
> (cd dist/wordpress.org-svn/assets && convert -density 300 -define icon:auto-resize=256 -background none icon.svg icon-256x256.png)
# copy plugin to svn
> rsync -rc dist/cm4all-wp-impex-php7.4.0/ dist/wordpress.org-svn/trunk/ --delete
# add files to svn
> (cd dist/wordpress.org-svn && svn add . --force)
# remove deleted files from svn
> (cd dist/wordpress.org-svn && svn status | grep '^\!' | sed 's/! *//' | xargs -I% svn rm %@ > /dev/null) || true
# copy tag locally to make this a single commit
> (cd dist/wordpress.org-svn && svn cp "trunk" "tags/$(SVN_TAG)")
# fix screenshots getting force downloaded when clicking them
> (cd dist/wordpress.org-svn/assets && find . -maxdepth 1 -name '*.png' -exec svn propset svn:mime-type 'image/png' *.png \; -quit)
> (cd dist/wordpress.org-svn/assets && find . -maxdepth 1 -name '*.jpg' -exec svn propset svn:mime-type 'image/jpeg' *.jpg \; -quit)
> (cd dist/wordpress.org-svn/assets && find . -maxdepth 1 -name '*.ico' -exec svn propset svn:mime-type 'image/x-icon' *.ico \; -quit)
> (cd dist/wordpress.org-svn/assets && find . -maxdepth 1 -name '*.gif' -exec svn propset svn:mime-type 'image/gif' *.gif \; -quit)
> (cd dist/wordpress.org-svn && svn status)
> (cd dist/wordpress.org-svn/assets svn commit -m "Update to version $(SVN_TAG) from GitHub" --no-auth-cache --non-interactive --username "$SVN_USERNAME" --password "$SVN_PASSWORD"
> echo "$@ tagged svn to $(SVN_TAG)"

.ONESHELL :
dist/cm4all-wp-impex: build
# optimization : create build/[plugin-name] directory and languages subdir at once
> mkdir -p '$@/languages'
> rsync -rupE plugins/cm4all-wp-impex/{plugin.php,inc,dist,profiles} $@/
> rsync -rupE plugins/cm4all-wp-impex/languages/{*.mo,*.json} $@/languages/
# 
> export CONTRIBUTORS="cm4all" # comma separated list of contributors
> export CHANGELOG=$$(sed 's/^### \(.*\)/*\1*/g;s/^## \(.*\)/= \1 =/g;' CHANGELOG.md)
# > export TAGS="import,export,migration" # provide default tags if not defined in plugin.php
# > scan plugin.php for wordpress plugin settings
> metadata=$$(grep -oP '^ \* \K[^:]+: [^$$]+$$' $@/plugin.php)
> while IFS=":" read -r key value
> do
>   # trim spaces and replace spaces with _
>   name="$$(echo $${key} | tr [:lower:] [:upper:] | tr -s ' ' '_')"
>   # trim spaces
>   value="$$(echo $$value)"
>   # declare and export as bash variable
>   declare -x $$name="$$value"
>   echo "exported $$name='$$value'"
> done <<< "$$metadata"
> envsubst < plugins/cm4all-wp-impex/readme.txt.template > $@/readme.txt
> touch -m $@

.ONESHELL:
dist/cm4all-wp-impex-example: build
> mkdir -p '$@'
> rsync -rupE plugins/cm4all-wp-impex-example/{plugin.php,inc} $@/

# create cm4all-wp-impex-[version].zip file
dist/%.zip: dist/% 
# see https://chriswiegman.com/2021/08/three-uses-for-make-in-wordpress-development/
> PLUGIN_VERSION=$$(grep '^ \* Version:' plugins/cm4all-wp-impex/plugin.php | awk -F' ' '{print $$3}' | sed 's/ //g')
> ARCHIVE_NAME="$$(basename $<)-v$${PLUGIN_VERSION}.zip"
> cd $< && zip -qq -r -o ../$$ARCHIVE_NAME *

# see https://gist.github.com/Olshansk/689fc2dee28a44397c6e31a0776ede30
.PHONY: help
.ONESHELL:
#HELP: * prints this screen
help: 
> @printf "Available targets\n\n"
> @awk '/^[a-zA-Z\-_0-9]+:/ { 
>   helpMessage = match(lastLine, /^#HELP: (.*)/); 
>   if (helpMessage) { 
>     helpCommand = substr($$1, 0, index($$1, ":")-1); 
>     helpMessage = substr(lastLine, RSTART + 6, RLENGTH); 
>     gsub(/\\n/, "\n", helpMessage);
>     printf "\033[36m%-30s\033[0m %s\n", helpCommand, helpMessage;
>   } 
> } 
> { lastLine = $$0 }' $(MAKEFILE_LIST)

# you can dry run semantic-release on any branch using 'npx semantic-release --no-ci --dry-run --branches [branch]'
.PHONY: test-release
#HELP: dry-run semantic release outside ci (requires providing your Github token in environment)
test-release:
> npx semantic-release --no-ci --dry-run --branches develop
