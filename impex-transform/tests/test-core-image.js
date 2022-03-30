import test from "./tape-wrapper.js";

import { addFilter, applyFilters } from "@wordpress/hooks";
import { registerCoreBlocks } from "@wordpress/block-library";
import {
  unregisterBlockType,
  getBlockTypes,
  getBlockAttributes,
  createBlock,
} from "@wordpress/blocks";

test("test 'core/image' transform", async (t, impexTransform) => {
  registerCoreBlocks();
  const transformed = impexTransform.transform(`<!DOCTYPE html>
  <body>
      <img src="./greysen-johnson-unsplash.jpg" title="Fly fishing">
  </body>
</html>`);

  t.match(transformed, /^<!-- wp:image -->/);
  t.includes(transformed, ' title="Fly fishing"/>');
  t.match(transformed, /<!-- \/wp:image -->$/);
});

test("test custom core/image transform", async (t, impexTransform) => {
  addFilter(
    "blocks.registerBlockType",
    "prepend-custom-image-transform",
    (blockType) => {
      if (blockType.name === "core/image") {
        blockType.transforms.from.unshift({
          type: "raw",
          isMatch: (node) =>
            node.nodeName === "FIGURE" && !!node.querySelector("img"),
          //schema,
          transform: (node) => {
            // Search both figure and image classes. Alignment could be
            // set on either. ID is set on the image.
            const className =
              node.className + " " + node.querySelector("img").className;
            const alignMatches =
              /(?:^|\s)align(left|center|right)(?:$|\s)/.exec(className);
            const anchor = node.id === "" ? undefined : node.id;
            const align = alignMatches ? alignMatches[1] : undefined;
            const idMatches = /(?:^|\s)wp-image-(\d+)(?:$|\s)/.exec(className);
            const id = idMatches ? Number(idMatches[1]) : undefined;
            const anchorElement = node.querySelector("a");
            const linkDestination =
              anchorElement && anchorElement.href ? "custom" : undefined;
            const href =
              anchorElement && anchorElement.href
                ? anchorElement.href
                : undefined;
            const rel =
              anchorElement && anchorElement.rel
                ? anchorElement.rel
                : undefined;
            const linkClass =
              anchorElement && anchorElement.className
                ? anchorElement.className
                : undefined;
            const attributes = getBlockAttributes(
              "core/image",
              node.outerHTML,
              {
                align,
                id,
                linkDestination,
                href,
                rel,
                linkClass,
                anchor,
              }
            );
            attributes.caption = attributes.title;
            return createBlock("core/image", attributes);
          },
        });
      }
      return blockType;
    }
  );
  registerCoreBlocks();

  const transformed = impexTransform.transform(`<!DOCTYPE html>
<body>
  <img src="./greysen-johnson-unsplash.jpg" title="Fly fishing">
</body>
</html>`);

  t.comment(`<!DOCTYPE html>
  <body>
    <img src="./greysen-johnson-unsplash.jpg" title="Fly fishing">
  </body>
  </html>`);

  t.match(transformed, /^<!-- wp:image -->/);
  t.includes(transformed, ' title="Fly fishing"/>');
  t.match(transformed, /<!-- \/wp:image -->$/);
});
