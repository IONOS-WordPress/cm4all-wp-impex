# FAQ

## Why is the ImpEx UI not (yet) working in Firefox / Brave ? 

The ImpEx UI uses the [File System Access API](https://web.dev/file-system-access/) to export and import data to the local filesystem. This browser feature is mandatory.

Right now Chromium based browsers (except of Brave who disabled this feature) and Safari supporting the [File System Access API](https://web.dev/file-system-access/). 

> As of now, it's unclear if and when Firefox will support the [File System Access API](https://web.dev/file-system-access/). 

As an alternative you can use the [ImpEx CLI](./impex-cli.html) to trigger import/export operations on the commandline.

## `Ouch -your browser does not support the Crypto API`

Same same as with the [File System Access API](https://web.dev/file-system-access/) API ... depending on your browser version the Crypto API feature we use (`window.crypto.randomUUID`) may not be supported by your browser. 

As of now, [all "green" browser support it](https://developer.mozilla.org/en-US/docs/Web/API/Crypto/randomUUID). 

But if you use an older version of a browser it might happen that Impex will not work since the browser feature is not available in your browser.