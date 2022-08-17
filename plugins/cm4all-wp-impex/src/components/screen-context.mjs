import element from "@wordpress/element";
import Debug from "@cm4all-impex/debug";
import hooks from "@wordpress/hooks";
import ImpexFilters from "@cm4all-impex/filters";

const debug = Debug.default("wp.impex.dashboard.export");
debug("loaded");

const ContextProvider = element.createContext();

export default function useScreenContext() {
  return element.useContext(ContextProvider);
}

ScreenContext = {
  normalizeFilename(fileName) {
    return (
      fileName
        .replace(/[^a-z0-9\-_]/gi, "_")
        .replace(/(-+)|(_+)/gi, ($) => $[0])
        .toLowerCase()
        // allow a maximum of 32 characters
        .slice(-32)
    );
  },
  currentDateString() {
    const date = new Date();
    return `${date.getFullYear()}-${("0" + (date.getMonth() + 1)).slice(-2)}-${(
      "0" + date.getDate()
    ).slice(-2)}-${date.getHours()}-${date.getMinutes()}-${date.getSeconds()}`;
  },
  async saveSlicesChunk(exportDirHandle, response, chunk) {
    const slices = await response;
    debug(`saveSlicesChunk(chunk=%o) : %o`, chunk, response);

    // create chunk sub directory
    const chunkDirHandle = await exportDirHandle.getDirectoryHandle(
      `chunk-${chunk.toString().padStart(4, "0")}`,
      { create: true }
    );

    return Promise.all(
      slices.map(async (slice, index) => {
        const sliceFileHandle = await chunkDirHandle.getFileHandle(
          `slice-${index.toString().padStart(4, "0")}.json`,
          { create: true }
        );

        slice = await hooks.applyFilters(
          ImpexFilters.SLICE_REST_UNMARSHAL,
          ImpexFilters.NAMESPACE,
          slice,
          index,
          chunkDirHandle
        );

        // Create a FileSystemWritableFileStream to write to.
        const writable = await sliceFileHandle.createWritable();
        // Write the contents of the file to the stream.
        await writable.write(JSON.stringify(slice, null, "  "));
        debug("slice(=%o) = %o", index, slice);
        // Close the file and write the contents to disk.
        await writable.close();
      })
    );
  },
};

export function ScreenContextProvider({ children }) {
  return (
    <ContextProvider.Provider value={ScreenContext}>
      {children}
    </ContextProvider.Provider>
  );
}
