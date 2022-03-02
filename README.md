# General

**This project is under development ! Source code and API may change often.**

The official [cm4all-wp-impex Wordpress Plugin for PHP 7.4](https://wordpress.org/plugins/cm4all-wp-impex/) can be downloaded from the [Wordpress Plugin directory](https://wordpress.org/plugins/cm4all-wp-impex/).

If you prefer to use the PHP 8 Version of the Plugin, you can download it from the [GitHub project repository releases page](https://github.com/IONOS-WordPress/cm4all-wp-impex/releases).

## Dependencies

This plugin requires

- Wordpress Version : see `plugins/cm4all-wp-impex/plugin.php` header

- PHP Version : see `plugins/cm4all-wp-impex/plugin.php` header

# Documentation

Documentation can be found here : https://ionos-wordpress.github.io/cm4all-wp-impex/

# Development

_Please note that the development scripts are currently only tested in Linux. Chances are high that they work also on MacOs and using Windows WSL._

Afer checkout simply call `make` to

- build the project : `make`
- setup an wp-env instance (available at http://localhost:8888)

Execute `make help` to see all available commands (including description). Make targets markled with asterisk (`*`) are the
primary targets you may want to execute.

## JS/CSS assets

- start `make dev`

On every change to the JS/CSS files in the plugin the transpiled files are generated in the `dist` folder and the recent browser tab gets reloaded.

## gh_pages

gh_pages is done using [mdbook](https://github.com/rust-lang/mdBook).

- start `make dev-gh-pages`

On every change the book gets rebuild and immediately rerendered in your browser.

## Debugging

- Ensure the VSCode PHP Debug extension (https://marketplace.visualstudio.com/items?itemName=felixfbecker.php-debug) is installed

- Set some breakpoints.

### wp-env

- start the VS Code launch configuration (generated by `make`) containing `impex` in VS Code

- Execute `make test-phpunit`

### phpunit

- start the VS Code launch configuration (generated by `make`) containing `impex phpunit` in VS Code

### Prerequisites

- PHP needs to be installed on your local machine for installing composer updates

## Dependencies

- make (GNU Make > 4.0)
- nodejs (see .nvmrc)
- docker
- nvm (**optional**)
- `entr`, `xdotool` and `google-chrome` for `make dev` (reload chrome browser tab after rebuild)
- `msgmerge`, `msginit` and `msgfmt` for i18n resource building (usually contained in \*nix package `gettext`)
- `subversion`, `librsvg2-bin` and `imagemagick` for wordpress.org deployment

### Update nodejs dependencies

- [`npx taze`](https://github.com/antfu/taze)

## release

The release process is done by Github Actions when the `main` branch gets updated (`git push`)

To make a release :

- fetch remote changes including tags for branch main : `git fetch --tags origin main:main`

- merge `main` into `develop` (prevent creating a merge commit message) : `git rebase --no-ff main` or `git merge --no-commit --no-ff main`

- push develop to main to trigger release creation using ci : `git push origin develop:main`
