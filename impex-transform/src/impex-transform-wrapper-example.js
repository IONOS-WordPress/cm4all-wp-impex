#!/usr/bin/env node

import ImpexTransform from "./impex-transform.js";

const impexTransform = new ImpexTransform({ verbose: true });

impexTransform.transform(`<!DOCTYPE html>
<body>
  <img src="http://localhost:8889/wp-content/uploads/2022/03/greysen-johnson-unsplash.jpg" title="Fly_fishing">     
</body>
</html>`);

impexTransform.cleanup();
process.exit(0);
