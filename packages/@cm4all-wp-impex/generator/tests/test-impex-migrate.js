import test from "./tape-configuration.js";
import migrate from "../src/impex-migrate.js";
import { rm } from 'node:fs/promises';
import { basename } from 'node:path';

const __TMP_DIR = './tests/tmp';
const __SNAPSHOT_HECHT = './tests/fixtures/impex-snapshots/hecht';

function __teardown(t) {
  t.teardown(async () => await rm(__TMP_DIR, { recursive : true, force : true }));
}

test("migrate() : test argument validation", async (t) => {
  __teardown(t);

  try {
    await migrate('./package.json', 'out');
    t.fail('should fail since argument "sourcePath" is a file');
  } catch {}

  try {
    await migrate('./not-existing', `${__TMP_DIR}/not-existing-migration`);
    t.fail('should fail since argument "sourcePath" is not existing');
  } catch {}

  try {
    await migrate(__SNAPSHOT_HECHT, `${__TMP_DIR}/migrated-hecht-snapshot`);
    t.fail('should fail since argument "sliceCallback" is not a function');
  } catch {}

  await migrate(__SNAPSHOT_HECHT, `${__TMP_DIR}/migrated-hecht-snapshot`, () => {});
  t.pass('migration succeeds');

  t.end();
});

test("migrate() : test noop migration", async (t) => {
  __teardown(t);

  const slicePaths = [];
  await migrate(__SNAPSHOT_HECHT, `${__TMP_DIR}/noop-migration`, (slicePath) => {
    slicePaths.push(basename(slicePath));
  });
  t.deepEqual(slicePaths, new Array(slicePaths.length).fill('').map((_,index)=>`slice-${('' + index).padStart(4, '0')}.json`));

  t.end();
});
