## [1.6.0](https://github.com/IONOS-WordPress/cm4all-wp-impex/compare/1.5.11...1.6.0) (2024-02-12)


### Features

* new option "cleanup media" added ([6e351ad](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/6e351ad7f0b2fc2c964b90856fb05cee543c1252))


### Bug Fixes

* made theme mods access safe ([c50f841](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/c50f84140b6f59fd2e75a47ceb4d256a9ae17a80))
* updated rector configuration to be rector 1.0 compatible ([246b60d](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/246b60dc4cea5077d87fff7aac0c505a2510ab5e))

## [1.5.11](https://github.com/IONOS-WordPress/cm4all-wp-impex/compare/1.5.10...1.5.11) (2024-01-11)


### Bug Fixes

* final fix for menu id reassociation ([a752c5e](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/a752c5e0021e7d88499bf29daa2c9d6ffcdd141b))

## [1.5.10](https://github.com/IONOS-WordPress/cm4all-wp-impex/compare/1.5.9...1.5.10) (2024-01-11)


### Bug Fixes

* added wp_attachment_pages_enabled option to export ([bf39694](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/bf39694920d725a1eb12e64b9a3112d98e43210b))
* fixed launch.json generation for successful 3rd-party plugin debugging ([5f07e44](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/5f07e44101fa88682a410fc6135ebc9aa7c8ba92))
* **import:** fix mysql/mariadb charset/collation issue ([fb5543f](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/fb5543f3a93c5879e697d788ac8e4619e80b1e48))
* importing mysql db tables into mariadb/wp-env ([a1a21a8](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/a1a21a8680d877a4ac5de8e13081165b2d7a1b22))
* menu id reassignment fixed ([ac7d0f5](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/ac7d0f5ad12e43a4c56319bb34150534586e24cc))
* upgraded rector config to work with latest rector release ([406283e](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/406283e1363a4d5178496b5616fd6ddfda2593e8))

## [1.5.9](https://github.com/IONOS-WordPress/cm4all-wp-impex/compare/1.5.8...1.5.9) (2023-07-24)


### Bug Fixes

* corrected indentation ([c609c48](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/c609c486da216fbe962cbdd2466074b61f611a5e))

## [1.5.8](https://github.com/IONOS-WordPress/cm4all-wp-impex/compare/1.5.7...1.5.8) (2023-06-21)


### Bug Fixes

* missing undefined $custom_taxonomies ([76eb948](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/76eb948e9ae9c794f82d04ba23e6e9f1e6532372))

## [1.5.7](https://github.com/IONOS-WordPress/cm4all-wp-impex/compare/1.5.6...1.5.7) (2023-04-03)


### Bug Fixes

* fixed unit tests for wp 6.2 ([b76f9dc](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/b76f9dc5d9f89e986d9d202667593abe5c1ff80f))
* impex uses anonymous filenames for attachment blobs ([6a47bac](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/6a47baca75ef5dd29161314b0f1e54b2ff477c13))
* removed obsolete vscode launch configuration ([ea9d509](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/ea9d509e20e618bb7bc6cd305c892f0e6d309156))

## [1.5.6](https://github.com/IONOS-WordPress/cm4all-wp-impex/compare/1.5.5...1.5.6) (2023-02-03)


### Bug Fixes

* imported option "theme_mods_trinity-core" will be updated after final import ([14b1865](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/14b18657f1e71b1a1c6cc1430cc77f4e1e463f86))
* remap imported reusable block ids ([a8828db](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/a8828db1d47f67c3ac5c846bb30686939dce7f35))

## [1.5.5](https://github.com/IONOS-WordPress/cm4all-wp-impex/compare/1.5.4...1.5.5) (2022-12-23)


### Bug Fixes

* remap imported reusable block ids ([f2c91ee](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/f2c91ee1c8be7e2d0f6d351bed93ee361fce40ff))

## [1.5.4](https://github.com/IONOS-WordPress/cm4all-wp-impex/compare/1.5.3...1.5.4) (2022-12-22)


