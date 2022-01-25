import React from "react";
import components from "@wordpress/components";
import { close } from "@wordpress/icons";
import { __ } from "@wordpress/i18n";

export default function Foo({ title }) {
  return <components.Icon icon={close}>{__(title)}</components.Icon>;
}
