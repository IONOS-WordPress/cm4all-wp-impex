import element from "@wordpress/element";
import Debug from "@cm4all-impex/debug";

import Screen from "./components/screen.mjs";
import { ScreenContextProvider } from "./components/screen-context.mjs";

import "./wp.impex.dashboard.scss";

const debug = Debug.default("wp.impex.dashboard");
debug("loaded");

// render impex dashboard only if not error notice (=> wordpress importer plugin is not installed) is shown
if (!document.querySelector(".notice.notice-error")) {
  element.render(
    <ScreenContextProvider>
      <Screen />
    </ScreenContextProvider>,
    document.getElementById("cm4all_wp_impex_wp_admin_dashboard")
  );
}
