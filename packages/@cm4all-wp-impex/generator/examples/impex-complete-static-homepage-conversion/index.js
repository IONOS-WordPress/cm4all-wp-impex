#!/usr/bin/env node

/*
 *  @cm4all-wp-impex/generator usage example converting a whole static homepage to an impex export
 */

import { resolve, join, extname, dirname } from "path";
import { readdir, readFile, mkdir, rmdir, writeFile } from "fs/promises";
import { ImpexTransformer, ImpexSliceFactory } from "../../src/index.js";

async function* getFiles(dir, recursive, extension) {
  const entries = await readdir(dir, { withFileTypes: true });
  for (const entry of entries) {
    const res = resolve(dir, entry.name);
    if (entry.isDirectory()) {
      yield* getFiles(res, recursive, extension);
    } else if (extension !== null || entry.name.endsWith(extension)) {
      yield res;
    }
  }
}

function setup() {
  ImpexTransformer.setup();
  return new ImpexSliceFactory();
}

async function main() {
  const impexSliceFactory = setup();

  // group files by type (html or attachment)
  const attachmentResources = [];
  const htmlResources = [];
  for await (const res of getFiles(
    new URL("homepage-dr-mustermann", import.meta.url).pathname,
    true
  )) {
    const resource = res.toString();
    switch (extname(res)) {
      case ".html":
        htmlResources.push({ resource });
        console.log("HTML %s", resource);
        break;
      case ".jpeg":
      case ".jpg":
      case ".gif":
      case ".png":
        attachmentResources.push({ resource });
        console.log("ATTACHMENT %s", resource);
        break;
    }
  }

  const slicePathGenerator = ImpexSliceFactory.PathGenerator();
  const impexExportDir = new URL("generated-impex-export", import.meta.url)
    .pathname;

  // recreate export directory
  try {
    await rmdir(impexExportDir, { recursive: true });
  } catch {}
  await mkdir(impexExportDir, { recursive: true });

  // transform html file content
  for (const htmlResource of htmlResources) {
    htmlResource.transformed = ImpexTransformer.transform(
      await readFile(htmlResource.resource, "utf8")
    );
    console.log(htmlResource.transformed);
    htmlResource.title =
      document.querySelector("head > title")?.textContent ?? "";
    htmlResource.description =
      document
        .querySelector('head > meta[name="description"]')
        ?.getAttribute("content") ?? "";
    htmlResource.keywords = (
      document
        .querySelector('head > meta[name="keywords"]')
        .getAttribute("content") ?? ""
    )
      .toLowerCase()
      .split(" ");

    const slice = impexSliceFactory.createSlice(
      "content-exporter",
      (factory, slice) => {
        slice.data.posts[0].title = "Hello";
        slice.data.posts[0]["wp:post_content"] =
          "<!-- wp:paragraph --><p>my friend</p><!-- /wp:paragraph -->";
        return slice;
      }
    );

    const slicePath = join(impexExportDir, slicePathGenerator.next().value);
    await mkdir(dirname(slicePath), {
      recursive: true,
    });

    await writeFile(slicePath, JSON.stringify(slice, null, 2));
  }

  process.exit(0);
}

main();
