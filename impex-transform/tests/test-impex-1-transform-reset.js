import test, { includes, doesNotInclude } from "./tape-configuration.js";
import transformer from "../src/impex-transform.js";

import { addFilter, applyFilters } from "@wordpress/hooks";
import {
  unregisterBlockType,
  getBlockTypes,
  getBlockAttributes,
  createBlock,
} from "@wordpress/blocks";

test("ensure impex-transform.setup(...) will provide a clean reset'ed block.settings.transforms", (t) => {
  transformer.setup({
    verbose: false,
    onRegisterCoreBlocks() {
      addFilter(
        "blocks.registerBlockType",
        "prepend-custom-image-transform",
        (blockType) => {
          if (blockType.name === "core/image") {
            const from = blockType.transforms.from[0];

            blockType.transforms.from.unshift({
              ...from,
              transform(node) {
                const block = from.transform(node);

                block.attributes.caption = "our-customized-caption";
                return block;
              },
            });
          }
          return blockType;
        }
      );
    },
  });

  const HTML = `<!DOCTYPE html>
  <body>
    <img src="./greysen-johnson-unsplash.jpg">
  </body>
  </html>`;

  let transformed = transformer.transform(HTML);
  includes(t, transformed, "<figcaption>our-customized-caption</figcaption>");

  transformer.setup({
    verbose: false,
  });

  transformed = transformer.transform(HTML);
  doesNotInclude(
    t,
    transformed,
    "<figcaption>our-customized-caption</figcaption>"
  );

  t.end();
});
