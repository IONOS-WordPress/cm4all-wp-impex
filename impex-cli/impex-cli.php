#!/usr/bin/env php
<?php

# Description: commandline tool to import/export wordpress data by interacting with the cm4all-wp-impex wordpress plugin
#
# This file is part of cm4all-wp-impex.
#
# Usage: see impex-cli/impex-cli.php help
#
# Requires PHP: 8.0, php-curl extension
# Author: Lars Gersmann<lars.gersmann@cm4all.com>
# Created: 2022-02-21
# Repository: https://github.com/IONOS-WordPress/cm4all-wp-impex
# License: See repository LICENSE file.
# Tags: #wordpress,#cli,#impex

namespace cm4all\wp\impex\cli;

use Exception;

function _noop(...$args)
{
}

function rmdir_r($dir)
{
  if (is_dir($dir)) {
    $objects = scandir($dir);
    foreach ($objects as $object) {
      if ($object != "." && $object != "..") {
        if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . "/" . $object))
          rmdir_r($dir . DIRECTORY_SEPARATOR . $object);
        else
          unlink($dir . DIRECTORY_SEPARATOR . $object);
      }
    }
    rmdir($dir);
  }
}

class DieException extends \RuntimeException
{
  function __construct($message, $code = 0, \Exception $previous = null)
  {
    parent::__construct($message, $code, $previous);
  }
}

function _die($options, $message, ...$args)
{
  $message = sprintf($message, ...array_map(fn ($_) => is_array($_) || is_object($_) ? json_encode($_) : $_, $args));

  throw new DieException($message, 1);
}

function main($argv)
{
  try {
    $argc = count($argv);

    $operation = 'help';
    $options = ['header' => []];
    $arguments = [];

    switch ($argc) {
      case 1:
        break;
      default:
        switch ($argv[1]) {
          case 'import':
          case 'export':
          case 'export-profile':
          case 'import-profile':
            $operation = $argv[1];
            _parseCommandlineArguments(array_slice($argv, 2), $options, $arguments);

            [$result, $status, $error] = _curl(
              $options,
              "",
              null,
              fn ($curl) => curl_setopt($curl, \CURLOPT_URL, $options['rest-url'])
            );


            // ensure we can connect to the wordpress rest api
            if ($error) {
              _die(
                $options,
                "Could not connect to wordpress rest endpoint(='%s'): %s\nEnsure param '-rest-url' is correct.\nCheck wordpress rest api is enabled.\n",
                $options['rest-url'],
                $error
              );
            } else {
              if (is_string($result) && $status === 301) {
                _die(
                  $options,
                  "Wordpress JSON Rest API Endpoint is probably misconfigured - request returned(http status='%s') : %s'\n",
                  $status,
                  'HTTP/1.1 301 Moved Permanently'
                );
              } else {
                // ensure impex plugin rest namespace is available
                if (!in_array('cm4all-wp-impex/v1', $result['namespaces'])) {
                  _die(
                    $options,
                    "Wordpress plugin cm4all-wp-impex seems not to be installed in the wordpress instance - expected rest endpoint(='cm4all-wp-impex/v1') is not available : Available rest endpoints %s'\n",
                    json_encode($result['namespaces'])
                  );
                }
              }
            }

            break;
          case 'help':
          case '--help':
            break;
          default:
            _die($options, "Invalid operation: %s", join(' ', array_slice($argv, 1)));
        }
    };

    [$json, $status] = call_user_func(__NAMESPACE__ . '\\' . str_replace(['-'], ['_'], $operation), $options, ...$arguments);
    if ($status === 200) {
      echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }
  } catch (DieException $ex) {
    if (php_sapi_name() === "cli" && !str_ends_with($_SERVER['argv'][0], 'phpunit')) {
      fprintf(STDERR, $ex->getMessage());
      exit(1);
    } else {
      throw $ex;
    }
  }
}

