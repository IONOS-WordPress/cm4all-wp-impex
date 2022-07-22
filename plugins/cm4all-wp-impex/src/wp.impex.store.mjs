import data from "@wordpress/data";
import apiFetch from "@wordpress/api-fetch";
import url from "@wordpress/url";
import Debug from "@cm4all-impex/debug";
import { __, sprintf } from "@wordpress/i18n";

const debug = Debug.default("wp.impex.store");
debug("loaded");

const KEY = `cm4all/impex`;

export default async function (settings) {
  const { namespace, base_uri, site_url } = settings;
  const DEFAULT_STATE = {
    settings,
    imports: [],
    exports: [],
    importProfiles: [],
    exportProfiles: [],
  };

  const discovery = await apiFetch({
    path: "/",
  });

  if (!discovery.namespaces.includes(namespace)) {
    throw `rest discovery doesn't provide expected impex rest namespace(=${namespace})`;
  }

  if (!discovery.routes[base_uri]) {
    throw `rest discovery doesn't provide expected impex rest route (=${base_uri})`;
  }

  const actions = {
    // this is a redux thunk (see https://make.wordpress.org/core/2021/10/29/thunks-in-gutenberg/)
    createAndDownloadExport: (exportProfile, screenContext) =>
      async function* ({ dispatch, registry, resolveSelect, select }) {
        let exportsDirHandle = null;
        try {
          // showDirectoryPicker will throw a DOMExxception in case the user pressed cancel
          // see https://web.dev/file-system-access/
          // see https://developer.mozilla.org/en-US/docs/Web/API/window/showDirectoryPicker
          exportsDirHandle = await window.showDirectoryPicker({
            startIn: "downloads",
            mode: "readwrite",
            id: "impex-dir",
          });
        } catch {
          return;
        }

        let _exportFolderName = screenContext.normalizeFilename(
          `${window.location.hostname}-${
            exportProfile.name
          }-${screenContext.currentDateString()}`
        );

        _exportFolderName =
          prompt(
            "Enter name of the export (max 32 characters):",
            _exportFolderName
          ) ?? _exportFolderName;

        /*
        _exportFolderName.substring(0, 32);

        matchingexistingExports = [];
        for await (let exportsDirChildHandle of exportsDirHandle.values()) {
          if (
            exportsDirChildHandle.kind === "directory" &&
            exportsDirChildHandle.name.startsWith(_exportFolderName)
          ) {
            matchingexistingExports.push(exportsDirChildHandle.name);
          }
        }
        */

        // ensure directory does not exist
        try {
          const r = await exportsDirHandle.getDirectoryHandle(
            _exportFolderName,
            {
              create: false,
            }
          );

          throw new Error(
            `Export folder ${_exportFolderName} already exists. Please remove/rename it and continue.\n(${ex.message})`
          );
        } catch (ex) {}

        const exportDirHandle = await exportsDirHandle.getDirectoryHandle(
          _exportFolderName,
          {
            create: true,
          }
        );

        debug({ exportDirHandle });

        const exports = select.getExports();
        debug({ exports });

        /*
        const createdExport = await dispatch.createExport(
          exportProfile,
          `intermediate-${window.crypto.randomUUID()}`,
          `intermediate snapshot created using profile ${exportProfile.name}`
                  // const date = screenContext.currentDateString();
        // const name = `${site_url.hostname}-${exportProfile.name}-${date}`;
        // const description = `Export '${exportProfile.name}' created by user '${currentUser.name}' at ${date}`;

        );

        const exports2 = select.getExports();
        console.log(exports2);
        */

        /*
        yield {
          type: "progress",
          title: __("Export", "cm4all-wp-impex"),
          message: __("Creating snapshot", "cm4all-wp-impex"),
        };

        await new Promise((resolve) => setTimeout(resolve, 1000));

        yield {
          type: "progress",
          title: __("Export", "cm4all-wp-impex"),
          message: __("Downloading snapshot", "cm4all-wp-impex"),
        };

        await new Promise((resolve) => setTimeout(resolve, 1000));

        // throw {
        //   title: __("Export failed", "cm4all-wp-impex"),
        //   message: __("Export failed by abortion", "cm4all-wp-impex"),
        // };

        throw new Error("Huuuuu - something went wrong");

        yield {
          type: "progress",
          title: __("Export", "cm4all-wp-impex"),
          message: __("Done", "cm4all-wp-impex"),
        };

        await new Promise((resolve) => setTimeout(resolve, 1000));
        */
      },

    async createExport(exportProfile, name = "", description = "") {
      const payload = await apiFetch({
        path: `${settings.base_uri}/export`,
        method: "POST",
        data: { profile: exportProfile.name, name, description },
      });

      return {
        type: "ADD_EXPORT",
        payload,
      };
    },
    setExports(exports) {
      return {
        type: "SET_EXPORTS",
        payload: exports,
      };
    },
    async updateExport(id, data) {
      const updatedExport = await apiFetch({
        path: `${DEFAULT_STATE.settings.base_uri}/export/${id}`,
        method: "PATCH",
        data,
      });

      return {
        type: "UPDATE_EXPORT",
        payload: updatedExport,
      };
    },
    async deleteExport(id) {
      const deletedExport = await apiFetch({
        path: `${settings.base_uri}/export/${id}`,
        method: "DELETE",
      });

      return {
        type: "DELETE_EXPORT",
        payload: id,
      };
    },
    async createImport(name, description, importProfile, options) {
      const payload = await apiFetch({
        path: `${settings.base_uri}/import`,
        method: "POST",
        data: { name, description, profile: importProfile.name, options },
      });

      return {
        type: "ADD_IMPORT",
        payload,
      };
    },
    async consumeImport(id, options = {}, offset = null, limit = null) {
      const queryArgs = {};

      if (offset !== null) {
        queryArgs["offset"] = offset;
      }

      if (limit !== null) {
        queryArgs["limit"] = limit;
      }

      const payload = await apiFetch({
        path: url.addQueryArgs(
          `${settings.base_uri}/import/${id}/consume`,
          queryArgs
        ),
        method: "POST",
        data: { options },
      });

      return {
        type: "",
      };
    },
    setImports(exports) {
      return {
        type: "SET_IMPORTS",
        payload: exports,
      };
    },
    async updateImport(id, data) {
      const updatedImport = await apiFetch({
        path: `${DEFAULT_STATE.settings.base_uri}/import/${id}`,
        method: "PATCH",
        data,
      });

      return {
        type: "UPDATE_IMPORT",
        payload: updatedImport,
      };
    },
    async deleteImport(id) {
      const deletedImport = await apiFetch({
        path: `${settings.base_uri}/import/${id}`,
        method: "DELETE",
      });

      return {
        type: "DELETE_IMPORT",
        payload: id,
      };
    },
  };

  const selectors = {
    getExportProfile(state, name) {
      return state.exportProfiles.find((_) => _.name === name);
    },

    getExportProfiles(state) {
      return state.exportProfiles;
    },

    getExport(state, id) {
      return state.exports.find((_) => _.id === id);
    },

    getExports(state) {
      return state.exports;
    },

    getImportProfile(state, name) {
      return state.importProfiles.find((_) => _.name === name);
    },

    getImportProfiles(state) {
      return state.importProfiles;
    },

    getImport(state, id) {
      return state.imports.find((_) => _.id === id);
    },

    getImports(state) {
      return state.imports;
    },

    getSettings(state) {
      return state.settings;
    },
  };

  const store = data.createReduxStore(KEY, {
    __experimentalUseThunks: true,
    reducer(state = DEFAULT_STATE, { type, payload }) {
      switch (type) {
        case "ADD_EXPORT": {
          return {
            ...state,
            exports: [payload, ...state.exports],
          };
        }
        case "UPDATE_EXPORT": {
          const indexOfExport = state.exports.findIndex(
            (_) => _.id === payload.id
          );
          if (indexOfExport === -1) {
            debug("Export(id=%s) is unknown", payload.id);
          }

          state.exports.splice(indexOfExport, 1, payload);

          return {
            ...state,
            exports: [...state.exports],
          };
        }
        case "DELETE_EXPORT": {
          const indexOfExport = state.exports.findIndex(
            (_) => _.id === payload
          );
          if (indexOfExport === -1) {
            debug("Export(id=%s) is unknown", payload);
          }

          state.exports.splice(indexOfExport, 1);

          return {
            ...state,
            exports: [...state.exports],
          };
        }
        case "SET_EXPORTS": {
          return {
            ...state,
            exports: [...payload],
          };
        }
        case "SET_EXPORTPROFILES": {
          return {
            ...state,
            exportProfiles: [...payload].sort((l, r) =>
              l.name.localeCompare(r.name)
            ),
          };
        }

        case "ADD_IMPORT": {
          return {
            ...state,
            imports: [payload, ...state.imports],
          };
        }
        case "UPDATE_IMPORT": {
          const indexOfExport = state.imports.findIndex(
            (_) => _.id === payload.id
          );
          if (indexOfExport === -1) {
            debug("Export(id=%s) is unknown", payload.id);
          }

          state.imports.splice(indexOfExport, 1, payload);

          return {
            ...state,
            imports: [...state.imports],
          };
        }
        case "DELETE_IMPORT": {
          const indexOfExport = state.imports.findIndex(
            (_) => _.id === payload
          );
          if (indexOfExport === -1) {
            debug("Export(id=%s) is unknown", payload);
          }

          state.imports.splice(indexOfExport, 1);

          return {
            ...state,
            imports: [...state.imports],
          };
        }
        case "SET_IMPORTS": {
          return {
            ...state,
            imports: [...payload],
          };
        }
        case "SET_IMPORTPROFILES": {
          return {
            ...state,
            importProfiles: [...payload].sort((l, r) =>
              l.name.localeCompare(r.name)
            ),
          };
        }
      }

      return state;
    },
    actions,
    selectors,
    resolvers: {
      async getExportProfiles() {
        const payload = await apiFetch({
          path: `${base_uri}/export/profile`,
        });
        return {
          type: "SET_EXPORTPROFILES",
          payload,
        };
      },
      async getExports() {
        const payload = await apiFetch({
          path: `${base_uri}/export`,
        });
        return {
          type: "SET_EXPORTS",
          payload,
        };
      },
      async getImportProfiles() {
        const payload = await apiFetch({
          path: `${base_uri}/import/profile`,
        });
        return {
          type: "SET_IMPORTPROFILES",
          payload,
        };
      },
      async getImports() {
        const payload = await apiFetch({
          path: `${base_uri}/import`,
        });
        return {
          type: "SET_IMPORTS",
          payload,
        };
      },
    },
  });

  data.register(store);
}

export { KEY };
