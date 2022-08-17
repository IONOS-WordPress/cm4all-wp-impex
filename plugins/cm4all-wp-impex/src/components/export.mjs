import element from "@wordpress/element";
import components from "@wordpress/components";
import apiFetch from "@wordpress/api-fetch";
import url from "@wordpress/url";
import data from "@wordpress/data";
import { __, sprintf } from "@wordpress/i18n";
import Debug from "@cm4all-impex/debug";
import { edit, cancelCircleFilled, download } from "@wordpress/icons";
import ExportProfileSelector from "./export-profile-selector.mjs";

import RenameModal from "./rename-modal.mjs";
import DeleteModal from "./delete-modal.mjs";
import useScreenContext from "./screen-context.mjs";

import Store from "@cm4all-impex/store";

const debug = Debug.default("wp.impex.dashboard.export");
debug("loaded");

//const { __, sprintf } = i18n;

export default function Export() {
  const { settings, exportProfiles, exports } = data.useSelect((select) => {
    const store = select(Store.KEY);
    return {
      settings: store.getSettings(),
      exportProfiles: store.getExportProfiles(),
      exports: store.getExports(),
    };
  });

  const [exportProfile, setExportProfile] = element.useState();

  element.useEffect(() => {
    if (exportProfiles.length === 1) {
      setExportProfile(exportProfiles[0]);
    }
  }, [exportProfiles]);

  const {
    createExport: _createExport,
    updateExport,
    deleteExport,
  } = data.useDispatch(Store.KEY /*, []*/);

  const [modal, setModal] = element.useState(null);
  const [progress, setProgress] = element.useState(null);

  const screenContext = useScreenContext();

  // debug({ exportProfile, exportProfiles });
  const { currentUser } = data.useSelect((select) => ({
    currentUser: select("core").getCurrentUser(),
  }));

  const createExport = async () => {
    const site_url = new URL(settings["site_url"]);

    const date = screenContext.currentDateString();
    const name = `${site_url.hostname}-${exportProfile.name}-${date}`;
    const description = `Export '${exportProfile.name}' created by user '${currentUser.name}' at ${date}`;

    setProgress({
      component: (
        <components.Modal
          title={__("Creating snapshot", "cm4all-wp-impex")}
          onRequestClose={() => {}}
          overlayClassName="blocking"
        >
          value
          <progress indeterminate="true"></progress>
        </components.Modal>
      ),
    });
    await _createExport(exportProfile, name, description);
    setProgress();
  };

  const onDownloadExport = async (_export) => {
    let _exportFolderName = null;
    // showDirectoryPicker will throw a DOMExxception in case the user pressed cancel
    try {
      // colons need to be replaced otherwise showDirectoryPicker will fail
      _exportFolderName = screenContext.normalizeFilename(_export.name);
    } catch {
      return;
    }

    // see https://web.dev/file-system-access/
    const exportsDirHandle = await window.showDirectoryPicker({
      // You can suggest a default start directory by passing a startIn property to the showSaveFilePicker
      startIn: "downloads",
      mode: "readwrite",
      // If an id is specified, the file picker implementation will remember a separate last-used directory for pickers with that same id.
      id: "impex-dir",
    });

    const exportDirHandle = await exportsDirHandle.getDirectoryHandle(
      _exportFolderName,
      {
        create: true,
      }
    );
    debug("download export %o", _export);

    const path = `${settings.base_uri}/export/${_export.id}/slice`;

    setProgress({
      component: (
        <components.Modal
          title={__("Downloading snapshot", "cm4all-wp-impex")}
          onRequestClose={() => {}}
          overlayClassName="blocking"
        >
          <progress indeterminate="true"></progress>
        </components.Modal>
      ),
    });

    const initialResponse = await apiFetch({
      path,
      // parse: false is needed to geta access to the headers
      parse: false,
    });

    const x_wp_total = Number.parseInt(
      initialResponse.headers.get("X-WP-Total"),
      10
    );
    const x_wp_total_pages = Number.parseInt(
      initialResponse.headers.get("X-WP-TotalPages")
    );

    const sliceChunks = [
      screenContext.saveSlicesChunk(exportDirHandle, initialResponse.json(), 1),
    ];
    for (let chunk = 2; chunk <= x_wp_total_pages; chunk++) {
      sliceChunks.push(
        screenContext.saveSlicesChunk(
          exportDirHandle,
          apiFetch({
            path: url.addQueryArgs(path, { page: chunk }),
          }),
          chunk
        )
      );
    }

    await Promise.all(sliceChunks);
    setProgress(null);
  };

  return (
    <>
      <components.Panel
        className="export"
        header={__("Export", "cm4all-wp-impex")}
      >
        <components.PanelBody
          title={__("Create snapshot", "cm4all-wp-impex")}
          opened
          className="create-export-form"
        >
          <ExportProfileSelector
            value={exportProfile}
            onChange={setExportProfile}
          />

          <components.Button
            isPrimary
            onClick={createExport}
            disabled={!exportProfile}
          >
            {__("Create Snapshot", "cm4all-wp-impex")}
          </components.Button>
        </components.PanelBody>
        {exports.map((_, index) => (
          <components.PanelBody
            key={_.id}
            title={_.name}
            initialOpen={index === 0}
          >
            <components.PanelRow>
              <components.Button
                isSecondary
                onClick={() => onDownloadExport(_)}
                icon={download}
              >
                {__("Download snapshot", "cm4all-wp-impex")}
              </components.Button>
              <components.DropdownMenu
                // icon={moreVertical}
                label={__(
                  "Additional actions on this export",
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
                          title: __("Edit export", "cm4all-wp-impex"),
                          async doSave(data) {
                            await updateExport(_.id, data);
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
                          title: __("Delete export", "cm4all-wp-impex"),
                          children: (
                            <>
                              {__("Are you really sure to delete export")}
                              <code>{_.name}</code>?
                            </>
                          ),
                          async doDelete() {
                            await deleteExport(_.id);
                          },
                        },
                      }),
                  },
                ]}
              />
            </components.PanelRow>
            <components.PanelRow>
              <pre>{JSON.stringify(_, null, "  ")}</pre>
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