function _parseCommandlineArguments($argv, &$options, &$arguments)
{
  foreach ($argv as $arg) {
    if (preg_match('/^-H=(.+)$/', $arg, $matches)) {
      $options['header'][] = trim($matches[1], '\"\'');
    } else if (preg_match('/^-([^=]+)$/', $arg, $matches)) {
      [, $name] = $matches;

      if (!isset($options[$name])) {
        $options[$name] = true;
      } else {
        if (!is_array($options[$name])) {
          $options[$name] = [$options[$name]];
        }
        $options[$name][] = true;
      }
    } else if (preg_match('/^-([^=]+)=(.+)$/', $arg, $matches)) {
      [, $name, $value] = $matches;
      $name = trim($name, '\"\'');
      $value = trim($value, '\"\'');

      if (!isset($options[$name])) {
        $options[$name] = $value;
      } else {
        if (!is_array($options[$name])) {
          $options[$name] = [$options[$name]];
        }
        $options[$name][] = "$value";
      }
    } else {
      $arguments[] = $name = trim($arg, '\"\'');;
    }
  }

  // convert provided username and password options to basic auth header
  if (isset($options['username']) && isset($options['password'])) {
    foreach ($options['header'] as $header) {
      if (preg_match('/^authorization: Basic$/', $header, $matches)) {
        _die($options, "Can't use both username and password options and Authorization header\n");
      }
    }

    $options['header'][] = "authorization: Basic " . base64_encode($options['username'] . ":" . $options['password']);
    _log('Used provided username(=%s) and password(=%s) options to create Authorization header(=%s).', $options['username'], $options['password'], end($options['header']),);
    unset($options['username']);
    unset($options['password']);
  }

  if (!isset($options['rest-url'])) {
    _die($options, "Missing required option '-rest-url'\n");
  }

  $accept_header_defined = false;
  foreach ($options['header'] as $header) {
    if (preg_match('/^accept:$/', $header, $matches)) {
      $accept_header_defined = true;
    }
  }

  if (!$accept_header_defined) {
    $options['header'][] = 'accept: application/json';
  }
}

function help($options, $message = null, ...$args)
{
  if ($message) {
    printf($message . PHP_EOL, ...$args);
  }

  echo "Usage: impex-cli.php [operation] [sub-operation?] -rest-url=[wordpress-restapi-url] [options] [arguments]

operation:
  help                                                  show this help

  export                                                export wordpress data using the impex wordpress plugin to a directory
    options:
      -profile=[export-profile]                         (required) export profile to use
    arguments:
      [directory]                                       (required) directory to export to

  import                                                import wordpress data (in impex format) from a directory
    options:
      -profile=[import-profile]                        (default='all') import profile to use
      -options=[import options in JSON format]         (default='{}') provide options to the import process
    arguments:
      [directory]                                       (required) impex directory to import from

  export-profile
    sub-operations:                                     (required)
      list                                              json list of known impex export profiles

  import-profile
    sub-operations:                                     (required)
      list                                              json list of known impex import profiles

global options:
  -username=[wordpress-username]
  -password=[wordpress-password]
  -rest-url=[wordpress-restapi-url]                     (required) url of wordpress rest endpoint
                                                        example: -rest-url=http://localhost:8888/wp-json

  -verbose                                              prints additional informations to stderr
  -CURLOPT_VERBOSE                                      prints additional php-curl debug informations stderr
  -H=[header]                                           additional header to send to the wordpress rest api
                                                        may occur multiple times
                                                        example: -H='x-foo: bar'

see https://ionos-wordpress.github.io/cm4all-wp-impex/impex-cli.html for more.
";
}

function _log($options, $message, ...$args)
{
  if (isset($options['verbose'])) {
    printf($message . PHP_EOL, ...$args);
  }
}

function _curl($options, string $endpoint, $method = null, $callback = __NAMESPACE__ . '\_noop')
{
  $curl = curl_init();

  curl_setopt($curl, \CURLOPT_URL, $options['rest-url'] . '/cm4all-wp-impex/v1/' . $endpoint);

  if ($method !== null) {
    curl_setopt($curl, $method, 1);
  }
  curl_setopt($curl, \CURLOPT_RETURNTRANSFER, 1);

  curl_setopt($curl, \CURLOPT_HTTPHEADER, $options['header']);

  if (isset($options['CURLOPT_VERBOSE'])) {
    curl_setopt($curl, \CURLOPT_VERBOSE, 1);
  }

  call_user_func($callback, $curl);

  curl_setopt($curl, CURLOPT_FAILONERROR, true);

  $result = curl_exec($curl);
  $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

  if (((string)$http_status)[0] !== '2') {
    _log(
      $options,
      "accessing '%s' returned http status code(=%s) : %s",
      curl_getinfo($curl, CURLINFO_EFFECTIVE_URL),
      $http_status,
      curl_error($curl),
    );
    $error = curl_error($curl);
  }

  if (curl_errno($curl)) {
    $error = curl_error($curl);
    if (isset($options['verbose'])) {
      _log("curl returned an error : '%s'", $error);
    }
  } else {
    $result = json_decode($result, true) ?? $result;
  }

  curl_close($curl);

  return [$result, $http_status, $error ?? null];
}

