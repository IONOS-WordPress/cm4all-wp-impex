import components from "@wordpress/components";
import { cancelCircleFilled } from "@wordpress/icons";

import React from "React";

export default function ({ title, doDelete, onRequestClose, children }) {
  const onDelete = async () => {
    await doDelete();

    onRequestClose();
  };

  return (
    <components.Modal
      title={title}
      icon={cancelCircleFilled}
      onRequestClose={() => onRequestClose()}
    >
      <p>{children}</p>
      <components.Button variant="primary" isDestructive onClick={onDelete}>
        Delete
      </components.Button>
    </components.Modal>
  );
}
