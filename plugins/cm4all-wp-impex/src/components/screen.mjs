import element from "@wordpress/element";
import components from "@wordpress/components";
import data from "@wordpress/data";
import { __, sprintf } from "@wordpress/i18n";
import Debug from "@cm4all-impex/debug";

import Store from "@cm4all-impex/store";

import Export from "./export.mjs";
import Import from "./import.mjs";

import ExportProfileSelector from "./export-profile-selector.mjs";
import ImportProfileSelector from "./import-profile-selector.mjs";

const debug = Debug.default("wp.impex.dashboard.screen");
debug("loaded");

const isFileystemApiAvailable =
  typeof window.showDirectoryPicker === "function";

function AdvancedTab() {
  return (
    <components.Flex direction="row" align="top">
      <components.FlexItem isBlock>
        <Export />
      </components.FlexItem>

      <components.FlexItem isBlock>
        <Import />
      </components.FlexItem>
    </components.Flex>
  );
}

function SimpleTab() {
  const { exportProfiles, importProfiles } = data.useSelect((select) => {
    const store = select(Store.KEY);
    return {
      exportProfiles: store.getExportProfiles(),
      importProfiles: store.getImportProfiles(),
    };
  });

  const [exportProfile, setExportProfile] = element.useState();
  element.useEffect(() => {
    if (exportProfiles.length === 1) {
      setExportProfile(exportProfiles[0]);
    }
  }, [exportProfiles]);

  const [importProfile, setImportProfile] = element.useState();
  element.useEffect(() => {
    if (importProfiles.length === 1) {
      setImportProfile(importProfiles[0]);
    }
  }, [importProfiles]);

  debug({ exportProfile, importProfile });

  return (
    <components.Flex direction="row" align="top">
      <components.FlexItem isBlock>
        <components.Panel className="export">
          <components.PanelBody opened className="create-export-form">
            <ExportProfileSelector
              value={exportProfile}
              onChange={setExportProfile}
            />
            <components.Button variant="primary" disabled={!exportProfile}>
              Export
            </components.Button>
          </components.PanelBody>
        </components.Panel>
      </components.FlexItem>

      <components.FlexItem isBlock>
        <components.Panel className="export">
          <components.PanelBody opened className="upload-import-form">
            <ImportProfileSelector
              value={importProfile}
              onChange={setImportProfile}
            />
            <components.Button variant="primary" disabled={!importProfile}>
              Import
            </components.Button>
          </components.PanelBody>
        </components.Panel>
      </components.FlexItem>
    </components.Flex>
  );
}

export default function () {
  return (
    <div>
      <h1>{__("ImpEx", "cm4all-wp-impex")}</h1>

      <components.SlotFillProvider>
        <components.TabPanel
          tabs={[
            {
              name: "basic",
              title: __("Basic", "cm4all-wp-impex"),
            },
            {
              name: "advanced",
              title: __("Advanced", "cm4all-wp-impex"),
            },
          ]}
        >
          {(tab) => (tab.name === "advanced" ? <AdvancedTab /> : <SimpleTab />)}
        </components.TabPanel>

        <components.Slot name="progress" />

        {!isFileystemApiAvailable && (
          <components.Modal
            title="Ouch - your browser does not support the File System Access API :-("
            isDismissible={false}
          >
            <p>
              ImpEx Import / Export requires a browser implementing the{" "}
              <a href="https://web.dev/file-system-access/">
                File System Access API
              </a>
              .
            </p>
            <p>
              Currently only Chromium based browsers like Chrome, Chromium, MS
              Edge are known to support this feature.
            </p>
            <p>
              See{" "}
              <a href="https://caniuse.com/mdn-api_window_showdirectorypicker">
                here
              </a>{" "}
              to find the latest list of browsers supporting the{" "}
              <a href="https://web.dev/file-system-access/">
                File System Access API
              </a>{" "}
              feature.
            </p>
          </components.Modal>
        )}
      </components.SlotFillProvider>
    </div>
  );
}
