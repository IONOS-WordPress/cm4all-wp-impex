import tape from "tape";

tape.onFinish(() =>
  process.nextTick(() => {
    process.exit(0);
  })
);

export default tape;
