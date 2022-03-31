#!/usr/bin/env node

import { fileURLToPath } from "url";
import process from "process";
import yargs from "yargs";
import { hideBin } from "yargs/helpers";
import { readFile } from "fs/promises";
import "global-jsdom/register";

// import React from "react";
import "polyfill-library/polyfills/__dist/matchMedia/raw.js";
import {
  registerCoreBlocks,
  __experimentalGetCoreBlocks,
} from "@wordpress/block-library";
import { rawHandler, serialize } from "@wordpress/blocks";

import { unregisterBlockType, getBlockTypes } from "@wordpress/blocks";
import { removeAllFilters } from "@wordpress/hooks";

const package_json = JSON.parse(
  await readFile(new URL("./../package.json", import.meta.url))
);

function noop(arg) {
  return arg;
}

function ImpexTransformFactory(configuration) {
  let verbose, onLoad, onDomReady, onRegisterCoreBlocks, onSerialize;

  // @TODO: preserve transforms ssection of blocks between setup() calls
  const coreBlocks = __experimentalGetCoreBlocks();

  const originalBlockTransforms = coreBlocks.filter(Boolean).map((block) => ({
    name: block.name,
    settings: { transforms: block.settings.transforms },
  }));

  const setup = (configuration = {}) => {
    verbose = configuration?.verbose ?? false;
    onLoad = configuration?.onLoad ?? noop;
    onDomReady = configuration?.onDomReady ?? noop;
    onRegisterCoreBlocks = configuration?.onRegisterCoreBlocks ?? noop;
    onSerialize = configuration?.onSerialize ?? noop;

    // reset (possibly mutated) core blocks after each test
    removeAllFilters("blocks.registerBlockType");
    for (const blockType of getBlockTypes()) {
      unregisterBlockType(blockType.name);
    }

    if (onRegisterCoreBlocks() !== false) {
      // reset possibly mutated transforms
      originalBlockTransforms.forEach((originalBlockTransform) => {
        coreBlocks.find(
          (coreBlock) => coreBlock.name === originalBlockTransform.name
        ).settings.transforms = structuredClone(
          originalBlockTransform.settings.transforms
        );
      });
      registerCoreBlocks(coreBlocks);
    }
  };

  // console.log(configuration);

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

  setup(configuration);

  return {
    setup,
    transform(html, options = {}) {
      verbose && console.log(html);

      document.documentElement.innerHTML = onLoad(html);

      const content = global.document.querySelector("body").innerHTML;

      onDomReady(document);

      verbose && console.log(content);

      const blocks = rawHandler({
        HTML: content,
      });

      const serialized = serialize(onSerialize(blocks));
      verbose && console.log(serialized);

      document.documentElement.innerHTML = "";

      return serialized;
    },
    VERSION: package_json.version,
    cleanup() {
      window.close();
    },
  };
}

registerCoreBlocks();

if (fileURLToPath(import.meta.url) === process.argv[1]) {
  //console.log("running standalone");

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

        const impexTransform = ImpexTransformFactory(args);
        impexTransform.transform(html, args);

        impexTransform.cleanup();
        process.exit(0);
      },
    })
    .demandCommand(1)
    .parse();

  // console.log({ args });
} else {
  //console.log("running embedded");
}

export default ImpexTransformFactory;
