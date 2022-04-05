import test from "./tape-wrapper.js";
import SliceFactory from "../src/impex-slice.js";

const VERBOSE = true;

/*

{
  "version": "1.0.0",
  "type": "php",
  "tag": "content-exporter",
  "meta": {
    "entity": "content-exporter"
  },
  "data": {
    "posts": [
      {
        "wp:post_id": 1,
        "wp:post_content": "<!-- wp:paragraph -->\n<p>Hello from first imported post !</p>\n<!-- /wp:paragraph -->",
        "title": "Hello first post!"
      }
    ]
  }
}


*/

test("test slice ", async (t, transformer) => {
  const factory = new SliceFactory();

  // const contentSlice = factory.createSlice({});

  t.true(true);
});