function export_profile($options, $command = 'list', ...$args)
{
  switch ($command) {
    case 'list':
      [$result, $http_status, $error] = _curl($options, 'export/profile');
      if ($error) {
        _die($options, "Failed to get impex export profiles(=%s) : %s\n", $http_status, $error);
      }
      return [$result, $http_status, $error];
    default:
      throw new \RuntimeException("Invalid command '$command'");
  }
}

function import_profile($options, $command = 'list', ...$args)
{
  switch ($command) {
    case 'list':
      [$result, $http_status, $error] = _curl($options, 'import/profile');
      if ($error) {
        _die($options, "Failed to get impex import profiles(=%s) : %s\n", $http_status, $error);
      }
      return [$result, $http_status, $error];
    default:
      throw new \RuntimeException("Invalid command '$command'");
  }
}

function sanitizeFilename($string, $force_lowercase = true, $anal = false)
{
  $strip = array(
    "~", "`", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "_", "=", "+", "[", "{", "]",
    "}", "\\", "|", ";", ":", "\"", "'", "&#8216;", "&#8217;", "&#8220;", "&#8221;", "&#8211;", "&#8212;",
    "â€”", "â€“", ",", "<", ".", ">", "/", "?"
  );
  $clean = trim(str_replace($strip, "", strip_tags($string)));
  $clean = preg_replace('/\s+/', "-", $clean);
  $clean = ($anal) ? preg_replace("/[^a-zA-Z0-9]/", "", $clean) : $clean;
  return ($force_lowercase) ?
    ((function_exists('mb_strtolower')) ? mb_strtolower($clean, 'UTF-8') : strtolower($clean))
    : $clean;
}

