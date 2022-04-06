import path from "path";

export default class ImpexSliceFactory {
  #slices_per_chunk_dir = 50;

  #next_post_id = 1;

  static #SLICE_TYPE_REGISTRY = (() => {
    const registry = {};

    registry["content-importer"] = (
      /* ImpexSliceFactory */ factory,
      options
    ) => {
      return {};
    };

    registry["attachment"] = (/* ImpexSliceFactory */ factory, options) => {
      return {};
    };

    return registry;
  })();

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

  getNextPostId() {
    return this.#next_post_id++;
  }

  /**
   * Generator function yielding relative Impex export paths.
   * Each generator call returns the path to the next slice file.
   *
   * yielded example : 'chunk-0001/slice-0001.json'
   *
   * @param {int=10} max_slices_per_chunk defines the maximum number of slices in a chunk directory
   *
   * @yields {string}
   */
  static *PathGenerator(max_slices_per_chunk = 10) {
    for (let chunk = 1; ; chunk++) {
      for (let slice = 1; slice <= max_slices_per_chunk; slice++) {
        yield path.join(
          "chunk-" + chunk.toString().padStart(4, "0"),
          "slice-" + slice.toString().padStart(4, "0") + ".json"
        );
      }
    }
  }

  createSlice(slice_type, options = {}) {
    const callback =
      this.#sliceTypeRegistry[slice_type] ||
      ImpexSliceFactory.#SLICE_TYPE_REGISTRY[slice_type] ||
      (() => {
        throw new Error(`Slice type "${slice_type}" not registered`);
      });

    const slice = callback(this, options);

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
