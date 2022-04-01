import tape from "tape";

import ImpexTransform from "../src/impex-transform.js";
const impexTransform = ImpexTransform({ __verbose: true });

tape.onFinish(() => {
  impexTransform.cleanup();
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
            arg.call(t, t, impexTransform);
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

  wrapper.__proto__ = test;

  return wrapper;
};

function escapeRegex(string) {
  return string.replace(/[-\/\\^$*+?.()|[\]{}]/g, "\\$&");
}

export default function (...args) {
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