### Bug Fixes

* added remapping site_logo and site_icon ([f669013](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/f6690133b7689fd69ade9eda8bf4f774c516655d))
* export example exports site logo, title and description ([362b9c2](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/362b9c22c7883794f351c726d439854806ae957a))
* export website title,description and logo ([52a8084](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/52a80848d91b4a3fefbb09ee7196e7ca5d8aa56e))
* exporting default homepage options ([968ca33](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/968ca33c8d129ab1e2427f343b63edf96eec9dfb))
* finalized remapping imported nav_menu refs and several options referencing posts ([93c27c3](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/93c27c34ba078e575091485f7e0eeec1b50b70cd))
* pinned ubuntu version for github action ([ead3c11](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/ead3c118e81a936bc1cb978b1faa090b80424edc))

## [1.5.3](https://github.com/IONOS-WordPress/cm4all-wp-impex/compare/1.5.2...1.5.3) (2022-11-03)


### Bug Fixes

* disabled example filter hiding impex import profiles ([6356f97](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/6356f97b909a525abd0741b6e4a736614e30cd40))
* GitHub action ([cf49192](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/cf4919240738d2126b0a8a326b364ebf6a3b8a42))
* GitHub permission issue ([2e6e688](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/2e6e68891356621f3ea362d5429c02ded74b46a9))
* GitHub permission issue ([f6a83d1](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/f6a83d166f8af743d0fa675e51423333623b2b3f))
* **make:** fixed a few caveats in Makefile ([48330ae](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/48330aea3ad240032f6139b2c86906ab762d9ea1))
* **mdbook image:** added dopcker label pointing to the github repo ([319e870](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/319e870d28dee09ea3d8f1cea3b201b2f3839b1d))
* nav menu import doesnt chrash anymore ([d8e9959](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/d8e9959f91efff5e86da09f1466fbd7955a8536b))
* patched Makefile to fit GitHub action needs ([174e666](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/174e66608d2dbe1ff91990164b44a0a7e2bac7b2))

### [1.5.2](https://github.com/IONOS-WordPress/cm4all-wp-impex/compare/1.5.1...1.5.2) (2022-10-17)


### Bug Fixes

* **@cm4all-wp-impex/generator:** fixed double passing slicePath argument on migrate callback ([8e11db4](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/8e11db41fb61d18957249404245b5ab74f19c6b3))

### [1.5.1](https://github.com/IONOS-WordPress/cm4all-wp-impex/compare/1.5.0...1.5.1) (2022-10-13)


### Bug Fixes

* **@cm4all-wp-impex/generator:** added missing migrate callback arguments ([9fc96ff](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/9fc96ffabaa26a4f8033d5d72b00664945a58402))

## [1.5.0](https://github.com/IONOS-WordPress/cm4all-wp-impex/compare/1.4.3...1.5.0) (2022-10-07)


### Features

* **@cm4all-wp-impex/generator:** added migrate function to minimize boilerplate code when writing impex export transformations ([209ad2d](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/209ad2d4995a6fee1a1419424544e00b8f3baeee))


### Bug Fixes

* **@cm4all-wp-impex/generator:** fixed chunk directory creation in `migrate` ([7f184bb](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/7f184bbb68d4d4a2e767f27e499d5d7202778ad0))
* **@cm4all-wp-impex/generator:** fixed testcases ([3e58f1d](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/3e58f1d8e5488fd4bd4f01f02f8a3471541a7459))
* imported media metadata will be generated after consuming slices ([1373a6e](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/1373a6eccda8d8d33c9b709bbfddadf761caf918))
* relax post consume callback error handling ([c309d1b](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/c309d1bcb34404c87ebc2821e444a9b75a6db729))
* simple import button disabled style workaround added for current gutenberg ([6b5c685](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/6b5c685e98d23f8197f9e2ab11d6a4ceb7629f64))
* use external mdbook image for documentation generation within ci ([639e824](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/639e824b6e3a536d0db8d7586eaa08630f744f52))

