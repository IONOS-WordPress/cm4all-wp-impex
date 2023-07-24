# About

This is a full featured example of converting a regular static website of a fictional german dentist to a WordPress site.

The web site is available offline at directory `./homepage-dr-mustermann`.

You can view the website by

- starting the PHP built-in webserver : `php -S localhost:8080 -t homepage-dr-mustermann/`
- and open the website in your browser : `http://localhost:8080/`.

## [Watch the walk-trough on YouTube](https://img.youtube.com/vi/pjG69RmULYo/2.jpg)

[![Watch the video](https://img.youtube.com/vi/pjG69RmULYo/2.jpg)](https://www.youtube.com/watch?v=pjG69RmULYo)

_(German audio with english sub titles.)_

# Conversion process

The conversion process is implemented in a single file `./index.js` :

- scanning for html and media files from the filesystem using plain NodeJS

- converting the HTML files to [ImpEx slice JSON](https://ionos-wordpress.github.io/cm4all-wp-impex/migrating-content.html#content-aka-wordpress-postspages) using `ImpexTransformer` and `ImpexSliceFactory` from package [`@cm4all-wp-impex/generator`](https://www.npmjs.com/@cm4all-wp-impex/generator). The HTML transformation is customized in the `setup(...)` function.

- creating [ImpEx slice JSON](https://ionos-wordpress.github.io/cm4all-wp-impex/migrating-content.html#attachments-like-pictures-and-videos) for the media files using `ImpexSliceFactory` from package [`@cm4all-wp-impex/generator`](https://www.npmjs.com/@cm4all-wp-impex/generator)

- saving the [ImpEx slice JSON](https://ionos-wordpress.github.io/cm4all-wp-impex/migrating-content.html#data-files) to the filesystem using paths generated by `ImpexSliceFactory.PathGenerator` from package [`@cm4all-wp-impex/generator`](https://www.npmjs.com/@cm4all-wp-impex/generator)

  - the media files are saved to the filesystem using paths adapted from the `ImpexSliceFactory.PathGenerator` generated paths for the slice files (as expected by the [ImpEx Export format](https://ionos-wordpress.github.io/cm4all-wp-impex/migrating-content.html#attachments-like-pictures-and-videos)).

The conversion process is implemented in less than 240 lines of code thanks to package [`@cm4all-wp-impex/generator`](https://www.npmjs.com/@cm4all-wp-impex/generator).

You can run the conversion script by executing `./index.js` (can be found at the GitHub repository : [packages/@cm4all-wp-impex/generator/examples/impex-complete-static-homepage-conversion\index.js](https://github.com/IONOS-WordPress/cm4all-wp-impex/blob/develop/packages/%40cm4all-wp-impex/generator/examples/impex-complete-static-homepage-conversion/index.js)

> Ensure the right nodejs version is active before using `nvm install` and to install the required NodeJS dependencies using `npm ci`.

> Ensure that you've installed the script dependencies by entering directory `cm4all-wp-impex/packages/@cm4all-wp-impex/generator` and executing `npm ci`.

The result is a folder `generated-impex-import/` containing the generated [ImpEx export folder layout](https://ionos-wordpress.github.io/cm4all-wp-impex/migrating-content.html#preparation) containing the ImpEx slice JSON files and media files.

This export can now be imported into WordPress using [ImpEx CLI](https://ionos-wordpress.github.io/cm4all-wp-impex/impex-cli.html) :

```sh
impex-cli.php import -username=<adminusername> -password=<adminpassword> -rest-url=<your-wordpress-rest-api-endpoint> ./generated-impex-export/
```

_(Replace the `<placeholder>` with your own values.)_

> Ensure your WordPress instance is empty (does not contain any pages/posts/media).

After executing the command the website contents are imported into your WordPress instance.

---

The example website and conversion script is intentionally simple.

Since every website is different, the conversion process cannot be universal work for every website.

By implementing additional transformation rules using the hooks known by `Transformer.setup(...)` function of [`@cm4all-wp-impex/generator`](https://www.npmjs.com/@cm4all-wp-impex/generator) almost any detail of a website can be converted to a WordPress post/page.

# Whats missing ?

The example does not cover every detail of a website conversion, only the content. But that's intentional.

Possible improvements:

- The navigation bar could be converted to a custom WordPress nav_menu.

  Navigation is different handled in FSE and classic themes. In a FSE you would generate a [Navigation block](https://wordpress.org/support/article/navigation-block/), in a classic theme it works different. It depends on the target WordPress environment how to take over navigation.

- Styles are ignored in the example.

  Because it depends on the goal of the transformation. If the content should be styled completely by a WordPress theme providing the complete styling, this is not needed.

  But if needed, style properties like fonts and colors could be introspected and transformed to FSE theme.json settings.

- Contact form will be taken over as `core/html` block. Submitting the form does not work in the example.

  WordPress/Gutenberg does not provide a generic Form block. There is no option to convert the HTML form to something matching using plain WordPress / Gutenberg.

  But the form could be easily converted into a [Ninja Form](https://ninjaforms.com/) or any other form builder plugin available for WordPress.

  To keep the example simple and working without depending on additional plugins like [Ninja Forms](https://ninjaforms.com/) the example ist just converted to a `core/html` block.

  _So it depends on your target WordPress environment (and available plugins) how the conversion will be implemented._

- The overall layout (header/footer/main section) is also ignored (but could be converted to [FSE part templates](https://developer.wordpress.org/themes/block-themes/templates-and-template-parts/)).

  But : as you might guess - all these improvements may vary depending on the goal.

> The important message is : **Everything is possible, but because it's individual - it's up to you 💪**

# Local Development using cm4all-wp-impex

- (optional) cleanup local wp-env installation : `(cd $(git rev-parse --show-toplevel) && make wp-env-clean)`

- import using ImpEx cli : `$(git rev-parse --show-toplevel)/impex-cli/impex-cli.php import -username=admin -password=password -rest-url=http://localhost:8888/wp-json -profile=all ./generated-impex-export/`