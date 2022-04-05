import tape from "tape";

import TransformerFactory from "../src/impex-transform.js";
const transformer = TransformerFactory({ verbose: false });

tape.onFinish(() => {
  transformer.cleanup();
  setTimeout(() => process.exit(0), 1000);
});

const customizeTape = (test, configuration) => {
  const wrapper = (...args) => {
    args = args.map((arg) => {
      if (typeof arg === "function") {
        return (t) => {
          Object.entries(configuration).forEach(([option, value]) => {
            if (typeof value === "function") {
              value = value.bind(t);
            }
            t[option] = value;
          });

          t.test = customizeTape(t.test, configuration);

          try {
            arg.call(t, t, transformer);
            t.end();
          } catch ($ex) {
            throw $ex;
          }
        };
      } else {
        return arg;
      }
    });
    test(...args);
  };

  Object.setPrototypeOf(wrapper, test);

  return wrapper;
};

function escapeRegex(string) {
  return string.replace(/[-\/\\^$*+?.()|[\]{}]/g, "\\$&");
}

export default function Test(...args) {
  const test = customizeTape(tape, {
    includes(haystack, needle) {
      return this.match(
        haystack,
        new RegExp(escapeRegex(needle)),
        `${JSON.stringify(haystack)} should include ${JSON.stringify(needle)}`
      );
    },
    doesNotInclude(haystack, needle) {
      return this.doesNotMatch(
        haystack,
        new RegExp(escapeRegex(needle)),
        `${JSON.stringify(haystack)} should not include ${JSON.stringify(
          needle
        )}`
      );
    },
  });

  return test(...args);
}

Test.only = tape.only;
