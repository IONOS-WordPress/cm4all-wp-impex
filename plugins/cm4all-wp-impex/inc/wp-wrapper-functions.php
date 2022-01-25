<?php

namespace cm4all\wp\impex;

/**
 * contains various utility functions wrapping general WordPress functions
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit();
}

/**
 * Retrieves a URL within the wp-impex plugin directory.
 *
 * @param string $path   Optional. Extra path appended to the end of the URL, including
 *                       the relative directory if $plugin is supplied. Default empty.
 *
 * @see \plugins_url
 */
function plugins_url(string $path = ''): string
{
  return \plugins_url($path, __DIR__);
}

/**
 * Get the filesystem directory path (with trailing slash) for the wp-impex plugin.
 *
 * @param string $path (optional) The filename of the plugin
 * @return string the filesystem path of the directory contained in wp-impex plugin.
 *
 * @see \plugin_dir_path
 */
function plugin_dir_path(string $path = ''): string
{
  return \plugin_dir_path(__DIR__) . $path;
}

/**
 * Register a new script.
 *
 * Registers a script to be enqueued later using the wp_enqueue_script() function.
 *
 * @param string   $handle    Name of the script. Should be unique.
 * @param string   $pluginRelativePath path of the script relative to the plugin root.
 *                            If source is set to false, script is an alias of other scripts it depends on.
 * @param string[] $deps      Optional. An array of registered script handles this script depends on. Default empty array.
 * @param bool     $in_footer Optional. Whether to enqueue the script before </body> instead of in the <head>.
 *                            Default 'false'.
 * @return bool Whether the script has been registered. True on success, false on failure.
 *
 * @see \wp_register_script
 */
function wp_register_script(string $handle, string $pluginRelativePath, array $deps = [], bool $in_footer = false): bool
{
  if (defined(SCRIPT_DEBUG) && !SCRIPT_DEBUG) {
    $pluginRelativePath = preg_replace('/\.js$/', '-min.js', $pluginRelativePath);
  }

  return \wp_register_script($handle, plugins_url($pluginRelativePath), $deps, filemtime(plugin_dir_path($pluginRelativePath)), $in_footer);
}

/**
 * Enqueue a script.
 *
 * @param string   $handle    Name of the script. Should be unique.
 * @param string   $pluginRelativePath path of the script relativeto the pluginroot.
 * @param string[] $deps      Optional. An array of registered script handles this script depends on. Default empty array.
 * @param bool     $in_footer Optional. Whether to enqueue the script before </body> instead of in the <head>.
 *                            Default 'false'.
 *
 * \wp_enqueue_script
 */
function wp_enqueue_script(string $handle, string $pluginRelativePath, array $deps = [], bool $in_footer = false)
{
  wp_register_script($handle, $pluginRelativePath, $deps, $in_footer);

  return \wp_enqueue_script($handle);
}

/**
 * Register a CSS stylesheet.
 *
 * @param string   $handle Name of the stylesheet. Should be unique.
 * @param string   $pluginRelativePath path of the stylesheet relative to the plugin root.
 *                         If source is set to false, stylesheet is an alias of other stylesheets it depends on.
 * @param string[] $deps   Optional. An array of registered stylesheet handles this stylesheet depends on. Default empty array.
 * @param string   $media  Optional. The media for which this stylesheet has been defined.
 *                         Default 'all'. Accepts media types like 'all', 'print' and 'screen', or media queries like
 *                         '(orientation: portrait)' and '(max-width: 640px)'.
 * @return bool Whether the style has been registered. True on success, false on failure.
 *
 * @see \wp_register_style
 */
function wp_register_style(string $handle, string $pluginRelativePath, array $deps = [], string $media = 'all'): bool
{
  if (defined(SCRIPT_DEBUG) && !SCRIPT_DEBUG) {
    $pluginRelativePath = preg_replace('/\.css$/', '-min.css', $pluginRelativePath);
  }

  return \wp_register_style($handle, plugins_url($pluginRelativePath), $deps, filemtime(plugin_dir_path($pluginRelativePath)), $media);
}

/**
 * Enqueue a CSS stylesheet.
 *
 * Registers the style if source provided (does NOT overwrite) and enqueues.
 *
 * @param string   $handle Name of the stylesheet. Should be unique.
 * @param string   $pluginRelativePath path of the stylesheet relative to the plugin root.
 * @param string[] $deps   Optional. An array of registered stylesheet handles this stylesheet depends on. Default empty array.
 * @param string   $media  Optional. The media for which this stylesheet has been defined.
 *                         Default 'all'. Accepts media types like 'all', 'print' and 'screen', or media queries like
 *                         '(orientation: portrait)' and '(max-width: 640px)'.
 *
 * @see \wp_enqueue_style
 */
function wp_enqueue_style(string $handle, string $pluginRelativePath, array $deps = [], string $media = 'all')
{
  wp_register_style($handle, $pluginRelativePath, $deps, $media);

  return \wp_enqueue_style($handle);
}
