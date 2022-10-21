The purpose of this image is documentation generation out of markdown files using [mdbook](https://rust-lang.github.io/mdBook/) : 

> [mdbook](https://rust-lang.github.io/mdBook/) is a command line tool to create books with Markdown. It is ideal for creating product or API documentation, tutorials, course materials or anything that requires a clean, easily navigable and customizable presentation.
> - Lightweight Markdown syntax helps you focus more on your content
> - Integrated search support
> - Color syntax highlighting for code blocks for many different languages
> - Theme files allow customizing the formatting of the output
> - Preprocessors can provide extensions for custom syntax and modifying content
> - Backends can render the output to multiple formats

It provides [mdbook](https://rust-lang.github.io/mdBook/) including 

  - [mdbook-toc](https://github.com/badboy/mdbook-toc) 
  
    for supporting table of contents generation in pages

  - [mdbook-mermaid](https://github.com/badboy/mdbook-mermaid) 
  
    adding support for diagrams using [mermaid](https://mermaid-js.github.io/mermaid/#/)

plugins.

# Usage

## Initialization

- Open a terminal and change into the directory where you want to work on your documentation

- Generate configuration and boilerplate content : 

  `docker run --rm -it --mount type=bind,source=$(pwd),target=/data -u $(id -u):$(id -g) lgersman/cm4all-wp-impex-mdbook init`

  The image will create a minimal preconfigured documentation layout

  ```
  ├── book                    
  ├── book.toml             
  ├── mermaid-init.js
  ├── mermaid.min.js
  └── src
      ├── chapter_1.md
      └── SUMMARY.md
  ``` 

  - `book/`

    The output directory for the generated html 

  - `book.toml`
  
    the mdbook configuration (see https://rust-lang.github.io/mdBook/format/configuration/index.html)

  - `mermaid*.js`  

    the [mdbook-mermaid](https://github.com/badboy/mdbook-mermaid) resources injected into the generated documentation

  - `src/`  
  
    the directory expected to contain markdown files to render to html.

    _(In case of an existing `./src` folder it will not being overwritten)_

    - `src/SUMMARY.md` 

    The summary file is used by mdBook to know what chapters to include, in what order they should appear, what their hierarchy is and where the source files are. Without this file, there is no book.

    See https://rust-lang.github.io/mdBook/format/summary.html

- Now you can start editing your documentation 

## Syntax

All commands are executes within the docker container. 

Syntax : `docker run --rm -it <options> --mount type=bind,source=<documentation-directory>,target=/data -u $(id -u):$(id -g) lgersman/cm4all-wp-impex-mdbook <command>`

  - `<documentation-directory>` 
    
    (required) must be replaced by the documentation directory.

    In case it is your working directory you can set it to `$(pwd)`

  - `<command>` 

    (required) must be replace by the actual command

  - `<options>` 

    (optional) here you can set some docker options required by a few commands like `serve`
### Commands

- `mdbook init` 

  When using the init command for the first time, a couple of files will be set up for you. 

  See see https://rust-lang.github.io/mdBook/cli/init.html for further options

- `mdbook build`

  Generates the html documentation in the `book/` folder

  See https://rust-lang.github.io/mdBook/cli/build.html for further options

- `mdbook serve`

  The serve command is used to preview a book by serving it via HTTP at `http://localhost:3000` by default

  When using `mdbook serve` you need to apply a few additional `docker` options in the commandline : 

  `docker run --rm -it -p 3000:3000 -p 3001:3001  --mount type=bind,source=$(pwd),target=/data -u $(id -u):$(id -g) lgersman/cm4all-wp-impex-mdbook mdbook serve -n 0.0.0.0`

  Now you can open the documentation in your browser at http://0.0.0.0:3000

  Whenever you change/save a markdown file (or any other resource used by [mdbook](https://rust-lang.github.io/mdBook/) the generated documentation will be updated and reloaded in the browser.

  See https://rust-lang.github.io/mdBook/cli/serve.html for further options

## Links

See https://rust-lang.github.io/mdBook/guide/reading.html for further commands and options of [mdbook](https://rust-lang.github.io/mdBook/)
# Showcases

- An example website created with this image is https://ionos-wordpress.github.io/cm4all-wp-impex/

  The sources including [mdbook](https://rust-lang.github.io/mdBook/) customization can be found here : https://github.com/IONOS-WordPress/cm4all-wp-impex/tree/develop/docs/gh-pages
