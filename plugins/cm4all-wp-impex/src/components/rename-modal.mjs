import element from "@wordpress/element";
import components from "@wordpress/components";
import data from "@wordpress/data";
import { __, sprintf } from "@wordpress/i18n";
import { edit } from "@wordpress/icons";

export default function RenameModal({ title, doSave, item, onRequestClose }) {
  const [name, setName] = element.useState(item.name);
  const [description, setDescription] = element.useState(item.description);

  const onSave = async () => {
    await doSave({ name, description });

    onRequestClose();
  };

  return (
    <components.Modal
      title={title}
      icon={edit}
      onRequestClose={() => onRequestClose()}
    >
      <components.TextControl
        label={__("Name", "cm4all-wp-impex")}
        help={__("Name should be short and human readable", "cm4all-wp-impex")}
        value={name}
        onChange={setName}
      />

      <components.TextareaControl
        label={__("Description", "cm4all-wp-impex")}
        help={__(
          "Description may contain more expressive information describing the item",
          "cm4all-wp-impex",
        )}
        value={description}
        onChange={setDescription}
      />

      <components.Button
        variant="primary"
        onClick={onSave}
        disabled={name === item.name && description === item.description}
      >
        Save
      </components.Button>
    </components.Modal>
  );
}
