/*
  provides functions related to transforming html to WordPress Gutenberg [block-annotated HTML](https://wordpress.com/support/wordpress-editor/blocks/)
*/

import { readFile } from "fs/promises";
import cloneDeepWith from "lodash.clonedeepwith";
import "global-jsdom/register";

import "polyfill-library/polyfills/__dist/matchMedia/raw.js";
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

import {
  registerCoreBlocks,
  __experimentalGetCoreBlocks,
} from "@wordpress/block-library";
import { rawHandler, serialize } from "@wordpress/blocks";

import { unregisterBlockType, getBlockTypes } from "@wordpress/blocks";
import { removeAllFilters } from "@wordpress/hooks";

/**
 * Traverses the given blocks including its children recursively
 *
 * @param {Block[]}
 * @generator
 * @yields {Block}
 */
export function* traverseBlocks(blocks) {
  for (const block of blocks) {
    yield block;
    if (Array.isArray(block.innerBlocks)) {
      yield* traverseBlocks(block.innerBlocks);
    }
  }
}

function noop(arg) {
  return arg;
}

let verbose, onLoad, onDomReady, onRegisterCoreBlocks, onSerialize;
// @TODO: preserve transforms section of blocks between setup() calls
const coreBlocks = __experimentalGetCoreBlocks();
const originalBlockTransforms = coreBlocks
  // array contains null values for some reason 🤷‍♀️
  .filter(Boolean)
  .map((block) => ({
    name: block.name,
    settings: { transforms: block.settings.transforms },
  }));

// ImpexTransformer is a singleton since most Gutenberg API functions assume only one Gutenberg instance
const ImpexTransformer = {
  /**
   * reconfigures the ImpexTransformer
   * setup is required to be called multiple times for testing purposes
   *
   * @param   {object}  configuration a object hooks and properties to configure the instance
   *                                  - hooks:
   *                                    - onLoad(string : html) : string
   *                                      may be used to mutate the html before it gets parsed
   *                                    - onDomReady(Document : document) : void
   *                                      may be used to mutate the html after it was parsed be mutating the document parameter
   *                                    - onRegisterCoreBlocks() : boolean
   *                                      may be used to register gutenberg blocks or modify block specs using filter "blocks.registerBlockType"
   *                                    - onSerialize(Block[] : blocks) : Block[]
   *                                      may be used to mutate the gutenberg blocks before they get serialized to
   *                                      [block-annotated HTML](https://wordpress.com/support/wordpress-editor/blocks/)
   *                                   - properties:
   *                                    - verbose: boolean
   *                                      (default: false) if true, the instance will print out the steps it takes to transform the html
   */
  setup(configuration = {}) {
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
          (coreBlock) => coreBlock?.name === originalBlockTransform?.name
        ).settings.transforms = cloneDeepWith(
          originalBlockTransform.settings.transforms,
          (value, indexOrKey, stack) => {
            if (indexOrKey === "schema") {
              return value;
            }
          }
        );
      });
      registerCoreBlocks(coreBlocks);
    }
  },
  /**
   * transforms html input to WordPress Gutenberg [block-annotated HTML](https://wordpress.com/support/wordpress-editor/blocks/)
   *
   * @param   {string}  html     html page (including <html><head>...</head><body>...</body></html>)
   * @param   {object}  options  options influencing the transformation process
   *
   * @return  {string}           the html page content transformed to WordPress Gutenberg [block-annotated HTML](https://wordpress.com/support/wordpress-editor/blocks/)
   */
  transform(html, options = {}) {
    verbose && console.log("\ntransform(html):\n%s\n", html);

    const _html = onLoad(html);
    if (typeof _html !== "string") {
      throw new Error("onLoad hook must return a string");
    }

    document.documentElement.innerHTML = _html;
    verbose &&
      console.log("\nonLoad:\n%s\n", document.documentElement.outerHTML);

    onDomReady(document);
    verbose &&
      console.log("\nonDomReady:\n%s\n", document.documentElement.outerHTML);

    const content = global.document.querySelector("body").innerHTML;
    const blocks = rawHandler({
      HTML: content,
    });

    const _blocks = onSerialize(blocks);
    if (!Array.isArray(_blocks)) {
      throw new Error("onSerialize hook must return an array");
    }
    verbose && console.log("\nonSerialize:\n%O\n", blocks);

    const serialized = serialize(_blocks);
    verbose && console.log("\nserialized:\n%s\n", serialized);

    // document.documentElement.innerHTML = "";

    return serialized;
  },
};
export default ImpexTransformer;

ImpexTransformer.setup();
