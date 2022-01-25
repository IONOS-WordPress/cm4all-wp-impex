import element from "@wordpress/element";
import components from "@wordpress/components";
import { __, sprintf } from "@wordpress/i18n";
import Debug from "@cm4all-impex/debug";

import Export from "./export.mjs";
import Import from "./import.mjs";

const debug = Debug.default("wp.impex.dashboard.screen");
debug("loaded");

const isFileystemApiAvailable =
  typeof window.showDirectoryPicker === "function";

export default function () {
  return (
    <div>
      <h1>{__("Impex", "cm4all-wp-impex")}</h1>

      <components.SlotFillProvider>
        <components.Flex direction="row" align="top">
          <components.FlexItem isBlock>
            <Export />
          </components.FlexItem>

          <components.FlexItem isBlock>
            <Import />
          </components.FlexItem>
        </components.Flex>

        <components.Slot name="progress" />

        {!isFileystemApiAvailable && (
          <components.Modal
            title="Ouch - your browser does not support the File System Access API :-("
            onRequestClose={() => {}}
          >
            <p>
              Impex Import / Export requires a browser implementing the{" "}
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
