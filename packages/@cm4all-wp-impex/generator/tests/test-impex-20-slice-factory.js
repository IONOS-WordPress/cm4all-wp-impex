import test from "./tape-configuration.js";
import SliceFactory from "../src/impex-slice.js";

const VERBOSE = true;

test("ImpexSliceFactory: ensure blubb", (t) => {
  t.end();
});

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
