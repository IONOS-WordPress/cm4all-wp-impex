import { readFile, stat, mkdir, readdir, cp } from 'node:fs/promises';
import { join, resolve, dirname, basename } from 'node:path';
import ImpexSliceFactory from './impex-slice-factory.js';

export const MIGRATE_DEFAULT_OPTIONS = {
  max_slices_per_chunk : 10,
};

async function* getSlices(dir) {
  const entries = await readdir(dir, { withFileTypes: true });
  entries.sort((l,r)=>l.name.localeCompare(r.name));
  for (const entry of entries) {
    const res = resolve(dir, entry.name);
    if (entry.isDirectory() && /^chunk-\d{4}$/.test(entry.name)) {
      yield* getSlices(res);
    } else if (/^slice-\d{4}\.json$/.test(entry.name)) {
      yield res;
    }
  }
}

async function processSlice(sliceCallback, slicePath, pathGenerator, targetPath, options) {
  // we should handle the slice by ourself if callback returns falsy
  if(!await sliceCallback(slicePath)) {
    // fuzzy testing for slices transporting a binary subsidiary file like attachments
    const sliceFilenameBase = basename(slicePath, '.json');
    let entries = await readdir(dirname(slicePath), { withFileTypes: true });
    entries = entries
      .filter(entry=>entry.name.startsWith(sliceFilenameBase));
    
    const sourceSliceDir = dirname(slicePath);
    const targetSlicePath = join(targetPath, pathGenerator.next().value);
    mkdir(dirname(targetSlicePath), { recursive : true});
    for (const entry of entries) {
      await cp(
        join(sourceSliceDir, entry.name), 
        targetSlicePath + entry.name.substring(sliceFilenameBase.length),
        { force : true, recursive : true },
      );
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
    throw "'sourcePath' expected to be an existing directory";
  } 

  if(typeof(sliceCallback)!=='function') {
    throw "argument 'sliceCallback' expected to be a function";
  }

  await mkdir(targetPath, { recursive : true });

  const slices = getSlices(sourcePath);

  const { value : firstSlicePath, done : empty} = await slices.next();
  if(empty) {
    return 
  }

  // merge default options into options 
  options = { ...MIGRATE_DEFAULT_OPTIONS, ...options};

  const pathGenerator = ImpexSliceFactory.PathGenerator(options.max_slices_per_chunk, '');

  // curry processSlice arguments 
  const _processSlice = async (slicePath) => await processSlice(sliceCallback, slicePath, pathGenerator, targetPath, options);

  await options.onStart?.();
  await _processSlice(firstSlicePath);

  for await (const slicePath of slices) {
    await _processSlice(slicePath);
  }

  await options.onFinish?.();
}