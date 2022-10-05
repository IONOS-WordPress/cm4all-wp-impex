import { readFile, stat, access, mkdir, readdir } from 'node:fs/promises';
import { join, resolve } from 'node:path';

async function* __getSlices(dir) {
  const entries = await readdir(dir, { withFileTypes: true });
  entries.sort((l,r)=>l.name.localeCompare(r.name));
  for (const entry of entries) {
    const res = resolve(dir, entry.name);
    if (entry.isDirectory() && /^chunk-\d{4}$/.test(entry.name)) {
      yield* __getSlices(res);
    } else if (/^slice-\d{4}\.json$/.test(entry.name)) {
      yield res;
    }
  }
}

/**
 * Migrates a impex export. 
 * 
 * Can be used to transform/filter the contents of an existing impex export.
 *
 * @param   {string}  sourcePath         path of the impex export source
 * @param   {string}  targetPath         target impex export path
 * @param   {async *function(slicePath)} sliceCallback function to be called for each slice of the source export
 * @param   {object}  options            optional options
 *
 * @return  {Promise} signalling success of the transformation
 */
export default async function migrate(sourcePath, targetPath, sliceCallback, options = {}) {
  const sourcePathStats = await stat(sourcePath);
  if(!sourcePathStats?.isDirectory()) {
    throw "'sourcePath' is expected to be an existing directory";
  } 

  if(typeof(sliceCallback)!=='function') {
    throw "argument 'sliceCallback expected to be a function";
  }

  await mkdir(targetPath, { recursive : true });

  for await (const slicePath of __getSlices(sourcePath)) {
    sliceCallback(slicePath);
  }
}