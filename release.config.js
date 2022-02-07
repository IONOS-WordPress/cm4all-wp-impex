// release.config.js
module.exports = {
  branches: [
    "+([0-9])?(.{+([0-9]),x}).x",
    "main",
    "next",
    "next-major",
    { name: "beta", prerelease: true },
    { name: "alpha", prerelease: true },
  ],
  verifyConditions: ["@semantic-release/changelog", "@semantic-release/git"],
  plugins: [
    "@semantic-release/commit-analyzer",
    "@semantic-release/release-notes-generator",
    "@semantic-release/changelog",
    [
      "@semantic-release/npm",
      {
        // tarballDir: './tmp',
        npmPublish: false,
      },
    ],
    [
      "@semantic-release/exec",
      {
        prepareCmd:
          "sed -i 's/Version:\\(.*\\)/Version: ${nextRelease.version}/' plugins/*/plugin.php && make dist",
        publishCmd:
          "SVN_TAG='${nextRelease.version}' SVN_USERNAME='${process.env.SVN_USERNAME}' SVN_PASSWORD='${process.env.SVN_PASSWORD}' make deploy-to-wordpress",
      },
    ],
    [
      "@semantic-release/git",
      {
        assets: [
          "CHANGELOG.md",
          "package-lock.json",
          "package.json",
          "plugins/cm4all-wp-impex/plugin.php",
          "plugins/cm4all-wp-impex-example/plugin.php",
        ],
        message:
          "chore(release): ${nextRelease.version} [skip release]\n\n${nextRelease.notes}",
      },
    ],
    [
      "@semantic-release/github",
      {
        assets: [
          {
            path: "dist/cm4all-wp-impex-v*.zip",
            label: "Impex plugin",
          },
          {
            path: "dist/cm4all-wp-impex-php*-v*.zip",
            label: "Impex plugin for PHP 7.4",
          },
          {
            path: "dist/cm4all-wp-impex-example-v*.zip",
            label: "optional third-party Impex integration example plugin",
          },
          {
            path: "dist/cm4all-wp-impex-gh-pages-v*.zip",
            label: "documentation for offline usage",
          },
          {
            path: "dist/cm4all-wp-impex/readme.txt",
            label: "Impex plugin readme for the Wordpress plugin directory",
          },
        ],
      },
    ],
  ],
  preset: "conventionalCommits",
  tagFormat: "${version}",
};