### [1.4.3](https://github.com/IONOS-WordPress/cm4all-wp-impex/compare/1.4.2...1.4.3) (2022-09-19)


### Bug Fixes

* fixed extensionless attachment imports ([ab28dc3](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/ab28dc34d4b547bb3e643cb6039f28a8d0b16563))

### [1.4.2](https://github.com/IONOS-WordPress/cm4all-wp-impex/compare/1.4.1...1.4.2) (2022-09-15)


### Bug Fixes

* fixed getFiles() in @cm4all-wp-impex/generator ([570f5e4](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/570f5e485f38b64c12b44a7502cfaebc8ab8ee5e))
* image attachments without extension can be handlet by impex ([f8d157f](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/f8d157fecfa4c726c105a031bef66e01dbd7afe3))
* typos fixed ([599e950](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/599e9503cb3c886a86c46bac841b421c89eb79ea))

### [1.4.1](https://github.com/IONOS-WordPress/cm4all-wp-impex/compare/1.4.0...1.4.1) (2022-08-31)


### Bug Fixes

* updated dev environment to wordpress 6.0.2 ([271b33d](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/271b33d97078fa4e584d82575fea7cecf00fe10d))

## [1.4.0](https://github.com/IONOS-WordPress/cm4all-wp-impex/compare/1.3.8...1.4.0) (2022-08-30)


### Features

* added cleanup option to "advanced import" ui ([74ffc47](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/74ffc479b0992580ada6ea5a3b89471062f83626))
* added import option to cleanup wordpress before import ([98cb602](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/98cb6021c8e2f44e21f34bf9c1afe61605755c3d))
* impex cli import now supports providing options to the import process ([9064a77](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/9064a7725e75a714d53e9f050f2beb63125fdce7))
* impex cli supports import options like "impex-import-option-cleanup_contents" ([b218221](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/b218221d1ba23b7756d23212018a0def9094e792))
* import/export filters can now be filtered using wp filters ([f94ae55](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/f94ae55c3203a93ff1bb017ab52e026b493f2345))
* simple import roughly implemented ([2836697](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/28366974ff2d8c23a65656d73af64876c013a0d6))


### Bug Fixes

* browser filesystem-api will use consistent snaopshot directory handle ([3960b98](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/3960b985f73b386fd3b6dd7680f7647cc859c368))
* consumeImport will gracefully handle not set option impex-import-option-cleanup_contents ([4ac7660](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/4ac7660f02e41d4dafd580e4f5ce53fe695f4042))
* ensuring that the window.crypto api is available before using it ([4b818b4](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/4b818b438cca375b73bf4eb6506d6ccc4cec224e))
* fixed crypto api detection ([893ff0d](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/893ff0d2435037ebeb5d4b816e7e8190bf407c3e))
* fixed error message if browser crypto api is missing ([9544a00](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/9544a0080decfacb9d6a6643cce03deac5aa50a0))
* fixed some typos ([a79decb](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/a79decb01d37308b2068f9caaa47b0ca0f93d01a))
* getProfile() should return an ArrayIterator ([ad6ace4](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/ad6ace41ae50aa0ad3c5227196bdc199a66e2cde))
* i18n translations fixed ([8a7f392](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/8a7f3923f12c57ae50c047b42889b9a56d03b7b2))
* impex error popup can handle multiline messages ([f6b51e1](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/f6b51e168183dd4feeee005f337637120249dcbf))
* import will no more fail on unknown post type import ([5e25505](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/5e255058c199de968a9c4a68a1d498f3419dc532))
* import will not fail on unknown taxonomy references ([eff2c2e](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/eff2c2ea5d23d7167356f2a450d5abdb087d4d24))
* made snapshot import more sensitive about files/directories ([f6120d4](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/f6120d40bf4ddecd79165ecc550804f3b6a4213f))
* relax import of users with illegal login ([fea4c5a](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/fea4c5a0e93f529d7d07481481f92360f28afe3b))
* simple ui labels fixed ([be850cf](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/be850cf4f2a1aa740dd30d438f9c890a3379073a))
* test environment runs only with disabled wp cron ([ff24148](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/ff241480518107ff4c8a8d2d88e2d0eef70a3aaa))
* upgraded rector command to latest rector cli ([bcfc886](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/bcfc88630e863dc6edc6587ba44680e644dc5a77))