function import($options, $import_directory, ...$args)
{
  $profile = $options['profile'] ?? 'all';
  // if (!$profile) {
  //   _die($options, "Import failed: missing option 'profile'");
  // }

  $import_options = $options['options'] ?? '{}';
  try {
    $import_options = json_decode(
      json: $import_options,
      associative: true,
      flags: JSON_THROW_ON_ERROR,
    );
  } catch(Exception $ex) {
    _die(
      $options,
      "Import failed : parsing import options(='%s') as JSON object failed : %s",
      $import_options,
      $ex->getMessage(),
    );
  }


  [$profiles] = import_profile($options, 'list');
  $profiles = array_column($profiles, 'name');
  if (!in_array($profile, $profiles)) {
    _die(
      $options,
      "Import failed : Profile(=%s) does not exist. Known profiles are %s",
      $profile,
      $profiles
    );
  }

  if (!is_dir($import_directory)) {
    _die($options, "Import failed : Directory(=%s) does not exist", $import_directory);
  };
  $import_directory = realpath($import_directory);

  _log($options, "Import directory(=%s) using profile(=%s) and import options(=%s) to %s", $import_directory, $profile, json_encode($import_options, true), $options['rest-url']);

  // create import snapshot
  [$result, $status, $error] = _curl(
    $options,
    'import',
    \CURLOPT_POST,
    fn ($curl) => curl_setopt($curl, \CURLOPT_POSTFIELDS, http_build_query([
      'profile' => $profile,
      'name' => "transient-import-" . vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4)),
      'options' => $import_options,
    ]))
  );

  if ($error) {
    _die($options, "Creating import snapshot failed : HTTP status(=%s) : %s", $status, $error);
  }

  $import_id = $result['id'];

  $__deleteImportSnapshot = function () use ($options, $import_id) {
    // delete import snapshot
    [$result, $status, $error] = _curl(
      $options,
      "import/$import_id",
      null,
      fn ($curl) => curl_setopt($curl, \CURLOPT_CUSTOMREQUEST, 'DELETE')
    );

    if ($error) {
      _die($options, "Deleting import snapshot failed : HTTP status(=%s) : %s", $status, $error);
    }
  };

  // import slices
  $position = 0;
  foreach (glob($import_directory . '/chunk-*', GLOB_ONLYDIR) as $chunk_dir) {
    // var_dump([$chunk_dir]);
    foreach (array_filter(glob($chunk_dir . '/slice-*.json'), 'is_file') as $slice_file) {
      $slice_contents = file_get_contents($slice_file);
      $slice = json_decode($slice_contents, true);

      // upload slice
      [$result, $status, $error] = _curl(
        $options,
        "import/$import_id/slice?" . http_build_query(['position' => $position]),
        \CURLOPT_POST,
        function ($curl) use ($slice, $slice_contents, $chunk_dir, $slice_file, $options) {
          $post_fields = [
            'slice' => $slice_contents,
          ];

          _log($options, "Uploading %s slice(=%s)", $slice["tag"], $slice_file);

          if (
            $slice["tag"] === "attachment" &&
            $slice["meta"]["entity"] === "attachment" &&
            $slice["type"] === "resource"
          ) {
            $attachmentFile = realpath($chunk_dir . '/' . basename($slice_file, '.json') . '-' . basename($slice['data']));

            $post_fields['AttachmentImporter'] = \curl_file_create($attachmentFile);
          }

          curl_setopt($curl, \CURLOPT_POSTFIELDS, $post_fields);
        }
      );

      if ($error) {
        $__deleteImportSnapshot();
        _die($options, "Uploading snapshot slice failed : HTTP status(=%s) : %s", $status, $error);
      }

      $position += 1;
    }
  }

  _log($options, 'Consume imported slices ...');
  [$result, $status, $error] = _curl(
    $options,
    "import/$import_id/consume",
    \CURLOPT_POST,
  );

  $postConsumeCallbacks = $result['callbacks'] ?? [];
  foreach($postConsumeCallbacks as $index => $postConsumeCallback) {
    _log(
      $options,
      'Executing post consume callback(path=%s, method=%s) with data(=%s)',
      $postConsumeCallback['path'],
      $postConsumeCallback['method'],
      json_encode($postConsumeCallback['data']),
    );
    [$result, $status, $error] = _curl(
      $options,
      $postConsumeCallback['path'],
      $postConsumeCallback['method']==='post' ? \CURLOPT_POST : null,
      fn ($curl) => curl_setopt($curl, \CURLOPT_POSTFIELDS, http_build_query($postConsumeCallback['data']))
    );

    if ($error) {
      _log($options, "Warning : Failed to update metadata : HTTP status(=%s) : %s", $status, $error);
    }
  }

  if ($error) {
    $__deleteImportSnapshot();
    _die($options, "Consuming imported snapshot failed : HTTP status(=%s) : %s", $status, $error);
  }

  $__deleteImportSnapshot();
}

