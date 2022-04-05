import tape from "tape";

tape.onFinish(() =>
  process.nextTick(() => {
    process.exit(0);
  })
);

function escapeRegex(string) {
  return string.replace(/[-\/\\^$*+?.()|[\]{}]/g, "\\$&");
}

export function includes(test, haystack, needle) {
  return test.match(
    haystack,
    new RegExp(escapeRegex(needle)),
    `${JSON.stringify(haystack)} should include ${JSON.stringify(needle)}`
  );
}

export function doesNotInclude(test, haystack, needle) {
  return test.doesNotMatch(
    haystack,
    new RegExp(escapeRegex(needle)),
    `${JSON.stringify(haystack)} should not include ${JSON.stringify(needle)}`
  );
}

export default tape;