### [1.3.8](https://github.com/IONOS-WordPress/cm4all-wp-impex/compare/1.3.7...1.3.8) (2022-07-20)


### Bug Fixes

* import will no more fail on unknown post type import ([df37c18](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/df37c18c3a01b09edb20ed683b257f64712f456b))

### [1.3.7](https://github.com/IONOS-WordPress/cm4all-wp-impex/compare/1.3.6...1.3.7) (2022-07-06)


### Bug Fixes

* made snapshot import more sensitive about files/directories ([e38ed41](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/e38ed41c5f40bef8ce191462e158789142dd722c))

### [1.3.6](https://github.com/IONOS-WordPress/cm4all-wp-impex/compare/1.3.5...1.3.6) (2022-07-05)


### Bug Fixes

* import will not fail on unknown taxonomy references ([c2016b5](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/c2016b59b18ad2a841acb76c5f0e1f3515ea6f2a))
* upgraded rector command to latest rector cli ([0976dcf](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/0976dcf68fdefc080331686a742dc833a209168c))

### [1.3.5](https://github.com/IONOS-WordPress/cm4all-wp-impex/compare/1.3.4...1.3.5) (2022-05-31)


### Bug Fixes

* **@cm4all-wp-impex/generator:** package.json repository property fixed ([b140ffe](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/b140ffe032cf1d349ac43a7ac995ca4ad2093869))
* **impex-cli:** made import option "profile" optional. default value is "all" ([82517e2](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/82517e2e11388c8e143a30a8e9c7bfc4c967e83e))
* rector upgrade ([fae99b8](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/fae99b8df3e64f34fcaa56cb7f3104b445df90c4))
* updated recommended vscode extensions ([facf38e](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/facf38e192295a1b39fd1e381d5dff43c501be17))
* updated supported wordpress version to 6.0 ([be2a62d](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/be2a62d44026eec73de3e6a5584d9fc424c7e03c))

### [1.3.4](https://github.com/IONOS-WordPress/cm4all-wp-impex/compare/1.3.3...1.3.4) (2022-05-10)


### Bug Fixes

* typo fixed in plugin descriptor ([419625f](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/419625f0240cff3941a58340ecbb520b4bc2effc))
* typo fixed in wordpress descriptor ([ba440bd](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/ba440bd78bcb5d153c05929f244eedd849c6d946))

### [1.3.3](https://github.com/IONOS-WordPress/cm4all-wp-impex/compare/1.3.2...1.3.3) (2022-05-10)


### Bug Fixes

* added missing youtube links to npm package and wp plugin ([62e225b](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/62e225bc9ede710eef7e5c1b12450ec9c76763fe))

### [1.3.2](https://github.com/IONOS-WordPress/cm4all-wp-impex/compare/1.3.1...1.3.2) (2022-05-10)


### Bug Fixes

* Documentation polished ([1ae7828](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/1ae7828e962c770dea474d9fae52abcea5b65dab))

### [1.3.1](https://github.com/IONOS-WordPress/cm4all-wp-impex/compare/1.3.0...1.3.1) (2022-05-03)


### Bug Fixes

* removed obsolete files ([0bc19e6](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/0bc19e61e2e0ce815fe14fc156ab2fd4f02b55ad))

## [1.3.0](https://github.com/IONOS-WordPress/cm4all-wp-impex/compare/1.2.1...1.3.0) (2022-05-03)


### Features

