<!-- toc -->

# WordPress Filters

ImpEx supports various WordPress filters for customizing ImpEx.

## Import

### `impex_import_filter_profiles` 

Using this filter you can hide ImpEx import profiles from the user.

An example : To hide the ImpEx import profile `all` you need to add the following WordPress filter to your sources :

```php: 
\add_filter( 
  'impex_import_filter_profiles', 
  fn( $profiles ) => array_filter(
    $profiles, 
    fn($profile) => $profile->name !== 'all'
  ),
);
```

## Export

### `impex_export_filter_profiles` 

Using this filter you can hide ImpEx export profiles from the user.

An example : To hide the ImpEx export profile `base` you need to add the following WordPress filter to your sources :

```php: 
\add_filter( 
  'impex_export_filter_profiles', 
  fn( $profiles ) => array_filter(
    $profiles, 
    fn($profile) => $profile->name !== 'base'
  ),
);
```
