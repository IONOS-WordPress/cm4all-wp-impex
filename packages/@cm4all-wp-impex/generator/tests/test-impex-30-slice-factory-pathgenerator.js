import test from "./tape-configuration.js";
import SliceFactory from "../src/impex-slice-factory.js";

test("ImpexSliceFactory::PathGenerator", (t) => {
  let gen = SliceFactory.PathGenerator(1);

  t.equal(gen.next().value, "chunk-0001/slice-0001.json");
  t.equal(gen.next().value, "chunk-0002/slice-0001.json");

  gen = SliceFactory.PathGenerator(2);

  t.equal(gen.next().value, "chunk-0001/slice-0001.json");
  t.equal(gen.next().value, "chunk-0001/slice-0002.json");
  t.equal(gen.next().value, "chunk-0002/slice-0001.json");
  t.equal(gen.next().value, "chunk-0002/slice-0002.json");
  t.equal(gen.next().value, "chunk-0003/slice-0001.json");
  t.equal(gen.next().value, "chunk-0003/slice-0002.json");

  t.end();
});
