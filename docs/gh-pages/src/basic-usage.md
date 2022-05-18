<!-- toc -->

# Basic usage

ImpEx separates Import and Export in 2 steps using the ImpEx Dashboard screen.

![ImpEx Dashboard screen](./impex-screenshot.png)

## Export

- You need to create a snapshot first.

  The snapshot will contain the current data (defined by the used [ImpEx profile](explanation-of-terms.html#profile)) of the WordPress instance.

- Now you can download the snapshot to your local machine.

## Import

- Upload snapshot from your local machine to the WordPress instance.

  Uploading does not modify your current WordPress contents.

- If you now import the snapshot, the contents of your WordPress instance will be updated with the snapshot data.

> Using the [impex-cli](./impex-cli.html) command line tool will combine both steps in one. You just export or import a local directory containing the snapshot in [ImpEx Export Format](./migrating-content.html#preparation). [impex-cli](./impex-cli.html) manages the temporary snapshot handling for you.
