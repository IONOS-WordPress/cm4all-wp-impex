import path from "path";

export default class ImpexSliceFactory {
  #next_post_id = 1;

  static #SLICE_TYPE_REGISTRY = (() => {
    const registry = {};

    registry["content-exporter"] = (
      /* ImpexSliceFactory */ factory,
      callback
    ) => {
      const boilerplate = {
        version: "1.0.0",
        type: "php",
        tag: "content-exporter",
        meta: {
          entity: "content-exporter",
        },
        data: {
          posts: [
            {
              "wp:post_id": factory.getNextPostId(),
              "wp:post_content": null,
              title: null,
            },
          ],
        },
      };

      return callback(factory, boilerplate);
    };

    registry["attachment"] = (/* ImpexSliceFactory */ factory, callback) => {
      const boilerplate = {
        version: "1.0.0",
        type: "resource",
        tag: "attachment",
        meta: {
          entity: "attachment",
        },
        data: null,
      };

      return callback(factory, boilerplate);
    };

    return registry;
  })();

  #sliceTypeRegistry = {};

  constructor(options = {}) {
    this.#next_post_id = options.next_post_id ?? this.#next_post_id;
  }

  registerSliceType(slice_type, callback) {
    this.#sliceTypeRegistry[slice_type] = callback;
    return this;
  }

  getRegisteredSliceTypes() {
    return [
      ...Object.keys(ImpexSliceFactory.#SLICE_TYPE_REGISTRY),
      ...Object.keys(this.#sliceTypeRegistry),
    ];
  }

  getNextPostId() {
    return this.#next_post_id++;
  }

  /**
   * Generator function yielding relative ImpEx export paths.
   * Each generator call returns the path to the next slice file.
   *
   * yielded example : 'chunk-0001/slice-0000.json'
   *
   * @param {int=10} max_slices_per_chunk defines the maximum number of slices in a chunk directory
   *
   * @yields {string}
   */
  static *PathGenerator(max_slices_per_chunk = 10, extension=".json") {
    for (let chunk = 1; ; chunk++) {
      for (let slice = 0; slice < max_slices_per_chunk; slice++) {
        yield path.join(
          "chunk-" + chunk.toString().padStart(4, "0"),
          "slice-" + slice.toString().padStart(4, "0") + extension
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
