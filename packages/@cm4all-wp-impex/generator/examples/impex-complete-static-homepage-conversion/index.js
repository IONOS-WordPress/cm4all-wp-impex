#!/usr/bin/env node

/*
 *  @cm4all-wp-impex/generator usage example converting a whole static homepage to an impex export
 */

import { resolve, join, extname, dirname, basename } from "path";
import { readdir, readFile, mkdir, rm, writeFile, copyFile } from "fs/promises";
import { ImpexTransformer, ImpexSliceFactory } from "../../src/index.js";

/**
 * STATIC_HOMEPAGE_DIRECTORY is the directory containing the static homepage
 */
const STATIC_HOMEPAGE_DIRECTORY = new URL(
  "homepage-dr-mustermann",
  import.meta.url
).pathname;

/**
 * generator function yielding matched files recursively
 *
 * @param   {string}  dir directory to search
 * @param   {boolean} recursive  whether to search recursively
 * @param   {string|undefined}  extension file extension to match or null to match all files
 *
 * @yields  {string} path to file
 */
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

/**
 * keeps track of images and their references from html files (aka pages)
 * key is image path relative to STATIC_HOMEPAGE_DIRECTORY
 * value is array of image references
 */
const img2imgSrc_mappings = {};

/**
 * set up the ImpexTransformer singleton
 *
 * @return  {ImpexSliceFactory}
 */
function setup() {
  ImpexTransformer.setup({
    onDomReady(document, options = { path: null }) {
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

      if (options?.path) {
        // grab all image references and remember them for later processing
        for (const img of document.querySelectorAll("img")) {
          const src = img.getAttribute("src");

          // compute image path relative to static webpage directory
          const imgPath = resolve(
            join(STATIC_HOMEPAGE_DIRECTORY, src)
          ).substring(STATIC_HOMEPAGE_DIRECTORY);

          // add reference to image path
          (
            img2imgSrc_mappings[imgPath] || (img2imgSrc_mappings[imgPath] = [])
          ).push(src);
        }
      }
    },
  });
  return new ImpexSliceFactory();
}

async function main() {
  // setup ImpexTransformer singleton and get a ImpexSliceFactory instance
  const impexSliceFactory = setup();

  // group files by type (html or attachment)
  const attachmentResources = [];
  const htmlResources = [];

  // iterate over all files recursively in STATIC_HOMEPAGE_DIRECTORY
  for await (const res of getFiles(STATIC_HOMEPAGE_DIRECTORY, true)) {
    const resource = res.toString();

    switch (extname(res)) {
      // stick HTML files into htmlResources
      case ".html":
        htmlResources.push({ resource });
        console.log("HTML %s", resource);
        break;
      // stick media files into attachmentResources
      case ".jpeg":
      case ".jpg":
      case ".gif":
      case ".png":
        attachmentResources.push({ resource });
        console.log("ATTACHMENT %s", resource);
        break;
    }
  }

  // get a generator function yielding ImpEx export format conformant paths
  const slicePathGenerator = ImpexSliceFactory.PathGenerator();

  // compute target directory
  const IMPEX_EXPORT_DIR = new URL("generated-impex-export", import.meta.url)
    .pathname;

  // delete already existing directory if it exists
  try {
    await rm(IMPEX_EXPORT_DIR, { recursive: true });
  } catch {}

  // create target directory
  await mkdir(IMPEX_EXPORT_DIR, { recursive: true });

  // convert html files to gutenberg annotated block content
  for (const htmlResource of htmlResources) {
    // transform html body to gutenberg annotated block content
    htmlResource.content = ImpexTransformer.transform(
      await readFile(htmlResource.resource, "utf8"),
      { path: htmlResource.resource }
    );
    // remember html metadata for later processing
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

    // create ImpEx slice json content for this html file
    const slice = impexSliceFactory.createSlice(
      "content-exporter",
      (factory, slice) => {
        slice.data.posts[0]["wp:post_type"] = "page";
        slice.data.posts[0].title = htmlResource.title;
        slice.data.posts[0]["wp:post_excerpt"] = htmlResource.title;
        slice.data.posts[0]["wp:post_content"] = htmlResource.content;
        // @TODO: categories (aka keywords)
        // @TODO: add navigation
        return slice;
      }
    );

    // compute ImpEx conform slice json file path
    const slicePath = join(IMPEX_EXPORT_DIR, slicePathGenerator.next().value);
    await mkdir(dirname(slicePath), {
      recursive: true,
    });

    // write json to file
    await writeFile(slicePath, JSON.stringify(slice, null, 2));
  }

  // make media files available as ImpEx slices
  for (const attachmentResource of attachmentResources) {
    // create ImpEx slice json content for this media file
    const slice = impexSliceFactory.createSlice(
      "attachment",
      (factory, slice) => {
        // apply relative path as content
        slice.data = attachmentResource.resource.substring(
          IMPEX_EXPORT_DIR.length + 1
        );

        // compute unique image file=>[img[@src]] mapping for this attachment
        let img2imgSrc_mapping = [
          ...new Set(img2imgSrc_mappings[attachmentResource.resource] ?? []),
        ];

        // add mapping to slice metadata
        slice.meta["impex:post-references"] = img2imgSrc_mapping;

        return slice;
      }
    );

    // compute ImpEx conform slice json file path
    const slicePath = join(IMPEX_EXPORT_DIR, slicePathGenerator.next().value);
    await mkdir(dirname(slicePath), {
      recursive: true,
    });

    // write slice json to file
    await writeFile(slicePath, JSON.stringify(slice, null, 2));

    // copy attachment file to target directory with ImpEx conform file name
    await copyFile(
      attachmentResource.resource,
      slicePath.replace(".json", "-" + basename(attachmentResource.resource))
    );
  }

  // JSDOM is preventing automatic process termination so we need to force it
  process.exit(0);
}

main();
