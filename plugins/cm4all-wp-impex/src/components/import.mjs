import element from "@wordpress/element";
import components from "@wordpress/components";
import data from "@wordpress/data";
import url from "@wordpress/url";
import { __, sprintf } from "@wordpress/i18n";
import hooks from "@wordpress/hooks";
import ImpexFilters from "@cm4all-impex/filters";
import Debug from "@cm4all-impex/debug";
import apiFetch from "@wordpress/api-fetch";
import {
  edit,
  cancelCircleFilled,
  upload,
  cloudUpload,
} from "@wordpress/icons";
import RenameModal from "./rename-modal.mjs";
import DeleteModal from "./delete-modal.mjs";
import useScreenContext from "./screen-context.mjs";
import ImportProfileSelector from "./import-profile-selector.mjs";

import Store from "@cm4all-impex/store";

const debug = Debug.default("wp.impex.dashboard.import");
debug("loaded");

export default function Import() {
  // @TODO: add dragn drop support for uploading an export ?
  // https://medium.com/@650egor/simple-drag-and-drop-file-upload-in-react-2cb409d88929
  // https://developer.mozilla.org/en-US/docs/Web/API/DataTransferItem/getAsFileSystemHandle
  // https://wicg.github.io/file-system-access/#drag-and-drop

  const { currentUser } = data.useSelect((select) => ({
    currentUser: select("core").getCurrentUser(),
  }));

  const { settings, importProfiles, imports } = data.useSelect((select) => {
    const store = select(Store.KEY);
    return {
      settings: store.getSettings(),
      importProfiles: store.getImportProfiles(),
      imports: store.getImports(),
    };
  });

  const { createImport, updateImport, deleteImport, consumeImport } =
    data.useDispatch(Store.KEY /*, []*/);

  const [modal, setModal] = element.useState(null);
  const [progress, setProgress] = element.useState(null);

  const screenContext = useScreenContext();

  const [importProfile, setImportProfile] = element.useState();

  element.useEffect(() => {
    if (importProfiles.length === 1) {
      setImportProfile(importProfiles[0]);
    }
  }, [importProfiles]);

  const onConsumeImport = async (_import) => {
    debug("onConsumeImport(%o)", _import);

    setProgress({
      component: (
        <components.Modal
          title={__("Importing data into WordPress ...", "cm4all-wp-impex")}
          onRequestClose={() => {}}
          overlayClassName="blocking"
        >
          <progress indeterminate="true"></progress>
        </components.Modal>
      ),
    });

    await consumeImport(_import.id, {}, null, null);
    setProgress();
  };

  const _getSliceFiles = async (importDirHandle) => {
    const slices = [];
    for await (let sliceChunkDirectoryHandle of importDirHandle.values()) {
      if (sliceChunkDirectoryHandle.kind === "directory") {
        for await (let sliceFileHandle of sliceChunkDirectoryHandle.values()) {
          if (
            sliceFileHandle.kind === "file" &&
            sliceFileHandle.name.match(/^slice-\d+\.json$/)
          ) {
            slices.push({
              fileHandle: sliceFileHandle,
              dirHandle: sliceChunkDirectoryHandle,
            });
          }
        }
      }
    }

    slices.sort((l, r) => {
      const cval = l.dirHandle.name.localeCompare(r.dirHandle.name);

      if (cval === 0) {
        return l.fileHandle.name.localeCompare(r.fileHandle.name);
      }

      return cval;
    });

    return slices;
  };

  const onUpload = async () => {
    let importDirHandle = null;
    // showDirectoryPicker will throw a DOMExxception in case the user pressed cancel
    try {
      // see https://web.dev/file-system-access/
      importDirHandle = await window.showDirectoryPicker({
        // You can suggest a default start directory by passing a startIn property to the showSaveFilePicker
        startIn: "downloads",
      });
    } catch {
      return;
    }

    debug("upload export %o", importDirHandle.name);

    const date = screenContext.currentDateString();
    const name = importDirHandle.name;
    const description = `Import '${importDirHandle.name}' created by user '${currentUser.name}' at ${date}`;

    setProgress({
      component: (
        <components.Modal
          title={__("Uploading snapshot", "cm4all-wp-impex")}
          onRequestClose={() => {}}
          overlayClassName="blocking"
        >
          <progress indeterminate="true"></progress>
        </components.Modal>
      ),
    });

    const _import = (await createImport(name, description, importProfile, []))
      .payload;

    const sliceFiles = await _getSliceFiles(importDirHandle);

    const path = `${settings.base_uri}/import/${_import.id}/slice`;

    const sliceUploads = sliceFiles.map(
      async ({ fileHandle, dirHandle }, position) => {
        const formData = new FormData();
        let slice = JSON.parse(await (await fileHandle.getFile()).text());

        slice = await hooks.applyFilters(
          ImpexFilters.SLICE_REST_UPLOAD,
          ImpexFilters.NAMESPACE,
          slice,
          parseInt(fileHandle.name.match(/^slice-(\d+)\.json$/)[1]),
          dirHandle,
          formData
        );

        if (slice) {
          debug("upload %o", {
            position,
            file: fileHandle.name,
            dir: dirHandle.name,
          });
          formData.append("slice", JSON.stringify(slice, null, "  "));

          return apiFetch({
            method: "POST",
            path: url.addQueryArgs(path, { position }),
            body: formData,

            parse: false,
          });
        }
      }
    );

    const finished = await Promise.all(sliceUploads);
    setProgress();
  };

  return (
    <>
      <components.Panel
        className="import"
        header={__("Import", "cm4all-wp-impex")}
      >
        <components.PanelBody
          title={__("Upload snapshot to WordPress", "cm4all-wp-impex")}
          opened
          className="upload-import-form"
        >
          <ImportProfileSelector
            value={importProfile}
            onChange={setImportProfile}
          />
          <components.Button
            isPrimary
            onClick={onUpload}
            icon={upload}
            disabled={!importProfile}
          >
            {__("Upload snapshot", "cm4all-wp-impex")}
          </components.Button>
        </components.PanelBody>
        {imports.map((_, index) => (
          <components.PanelBody
            key={_.id}
            title={_.name}
            initialOpen={index === 0}
          >
            <components.PanelRow>
              <components.Button
                isDestructive
                isPrimary
                onClick={() => onConsumeImport(_)}
                icon={cloudUpload}
              >
                {__("Import uploaded snapshot", "cm4all-wp-impex")}
              </components.Button>
              <components.DropdownMenu
                // icon={moreVertical}
                label={__(
                  "Additional actions on this import",
                  "cm4all-wp-impex"
                )}
                controls={[
                  {
                    title: __("Edit", "cm4all-wp-impex"),
                    icon: edit,
                    onClick: () =>
                      setModal({
                        component: RenameModal,
                        props: {
                          title: __("Edit import snapshot", "cm4all-wp-impex"),
                          async doSave(data) {
                            await updateImport(_.id, data);
                          },
                          item: _,
                        },
                      }),
                  },
                  {
                    title: __("Delete", "cm4all-wp-impex"),
                    icon: cancelCircleFilled,
                    onClick: () =>
                      setModal({
                        component: DeleteModal,
                        props: {
                          title: __("Delete import", "cm4all-wp-impex"),
                          children: (
                            <>
                              {__(
                                "Are you really sure to delete import",
                                "cm4all-wp-impex"
                              )}
                              <code>{_.name}</code>?
                            </>
                          ),
                          async doDelete() {
                            await deleteImport(_.id);
                          },
                        },
                      }),
                  },
                ]}
              />
            </components.PanelRow>
            <components.PanelRow>
              <pre style={{ overflow: "auto" }}>
                {JSON.stringify(_, null, "  ")}
              </pre>
            </components.PanelRow>
          </components.PanelBody>
        ))}
      </components.Panel>
      {modal && <modal.component {...modal.props} onRequestClose={setModal} />}

      {progress && (
        <components.Fill name="progress" onRequestClose={() => {}}>
          {progress.component}
        </components.Fill>
      )}
    </>
  );
}
