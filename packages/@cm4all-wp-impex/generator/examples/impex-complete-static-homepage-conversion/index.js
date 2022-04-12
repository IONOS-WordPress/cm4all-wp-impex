#!/usr/bin/env node

/*
 *  @cm4all-wp-impex/generator usage example converting a whole static homepage to an impex export
 */

import { resolve, join, extname, dirname, basename } from "path";
import { readdir, readFile, mkdir, rm, writeFile, copyFile } from "fs/promises";
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
  ImpexTransformer.setup({
    onDomReady(document) {
      // replace <header> elements with the <ul> child
      for (const section of document.querySelectorAll("header")) {
        const ul = document.querySelector("ul.pure-menu-list");
        section.replaceWith(ul.cloneNode(true));
      }

      // replace <section> elements with its inner contents
      for (const section of document.querySelectorAll("section")) {
        for (const child of section.childNodes) {
          section.parentNode.insertBefore(child.cloneNode(true), section);
        }
        section.remove();
      }

      // replace <footer> elements with <p>
      for (const footer of document.querySelectorAll("footer")) {
        const paragraph = document.createElement("p");
        //paragraph.setAttribute("class", "footer");
        paragraph.innerHTML = footer.innerHTML;
        footer.replaceWith(paragraph);
      }
    },
  });
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
    await rm(impexExportDir, { recursive: true });
  } catch {}
  await mkdir(impexExportDir, { recursive: true });

  // transform html file content
  for (const htmlResource of htmlResources) {
    htmlResource.content = ImpexTransformer.transform(
      await readFile(htmlResource.resource, "utf8")
    );
    console.log(htmlResource.content);
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
        slice.data.posts[0]["wp:post_type"] = "page";
        slice.data.posts[0].title = htmlResource.title;
        slice.data.posts[0]["wp:post_excerpt"] = htmlResource.title;
        slice.data.posts[0]["wp:post_content"] = htmlResource.content;
        // @TODO: categories (aka keywords)
        return slice;
      }
    );

    const slicePath = join(impexExportDir, slicePathGenerator.next().value);
    await mkdir(dirname(slicePath), {
      recursive: true,
    });

    await writeFile(slicePath, JSON.stringify(slice, null, 2));
  }

  // transform attachments to wordpress content
  for (const attachmentResource of attachmentResources) {
    const slice = impexSliceFactory.createSlice(
      "attachment",
      (factory, slice) => {
        slice.data = attachmentResource.resource.substring(
          impexExportDir.length + 1
        );
        return slice;
      }
    );

    const slicePath = join(impexExportDir, slicePathGenerator.next().value);
    await mkdir(dirname(slicePath), {
      recursive: true,
    });

    await writeFile(slicePath, JSON.stringify(slice, null, 2));
    await copyFile(
      attachmentResource.resource,
      slicePath.replace(".json", "-" + basename(attachmentResource.resource))
    );
  }

  process.exit(0);
}

main();