function export($options, $export_directory, ...$args)
{
  $profile = $options['profile'] ?? null;
  if (!$profile) {
    _die($options, "Export failed: missing option 'profile'");
  }

  [$profiles] = export_profile($options, 'list');
  $profiles = array_column($profiles, 'name');
  if (!in_array($profile, $profiles)) {
    _die(
      $options,
      "Export failed : Profile(=%s) does not exist. Known profiles are %s",
      $profile,
      $profiles
    );
  }

  if (!is_dir($export_directory)) {
    _die($options, "Export failed : Directory(=%s) does not exist", $export_directory);
  };
  $export_directory = realpath($export_directory);

  _log($options, "Exporting %s using profile(=%s) to directory(=%s)", $options['rest-url'], $profile, $export_directory);

  // create export snapshot
  [$result, $status, $error] = _curl(
    $options,
    'export',
    \CURLOPT_POST,
    fn ($curl) => curl_setopt($curl, \CURLOPT_POSTFIELDS, http_build_query(['profile' => $profile]))
  );

  if ($error) {
    _die($options, "Creating export snapshot failed : HTTP status(=%s) : %s", $status, $error);
  }

  $export_filename = $result['name'];
  $export_filename = substr(sanitizeFilename($export_filename), 0, min(32, strlen($export_filename)));
  $export_id = $result['id'];

  $path = "export/${export_id}/slice";

  // get export snapshot metadata
  [$result, $status, $error] = _curl(
    $options,
    // per_page=1 is a hack to get the first page of results
    "export/${export_id}/slice",
    null,
    fn ($curl) => curl_setopt($curl, \CURLOPT_HEADER, 1) && curl_setopt($curl, \CURLOPT_NOBODY, 1)
  );

  if ($error) {
    _die($options, "Downloading export slice failed : HTTP status(=%s) : %s", $status, $error);
  }

  // preg_match('/^X-WP-Total:\s+(\d+)/mi', $result, $matches);
  // $total = (int)$matches[1] ?? null;
  preg_match('/^X-WP-TotalPages:\s+(\d+)/mi', $result, $matches);
  $x_wp_total_pages = (int)$matches[1] ?? null;

  $export_directory = $export_directory . '/' . $export_filename;

  if (file_exists($export_directory) && ($options['overwrite'] ?? false) === true) {
    rmdir_r($export_directory);
  }

  if (mkdir($export_directory, 0777, true) === false) {
    _die($options, "Export failed : Export directory(=%s) could not created", $export_directory);
  }

  // downlod slices
  for ($chunk = 1; $chunk <= $x_wp_total_pages; $chunk++) {
    _saveSlicesChunk(
      $options,
      $export_directory,
      _curl(
        $options,
        $path . '?' . http_build_query(['page' => $chunk]),
        null,
      ),
      $chunk
    );
  }

  // delete export snapshot
  [$result, $status, $error] = _curl(
    $options,
    // per_page=1 is a hack to get the first page of results
    "export/$export_id",
    null,
    fn ($curl) => curl_setopt($curl, \CURLOPT_CUSTOMREQUEST, 'DELETE')
  );

  if ($error) {
    _die($options, "Deleting export snapshot failed : HTTP status(=%s) : %s", $status, $error);
  }

  echo "$export_directory\n";
}

function _saveSlicesChunk($options, $export_directory, $response, $chunk)
{
  [$slices, $http_status, $error] = $response;

  if ($error) {
    _die($options, "Receiving export slices failed : HTTP status(=%s) : %s", $http_status, $error);
  }

  // create chunk sub directory
  $chunk_directory = $export_directory . '/chunk-' . str_pad($chunk, 4, '0', STR_PAD_LEFT);
  if (mkdir($chunk_directory, 0777, true) === false) {
    _die($options, "Export failed : Export chunk directory(=%s) could not created", $chunk_directory);
  }

  foreach ($slices as $index => $slice) {
    $slice_file = $chunk_directory . '/slice-' . str_pad($index, 4, '0', STR_PAD_LEFT) . '.json';

    _log($options, "Downloading %s to file(=%s)", $slice["tag"], $slice_file);

    if (
      $slice["tag"] === "attachment" &&
      $slice["meta"]["entity"] === "attachment" &&
      $slice["type"] === "resource"
    ) {
      $_links_self = $slice["_links"]["self"] ?? null;

      if ($_links_self) {
        // download attachments to local folder
        foreach ($_links_self as $entry) {
          $href = $entry["href"];

          $path = basename($href);

          $file = dirname($slice_file) . '/' . basename($slice_file, '.json') . '-' . $path;

          copy($href, $file);
        }
      }

      unset($slice["_links"]);
    }

    if (file_put_contents($slice_file, json_encode($slice, JSON_PRETTY_PRINT)) === false) {
      _die($options, "Export failed : Export slice(=%s) could not be written", $slice_file);
    }
  }
}

error_reporting(E_ALL ^ E_WARNING);

/*
main([
  "./impex-cli.php",
  "export",
  "-verbose",
  "-username=admin",
  "-password=password",
  "-rest-url=",
  "-profile=cm4all-wordpress",
  ".",
]);
*/

// if we are running in cli mode and not as phpunit test then run main
if (php_sapi_name() === "cli" && !str_ends_with($_SERVER['argv'][0], 'phpunit')) {
  main($argv);
}

/*
main([
  "./impex-cli.php",
  "import",
  "-verbose",
  "-username=admin",
  "-password=password",
  "-rest-url=",
  "-profile=all",
  "~/Sync/tmp/snapshots/my-export",
]);
*/
