#!/usr/bin/env node

/*
 * example usage: ./src/impex-generator-cli.js transform --input ./examples/example.html
 */

import process from "process";
import yargs from "yargs";
import { hideBin } from "yargs/helpers";
import { readFile } from "fs/promises";

import { ImpexTransformer, ImpexSliceFactory } from "./index.js";

const package_json = JSON.parse(
  await readFile(new URL("./../package.json", import.meta.url))
);

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

      ImpexTransformer.setup({ verbose: args.verbose });
      console.log(ImpexTransformer.transform(html, args));

      window.close();
      process.exit(0);
    },
  })
  .demandCommand(1)
  .parse();
