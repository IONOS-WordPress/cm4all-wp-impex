import path from "path";

export default class ImpexSliceFactory {
  #slices_per_chunk_dir = 50;

  #next_post_id = 1;

  #sliceTypeRegistry = {};

  constructor(options = {}) {
    this.#slices_per_chunk_dir =
      options.slices_per_chunk_dir ?? this.#slices_per_chunk_dir;

    this.#next_post_id = options.next_post_id ?? this.#next_post_id;
  }

  registerSliceType(slice_type, callback) {
    this.#sliceTypeRegistry[slice_type] = callback;
    return this;
  }

  createSlice(slice_type, options = {}) {
    const callback =
      this.#sliceTypeRegistry[slice_type] ||
      (() => {
        throw new Error(`Slice type "${slice_type}" not registered`);
      });

    const slice = callback(options);

    return slice;
  }
}

/*

{
  "version": "1.0.0",
  "type": "resource",
  "tag": "attachment",
  "meta": {
    "entity": "attachment"
  },
  "data": "./wes-walker-unsplash.jpg"
}


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
