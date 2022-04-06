import test from "./tape-configuration.js";
import SliceFactory from "../src/impex-slice-factory.js";

test("ImpexSliceFactory: basics", (t) => {
  const sliceFactory = new SliceFactory();

  const registeredSliceTypes = sliceFactory.getRegisteredSliceTypes();
  t.ok(registeredSliceTypes.includes("content-exporter"));
  t.ok(registeredSliceTypes.includes("attachment"));

  t.end();
});

test("ImpexSliceFactory: content-exporter", (t) => {
  const sliceFactory = new SliceFactory({ next_post_id: 10 });

  const slice = sliceFactory.createSlice(
    "content-exporter",
    (factory, slice) => {
      slice.data.posts[0].title = "Hello";
      slice.data.posts[0]["wp:post_content"] =
        "<!-- wp:paragraph --><p>my friend</p><!-- /wp:paragraph -->";
      return slice;
    }
  );

  t.equal(slice?.tag, "content-exporter");
  t.equal(slice.data.posts.length, 1);

  t.equal(slice?.data?.posts[0]["wp:post_id"], 10);
  t.equal(slice?.data?.posts[0]?.title, "Hello");
  t.match(slice?.data?.posts[0]["wp:post_content"], /^<!-- wp:paragraph -->/);
  t.match(slice?.data?.posts[0]["wp:post_content"], /<!-- \/wp:paragraph -->$/);

  t.end();
});

test("ImpexSliceFactory: attachment", (t) => {
  const sliceFactory = new SliceFactory();

  const slice = sliceFactory.createSlice("attachment", (factory, slice) => {
    slice.data = "file://wow.jpg";
    return slice;
  });

  t.equal(slice?.tag, "attachment");
  t.ok(typeof slice?.data, "string");
  t.equal(slice?.data, "file://wow.jpg");

  t.end();
});

test("ImpexSliceFactory: custom slice type", (t) => {
  const sliceFactory = new SliceFactory({ next_post_id: 10 });

  sliceFactory.registerSliceType(
    "custom-impex-export-provider-type",
    (factory, callback) => {
      const boilerplate = {
        version: "1.0.0",
        type: "php",
        tag: "custom-impex-export-provider-type",
        meta: {
          entity: "custom-impex-export-provider-type",
        },
        data: {
          id: factory.getNextPostId(),
          foo: "bar",
        },
      };

      return callback(factory, boilerplate);
    }
  );

  const slice = sliceFactory.createSlice(
    "custom-impex-export-provider-type",
    (factory, slice) => {
      slice.data.foo = slice.data.foo + "baz";

      return slice;
    }
  );

  t.equal(slice?.tag, "custom-impex-export-provider-type");
  t.equal(slice?.data?.id, 10);
  t.equal(slice?.data?.foo, "barbaz");

  t.end();
});
