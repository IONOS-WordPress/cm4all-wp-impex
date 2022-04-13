# Usage

## View the static homepage "as is" in the browser

- start a basic webserver : `php -S localhost:8080 -t homepage-dr-mustermann/`

- and point your browser to `http://localhost:8000/`

## Convert the static homepage to a dynamic one

- run the homepage to impex export transformation (requires predefined nodejs version : `nvm use`) : `./index.js`

- import generated content into wp-env :

  - (optional) cleanup local wp-env installation : `(cd `git rev-parse --show-toplevel` && make wp-env-clean)`

  - do the impex import : `$(git rev-parse --show-toplevel)/impex-cli/impex-cli.php import -username=admin -password=password -rest-url=http://localhost:8888/wp-json -profile=all ./generated-impex-export/`
