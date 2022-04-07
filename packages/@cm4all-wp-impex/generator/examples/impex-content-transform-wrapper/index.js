#!/usr/bin/env node

import Transformer from "../../src/impex-content-transform.js";
Transformer.setup();
console.log(
  Transformer.transform(`<!DOCTYPE html>
<body>
  <img src="http://localhost:8889/wp-content/uploads/2022/03/greysen-johnson-unsplash.jpg" title="Fly_fishing">     
</body>
</html>`)
);
// must be called at the end since jsdom adds timer to nodejs so node will never exit otherwise
process.exit(0);
