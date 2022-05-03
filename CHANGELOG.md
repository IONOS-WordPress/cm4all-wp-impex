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
