import components from "@wordpress/components";
import data from "@wordpress/data";
import element from "@wordpress/element";
import Store from "@cm4all-impex/store";
import { __, sprintf } from "@wordpress/i18n";

export default function ImportProfileSelector({ value, onChange }) {
  const { importProfiles } = data.useSelect((select) => {
    const store = select(Store.KEY);
    return {
      importProfiles: store.getImportProfiles(),
    };
  });

  const importProfileSelectRef = element.useRef();

  const setImportProfile = (importProfileName = null) => {
    const importProfile = importProfiles.find(
      (_) => _.name === importProfileName
    );
    onChange(importProfile);

    if(importProfileSelectRef.current) {
      if (!importProfile) {
        importProfileSelectRef.current.selectedIndex = 0;
      }

      importProfileSelectRef.current.title = importProfile?.description || "";
    }
  };

  element.useEffect(() => {
    setImportProfile(value?.name);
  }, [importProfiles]);

  element.useEffect(() => {
    for(const option of importProfileSelectRef.current?.options ?? []) {
      if(!option.disabled) {
        option.title = importProfiles.find(_ => _.name === option.value)?.description;
      }
    }
  }, [importProfiles]);

  const options = [...importProfiles];

  if(importProfiles.length > 1) {
    options.unshift(
      {
        name: importProfiles.length
          ? __("Select an import profile", "cm4all-wp-impex")
          : __("No import profiles found"),
        disabled: true,
      });
  }

  return (
    <wp.components.SelectControl
      ref={importProfileSelectRef}
      disabled={!importProfiles.length}
      label={__("Import Profile:", "cm4all-wp-impex")}
      value={value?.name}
      onChange={setImportProfile}
      options={
        options
        .map((_) => ({
          value: _.disabled ? undefined : _.name,
          label: _.name,
          disabled: _.disabled,
        }))
      }
      help={__(
        "Import profiles define which WordPress data should be consumed",
        "cm4all-wp-impex"
      )}
    ></wp.components.SelectControl>
  );
}
