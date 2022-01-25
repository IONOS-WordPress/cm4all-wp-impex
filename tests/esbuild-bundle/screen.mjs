import React from "react";
import ReactDom from "react-dom";
import components from "@wordpress/components";
import domReady from "@wordpress/dom-ready";
import Debug from "@cm4all/debug";
import { __ } from "@wordpress/i18n";
import Foo from "./components/foo.mjs";

console.log("environment : %s", process.env.NODE_ENV);

import "./screen.scss";

const debug = Debug("krass");

function Log(props) {
  return (
    <div>
      <Foo title="Hey" />
      <components.Button>{__(props.text)}</components.Button>;
    </div>
  );
}

console.log("name = %s, foo = %s", Log?.name, Log?.foo);

let r = Log?.foo ?? "bert";

r ||= "hilde";

ReactDom.render(<Log huhu="haha" />);

domReady(() => {
  console.log("huhu !");
});
