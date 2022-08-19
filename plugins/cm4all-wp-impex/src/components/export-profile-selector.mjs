import components from "@wordpress/components";
import data from "@wordpress/data";
import element from "@wordpress/element";
import { __, sprintf } from "@wordpress/i18n";
import Store from "@cm4all-impex/store";

export default function ExportProfileSelector({ value, onChange }) {
  const { exportProfiles } = data.useSelect((select) => {
    const store = select(Store.KEY);
    return {
      exportProfiles: store.getExportProfiles(),
    };
  });

  const exportProfileSelectRef = element.useRef();

  const setExportProfile = (exportProfileName = null) => {
    const exportProfile = exportProfiles.find(
      (_) => _.name === exportProfileName
    );
    onChange(exportProfile);

    if(exportProfileSelectRef.current) {
      if (!exportProfile) {
        exportProfileSelectRef.current.selectedIndex = 0;
      }

      exportProfileSelectRef.current.title = exportProfile?.description || "";
    }
  };

  element.useEffect(() => {
    setExportProfile(value?.name);
  }, [exportProfiles]);

  const options = [...exportProfiles];
  
  if(exportProfiles.length > 1) {
    options.unshift({
      name: exportProfiles.length
      ? __("Select an export profile", "cm4all-wp-impex")
      : __("No export profiles found"),
      disabled: true,
    });
  }

  return (
    <components.SelectControl
      ref={exportProfileSelectRef}
      disabled={!exportProfiles.length}
      value={value?.name}
      onChange={setExportProfile}
      options={
        options
        .map((_) => ({
          value: _.disabled ? undefined : _.name,
          label: _.name,
          disabled: _.disabled,
        }))
      }
      label={__("Export Profile:", "cm4all-wp-impex")}
      help={__(
        "Export profiles define which WordPress data should be extracted",
        "cm4all-wp-impex"
      )}
    ></components.SelectControl>
  );
}
