import test from "./tape-wrapper.js";

import { addFilter, applyFilters } from "@wordpress/hooks";
import {
  unregisterBlockType,
  getBlockTypes,
  getBlockAttributes,
  createBlock,
} from "@wordpress/blocks";

test("test 'core/image' transform", async (t, impexTransform) => {
  impexTransform.setup({ verbose: true });
  const transformed = impexTransform.transform(`<!DOCTYPE html>
  <body>
      <img src="./greysen-johnson-unsplash.jpg" title="Fly fishing">
  </body>
</html>`);

  t.match(transformed, /^<!-- wp:image -->/);
  t.includes(transformed, ' title="Fly fishing"/>');
  t.match(transformed, /<!-- \/wp:image -->$/);
});

test("custom core/image transform : takeover img[@title] as figcaption", async (t, impexTransform) => {
  impexTransform.setup({
    verbose: true,
    onRegisterCoreBlocks() {
      // copied from https://github.com/WordPress/gutenberg/blob/3da717b8d0ac7d7821fc6d0475695ccf3ae2829f/packages/block-library/src/image/transforms.js#L74
      // except one line (see below)
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
                const idMatches = /(?:^|\s)wp-image-(\d+)(?:$|\s)/.exec(
                  className
                );
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
                // this is the line that is modified
                // take over image[@title] as caption
                attributes.caption = attributes.title;

                return createBlock("core/image", attributes);
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
    <img src="./greysen-johnson-unsplash.jpg" title="Fly fishing">
  </body>
  </html>`;

  t.comment(HTML);

  let transformed = impexTransform.transform(HTML);
  t.includes(transformed, "<figcaption>Fly fishing</figcaption>");

  impexTransform.setup({
    verbose: true,
    onRegisterCoreBlocks() {
      // same as first transforms from filter but implemented by reusing first from transform of core/image
      addFilter(
        "blocks.registerBlockType",
        "prepend-custom-image-transform",
        (blockType) => {
          if (blockType.name === "core/image") {
            // grab the first transform from core/image
            const from = blockType.transforms.from[0];

            blockType.transforms.from.unshift({
              ...from,
              transform(node) {
                const block = from.transform(node);
                // take over block attribute title as caption
                block.attributes.caption = block.attributes.title;
                return block;
              },
            });
          }
          return blockType;
        }
      );
    },
  });

  transformed = impexTransform.transform(HTML);
  t.includes(transformed, "<figcaption>Fly fishing</figcaption>");
});
