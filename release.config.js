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
      "@semantic-release/npm",
      {
        npmPublish: true,
        pkgRoot: "packages/@cm4all-wp-impex/generator",
      },
    ],
    [
      "@semantic-release/exec",
      {
        prepareCmd:
          "sed -i 's/Version:\\(.*\\)/Version: ${nextRelease.version}/' plugins/*/plugin.php && make dist",
        publishCmd: "SVN_TAG='${nextRelease.version}' make deploy-to-wordpress",
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
          "packages/@cm4all-wp-impex/generator/package.json",
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
            path: "dist/cm4all-wp-impex-cli-v*.zip",
            label: "Impex CLI",
          },
          {
            path: "dist/cm4all-wp-impex-example-v*.zip",
            label: "optional third-party ImpEx integration example plugin",
          },
          {
            path: "dist/cm4all-wp-impex-gh-pages-v*.zip",
            label: "documentation for offline usage",
          },
          {
            path: "dist/cm4all-wp-impex/readme.txt",
            label: "Impex plugin readme for the WordPress plugin directory",
          },
        ],
      },
    ],
  ],
  preset: "conventionalCommits",
  tagFormat: "${version}",
};
