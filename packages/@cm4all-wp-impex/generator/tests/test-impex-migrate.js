import test from "./tape-configuration.js";
import migrate from "../src/impex-migrate.js";
import { rm, mkdir } from 'node:fs/promises';
import { basename } from 'node:path';
import 'zx/globals'

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

  try {
    await $`diff -r ${__SNAPSHOT_HECHT} ${__TMP_DIR}/noop-migration`;
    t.pass('migrated impex export directory is identical');
  } catch(ex) {
    t.fail(`migrated impex export directory content is not identical : "diff -r ${__SNAPSHOT_HECHT} ${__TMP_DIR}/noop-migration" (exitCode=${ex.exitCode},stderr="${ex.stderr}",stdout="${ex.stdout}")`);
  }

  t.end();
});

test("migrate() : test onStart/onFinish events", async (t) => {
  __teardown(t);

  const eventOrder = new Set();
  await migrate(__SNAPSHOT_HECHT, `${__TMP_DIR}/dummy-migration`, (slicePath) => eventOrder.add('cb()'), 
    {
      onStart() {
        eventOrder.add('onStart');
      },
      onFinish() {
        eventOrder.add('onFinish');
      },
    }
  );

  t.deepEqual(Array.from(eventOrder), ['onStart', 'cb()', 'onFinish'], 'ensure order of events');

  const actualEventsOnEmptyImport = [];
  const EMPTY_EXPORT_DIR = `${__TMP_DIR}/empty-export`;
  mkdir(EMPTY_EXPORT_DIR, {recursive : true });
  await migrate(EMPTY_EXPORT_DIR, `${__TMP_DIR}/empty-migration`, () => {}, {
    onStart() {
      t.fail('onStart event should not be fired');
    },
    onFinish() {
      t.fail('onFinish event should not be fired');
    },
  });

  t.end();
});