* added npm package [@cm4all-wp-impex](https://github.com/cm4all-wp-impex) publish informations ([c0c659a](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/c0c659a53c49004b5887bab031a98acb17a2c833))
* **attachment:** added slice property 'impex:post-references' ([fca0be4](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/fca0be4ac978faa3bf8b1aeae2319429ed5e4fe3))


### Bug Fixes

* **impex-transform:** add testcase core/image injecting <figure>...<figcaption> using onLoad hook ([278a0d9](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/278a0d95df5b9060d2feef6011a05ce1336af8d3))
* **impex-transform:** final attempt to fix resetting block transforms for tape tests ([8c793f7](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/8c793f720598ea4a2acadb9d4544781f091816af))
* provide NPM_TOKEN to semantic-release ([5f2fed5](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/5f2fed59a76cfb1b321f1b4d2b4bc05e8c7daac3))

### [1.2.1](https://github.com/IONOS-WordPress/cm4all-wp-impex/compare/1.2.0...1.2.1) (2022-03-24)


### Bug Fixes

* assert that WP_Filesystem() is already declared ([5ff641f](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/5ff641fe6b7bf2a9defbd9d6e6e0d84b216b3aff))
* fixed impexcli docker image build ([ce435ee](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/ce435eef11bab073d8bd8e5656688a9831ff285c))
* fixed variable naming typo ([e408ade](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/e408ade71ca093675bd55bea5b366f7ca8980577))
* **impex-cli-test:** fixed paths to impex-cli ([170af21](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/170af2149611847171fd2d63b36fef49c48c3ec7))
* import php namespace issue fixed ([342e5bd](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/342e5bd643b56fa4f383b11093a7faa933bd2f76))
* **wp-env:** htaccess gets also deployed to test-Wordpress ([993b275](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/993b2758d4c343e015a9fd2fb16ecbe2fa2b7e90))

## [1.2.0](https://github.com/IONOS-WordPress/cm4all-wp-impex/compare/1.1.7...1.2.0) (2022-03-02)


### Features

* impex-cli import and export finally works ([482c87e](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/482c87e3025bfcfe0168c966594d360d547d0906))


### Bug Fixes

* added additional links to impex plugin entry in plugins.php ([4bd2a8a](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/4bd2a8a53ac2afc86b0ac5db1ea2980d37be6cf3))
* fixed mdbook image generation ([2f613fa](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/2f613fa01bdb869474295961b6ae99452db9c551))
* fixed typo in .wp-env.override.json ([8b9227d](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/8b9227d69b9f7a7337cb321e68a7e72192bdf0c5))
* impex plugin readme template updated ([56344dd](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/56344ddab6e3d15ab96b37de8664a4fc1a5d000d))
* **impex-cli:** improved impex-cli logging ([8476260](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/84762600997a825d2aed9045d4990b42e8b5f630))

### [1.1.7](https://github.com/IONOS-WordPress/cm4all-wp-impex/compare/1.1.6...1.1.7) (2022-02-08)


### Bug Fixes

* fixed wordpress.org subversion commit command in Makefile ([3d7baa5](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/3d7baa5e4d141a4dccc5a10839a26c801ed5d6c7))
* improved Makefile ([8272a02](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/8272a02ff8335561e4e9d89495c6a32970f3b80a))
* Makefile target "deploy-to-wordpress" - fixed SVN_* variable provisioning ([d639f59](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/d639f594f49313d5ad21e7d50bf3b41bc4239972))
* Makefile target "deploy-to-wordpress" - fixed typo in svn commit message ([baedfde](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/baedfde7018598b55e7158ce12754a8555699792))
* Makefile target "deploy-to-wordpress" - SNV_* parameter provisioning ([636ec30](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/636ec30623b8f5d4b01e2917fca33c49c6f5604e))

### [1.1.6](https://github.com/IONOS-WordPress/cm4all-wp-impex/compare/1.1.5...1.1.6) (2022-02-08)


### Bug Fixes

* fixed wordpress.org subversion commit command in Makefile ([c098eb9](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/c098eb9bd4c4241b091842e183e585c1e90ee8c0))

### [1.1.5](https://github.com/IONOS-WordPress/cm4all-wp-impex/compare/1.1.4...1.1.5) (2022-02-08)


### Bug Fixes

* improved Makefile ([0bafa03](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/0bafa033bcdbbc51d3c612b34caa4b9ba9030d0f))

### [1.1.4](https://github.com/IONOS-WordPress/cm4all-wp-impex/compare/1.1.3...1.1.4) (2022-02-08)


### Bug Fixes

* Makefile target "deploy-to-wordpress" - SNV_* parameter provisioning ([0a9db88](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/0a9db88865d3ee85989711f7eca6e6ab8986b94c))

### [1.1.3](https://github.com/IONOS-WordPress/cm4all-wp-impex/compare/1.1.2...1.1.3) (2022-02-08)


### Bug Fixes

* Makefile target "deploy-to-wordpress" - fixed SVN_* variable provisioning ([4c7d229](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/4c7d2293656759e95d136454cb4ff009421e80b7))

### [1.1.2](https://github.com/IONOS-WordPress/cm4all-wp-impex/compare/1.1.1...1.1.2) (2022-02-08)


### Bug Fixes

* Makefile target "deploy-to-wordpress" - fixed typo in svn commit message ([e5edf8b](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/e5edf8b8dd51ea5de40fef6a05d9702b66662d5c))

### [1.1.1](https://github.com/IONOS-WordPress/cm4all-wp-impex/compare/1.1.0...1.1.1) (2022-02-08)


### Bug Fixes

* fixed installation of convert in github action dependencies ([52e319c](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/52e319cc9122f7c27acb39e8dabda78a12a6a840))
* fixed installation of github action dependencies ([b4a7072](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/b4a70727946905a2a2367faa9fd863ed31abd33c))

## [1.1.0](https://github.com/IONOS-WordPress/cm4all-wp-impex/compare/1.0.4...1.1.0) (2022-02-02)


### Features

* impex for php 7.4 infrastructure added ([60d9ee5](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/60d9ee548cbcf3eadf4e9a80dbf9bfc712fce3d9))
* implex plugin for php 7.4 will be generated ([af7a393](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/af7a39316d062643ee8386be280587aac492cc4c))

### [1.0.4](https://github.com/IONOS-WordPress/cm4all-wp-impex/compare/1.0.3...1.0.4) (2022-01-26)


### Bug Fixes

* improved github action semantic-release ([feeeb53](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/feeeb5315898c058af8c59015c662e069a127ccc))

### [1.0.3](https://github.com/IONOS-WordPress/cm4all-wp-impex/compare/1.0.2...1.0.3) (2022-01-26)


### Bug Fixes

* added missing "Tested up to" metdata to plugin.php and readme.txt template ([270355e](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/270355e71c1c89cb3b29958116b2b43ca5b80f18))

### [1.0.2](https://github.com/IONOS-WordPress/cm4all-wp-impex/compare/1.0.1...1.0.2) (2022-01-25)


### Bug Fixes

* "Merge main back into develop" task of github release action fixed ([e2e4632](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/e2e463239bbbdc14c414ad1b8354b5e4693634a5))
* github release action fixed ([f26da00](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/f26da00011498e24ee816c20cdb11ca4b7559674))

### [1.0.1](https://github.com/IONOS-WordPress/cm4all-wp-impex/compare/1.0.0...1.0.1) (2022-01-25)


### Bug Fixes

* fixed typo for impex plugin field "Description" ([4e674f3](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/4e674f3105fa6f22fdf808eddad55f12caf22afe))

## 1.0.0 (2022-01-25)


### Features

* initial release ([69a74a7](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/69a74a70792304daff22d5bef721382f820e90d4))


### Bug Fixes

* added missing package-lock.json ([bf5b9d5](https://github.com/IONOS-WordPress/cm4all-wp-impex/commit/bf5b9d5541e732046edd466ef5b07784f92dcd3c))
