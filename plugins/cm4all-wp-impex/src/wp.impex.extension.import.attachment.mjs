import hooks from "@wordpress/hooks";
import Debug from "@cm4all-impex/debug";
import ImpexFilters from "@cm4all-impex/filters";

const debug = Debug.default("wp.impex.attachments");
debug("huhu!");

hooks.addFilter(
  ImpexFilters.SLICE_REST_UPLOAD,
  ImpexFilters.NAMESPACE,
  async function (namespace, slice, sliceIndex, chunkDirHandle, formData) {
    if (
      slice["tag"] === "attachment" &&
      slice["meta"]["entity"] === "attachment" &&
      slice["type"] === "resource"
    ) {
      const localAttachmentFilename =
        `slice-${sliceIndex.toString().padStart(4, "0")}-` +
        slice["data"].split(/[\\/]/).pop();

      const localAttachmentFileHandle = await chunkDirHandle
        .getFileHandle(localAttachmentFilename)
        .catch((NotFoundError)=>{
          return chunkDirHandle.getFileHandle(`slice-${sliceIndex.toString().padStart(4, "0")}-attachment.blob`);
        })
        .catch((e) => {
          console.log(localAttachmentFilename);
          return Promise.reject(e);
        });

      formData.append(
        // constant was injected using \wp_add_inline_script
        wp.impex.extension.import.attachment
          .WP_FILTER_IMPORT_REST_SLICE_UPLOAD_FILE,
        await localAttachmentFileHandle.getFile(),
      );
    }

    return slice;
  },
);

export default {};
