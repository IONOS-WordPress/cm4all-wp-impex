# FAQ

## Why is the ImpEx UI not (yet) working in Firefox / Brave ? 

The ImpEx UI uses the [File System Access API](https://web.dev/file-system-access/) to export and import data to the local filesystem. This browser feature is mandatory.

Right now Chromium based browsers (except of Brave who disabled this feature) and Safari supporting the [File System Access API](https://web.dev/file-system-access/). 

> As of now, it's unclear if and when Firefox will support the [File System Access API](https://web.dev/file-system-access/). 

As an alternative you can use the [ImpEx CLI](./impex-cli.html) to trigger import/export operations on the commandline.