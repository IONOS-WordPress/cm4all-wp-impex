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

  const [cleanupContent, setCleanupContent] = element.useState(true);
  const [cleanupMedia, setCleanupMedia] = element.useState(true);

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

    await consumeImport(_import.id, {
        // @see PHP class ImpexExport::OPTION_CLEANUP_CONTENTS
        'impex-import-option-cleanup_contents' : cleanupContent,
      },
      null,
      null
    );

    setProgress();
  };

  const onUpload = async () => {
    let importDirHandle = null;
    // showDirectoryPicker will throw a DOMException in case the user pressed cancel
    try {
      // see https://web.dev/file-system-access/
      importDirHandle = await window.showDirectoryPicker({
        // You can suggest a default start directory by passing a startIn property to the showSaveFilePicker
        startIn: "downloads",
        id: "impex-import-dir",
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

    const _import = (await createImport(name, description, importProfile, {}))
      .payload;

    const sliceFiles = await screenContext._getSliceFilesToImport(importDirHandle);

    const finished = await screenContext._uploadSlices(_import, sliceFiles);
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
        <components.PanelBody
          title={__("Import options", "cm4all-wp-impex")}
          opened
          className="import-options-form"
        >
          <components.ToggleControl
            help={ cleanupContent ? __("Clean up existing post, page, block pattern, nav_menu an reusable block items", "cm4all-wp-impex") : __("Keep existing post, page, block pattern, nav_menu an reusable block items.", "cm4all-wp-impex") }
            checked={ cleanupContent }
            onChange={ setCleanupContent }
            label={__("Remove existing content before importing uploaded snapshot", "cm4all-wp-impex")}
          >
          </components.ToggleControl>
          <components.ToggleControl
                help={ cleanupMedia ? __("Clean up existing media like images and videos (located at WordPress uploads)", "cm4all-wp-impex") : __("Keep existing media items. Media might be partly overwritten by export", "cm4all-wp-impex") }
                checked={ cleanupMedia }
                disabled={ !imports.length }
                onChange={ setCleanupMedia }
                label={__("Remove existing media before import", "cm4all-wp-impex")}
              >
          </components.ToggleControl>
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
