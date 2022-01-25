import hooks from "@wordpress/hooks";
import Debug from "@cm4all-impex/debug";
import ImpexFilters from "@cm4all-impex/filters";

const debug = Debug.default("wp.impex.attachments");
debug("huhu!");

hooks.addFilter(
  ImpexFilters.SLICE_REST_UNMARSHAL,
  ImpexFilters.NAMESPACE,
  async function (namespace, slice, sliceIndex, chunkDirHandle) {
    if (
      slice["tag"] === "attachment" &&
      slice["meta"]["entity"] === "attachment" &&
      slice["type"] === "resource"
    ) {
      const _links_self = slice["_links"]?.["self"];

      if (_links_self) {
        // download attachments to local folder
        for (const entry of _links_self) {
          const href = entry["href"];

          let path = href.split(/[\\/]/);

          const filename =
            `slice-${sliceIndex.toString().padStart(4, "0")}-` + path.pop();
          //path.push(filename);
          //path = path.join("//");

          await fetch(href).then(async (response) => {
            attachmentFileHandle = await chunkDirHandle.getFileHandle(
              filename,
              {
                create: true,
              },
            );
            const writable = await attachmentFileHandle.createWritable();

            await response.body.pipeTo(writable);

            // see https://web.dev/file-system-access/
            // => pipeTo() closes the destination pipe by default, no need to close it.
            // await writable.close();
          });
        }
      }

      delete slice["_links"];
      /*
      slice['_links']['self'][] = [
        'href' => slice[Impex::SLICE_META]['data']['guid'],
        'tag'  => AttachmentsExporter::SLICE_TAG,
        'provider'  => AttachmentsExporter::PROVIDER_NAME,
      ];
      */
    }

    return slice;
  },
);
