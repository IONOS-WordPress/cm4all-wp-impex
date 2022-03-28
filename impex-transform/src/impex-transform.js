#!/usr/bin/env node

import { fileURLToPath } from "url";
import process from "process";
import yargs from "yargs";
import { hideBin } from "yargs/helpers";
import { readFile } from "fs/promises";
import "jsdom-global";

// import polyfillLibrary from "polyfill-library";

const package_json = JSON.parse(
  await readFile(new URL("./../package.json", import.meta.url))
);

if (fileURLToPath(import.meta.url) === process.argv[1]) {
  console.log("running standalone");

  // const args = yargs(hideBin(process.argv)).argv;
  // console.log(args);

  const args = yargs(hideBin(process.argv))
    .help()
    .version(package_json.version)
    .example([
      [
        "$0 transform --input=foo.html --output=foo.txt --input-format=html --output-format=raw",
        "Transforms foo.html into Gutenberg post content saved to foo.txt",
      ],
      [
        "$0 transform --input=stdin --output=stdout --input-format=html --output-format=raw",
        "Transforms input taken from stdin into Gutenberg post content piped to stdout",
      ],
    ])
    .showHelpOnFail(true)
    .epilog("Copyright 2022 CM4all GmbH")
    .option("verbose", {
      alias: "v",
      type: "boolean",
      default: false,
      description: "enable verbose output",
    })
    .command({
      command: ["transform", "$0"], // default command
      description: "transform input file to output file",
      builder: {
        "input-format": {
          type: "string",
          default: "html",
          description: "input format",
          choices: ["html"],
        },
        "output-format": {
          type: "string",
          default: "raw",
          description: "output format",
          choices: ["raw"],
        },
        input: {
          type: "string",
          required: true,
          description: "input file",
        },
      },
      // Function for your command
      async handler(args) {
        const html = await readFile(args.input, "utf8");
        console.log(html);

        global.CSS = {
          escape(ident) {
            return "";
          },
          supports(property, value) {
            return true;
          },
          supports(conditionText) {
            return true;
          },
        };

        console.log(global.document);

        //global.self = document;

        /**
         * Load polyfills required for WordPress Blocks loading
         */
        // await import("polyfill-library/polyfills/__dist/matchMedia/raw.js");
        //import "polyfill-library/polyfills/__dist/requestAnimationFrame/raw.js";

        /**
         * WordPress Blocks dependencies
         */
        // const { registerCoreBlocks } = require('@wordpress/block-library')
        // const { rawHandler, serialize } = require('@wordpress/blocks')
        // registerCoreBlocks();
      },
    })
    .demandCommand(1)
    .parse();

  // console.log({ args });
} else {
  console.log("running embedded");
}

const ImpexTransform = {
  foo: "bar",
};

export default ImpexTransform;
