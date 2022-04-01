import test from "./tape-wrapper.js";

import { addFilter, applyFilters } from "@wordpress/hooks";
import {
  unregisterBlockType,
  getBlockTypes,
  getBlockAttributes,
  createBlock,
} from "@wordpress/blocks";

test("ensure impex-transform.setup(...) will provide a clean reset'ed block.settings.transforms", (t, impexTransform) => {
  impexTransform.setup({
    verbose: true,
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

  let transformed = impexTransform.transform(HTML);
  t.includes(transformed, "<figcaption>our-customized-caption</figcaption>");

  impexTransform.setup({
    verbose: true,
  });

  transformed = impexTransform.transform(HTML);
  t.doesNotInclude(
    transformed,
    "<figcaption>our-customized-caption</figcaption>"
  );
});
