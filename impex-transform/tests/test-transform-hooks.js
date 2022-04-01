import test from "./tape-wrapper.js";

import { addFilter, applyFilters } from "@wordpress/hooks";
import {
  unregisterBlockType,
  getBlockTypes,
  getBlockAttributes,
  createBlock,
} from "@wordpress/blocks";

import { traverseBlocks } from "../src/impex-transform.js";

const VERBOSE = true;

test("test 'core/image' transform", async (t, impexTransform) => {
  impexTransform.setup({ verbose: VERBOSE });
  const transformed = impexTransform.transform(`<!DOCTYPE html>
  <body>
      <img src="./greysen-johnson-unsplash.jpg" title="Fly fishing"/
  </body>
</html>`);

  t.match(transformed, /^<!-- wp:image -->/);
  t.includes(transformed, ' title="Fly fishing"/>');
  t.match(transformed, /<!-- \/wp:image -->$/);
});

test("onRegisterCoreBlocks hook : takeover img[@title] as figcaption", async (t, impexTransform) => {
  impexTransform.setup({
    verbose: VERBOSE,
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
                delete attributes.title;

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

  let transformed = impexTransform.transform(HTML);
  t.includes(transformed, "<figcaption>Fly fishing</figcaption>");

  impexTransform.setup({
    verbose: VERBOSE,
    onRegisterCoreBlocks() {
      // same as first transforms from filter but implemented by reusing first from transform of core/image
      addFilter(
        "blocks.registerBlockType",
        "prepend-custom-image-transform",
        (blockType) => {
          if (blockType.name === "core/image") {
            @TODO: BUG - will be called multiple times !!!!!

            // grab the first transform from core/image
            const from = blockType.transforms.from[0];
            const orig_transform = blockType.transforms.from[0].transform;

            blockType.transforms.from.unshift({
              ...from,
              transform(node) {
                const block = orig_transform(node);

                // move img[@title] over as block attribute caption (=> results in <figcation> tag)
                block.attributes.caption = block.attributes.title;
                delete block.attributes.title;

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

test("onSerialize hook : 'core/image' transform", async (t, impexTransform) => {
  impexTransform.setup({
    verbose: VERBOSE,
    onSerialize(blocks) {
      // takeover img[@title] as figcaption in every block
      for (const block of traverseBlocks(blocks)) {
        if (block.name === "core/image") {
          block.attributes.caption = block.attributes.title;
          delete block.attributes.title;
        }
      }

      return blocks;
    },
  });
  const transformed = impexTransform.transform(`<!DOCTYPE html>
  <body>
    <img src="./greysen-johnson-unsplash.jpg" title="Fly fishing"/>
  </body>
</html>`);

  t.match(transformed, /^<!-- wp:image -->/);
  t.includes(transformed, "<figcaption>Fly fishing</figcaption>");
  t.match(transformed, /<!-- \/wp:image -->$/);
});

test("onSerialize hook : surround <img> with <figure>", async (t, impexTransform) => {
  impexTransform.setup({
    verbose: VERBOSE,
    onDomReady(document) {
      // @TODO: this looks a bit hacky and not very elegant but is the owed the limiting css / dom support of jsdom
      for (const IMG of Array.from(document.querySelectorAll("img"))) {
        if (IMG.hasAttribute("title")) {
          const FIGURE = document.createElement("figure");
          FIGURE.setAttribute("class", "wp-block-image");
          const _IMG = IMG.cloneNode(true);
          _IMG.removeAttribute("title");
          FIGURE.appendChild(_IMG);
          const FIGCAPTION = document.createElement("figcaption");
          FIGCAPTION.textContent = IMG.getAttribute("title");
          FIGURE.appendChild(FIGCAPTION);
          IMG.replaceWith(FIGURE);
        }
      }
    },
  });
  const transformed = impexTransform.transform(`<!DOCTYPE html>
  <body>
      <img src="./greysen-johnson-unsplash.jpg" title="Fly fishing"/>
  </body>
</html>`);

  t.doesNotInclude(transformed, 'title="Fly fishing"');
  t.match(transformed, /^<!-- wp:image -->/);
  t.includes(transformed, "<figcaption>Fly fishing</figcaption>");
  t.match(transformed, /<!-- \/wp:image -->$/);
});
