#!/usr/bin/env php
<?php

// ./impex-cli.php export -username=admin -password=password -rest-url=http://localhost:8888/wp-json -profile=cm4all-wordpress -overwrite -verbose  .
// ./impex-cli.php import -H=bzzle -H=izzle -foo=bar -foo=haar -billy=kid -verbose -verbose -username=admin -password=password -rest-url=http://web.de my-directory
// curl -q -X 'GET'   'http://localhost:8888/wp-json/cm4all-wp-impex/v1/export/profile'   -H 'accept: application/json'   -H 'authorization: Basic YWRtaW46cGFzc3dvcmQ=' | jq

namespace cm4all\wp\impex\cli;

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

function _die($options, $message, ...$args)
{
  fprintf(STDERR, $message, ...array_map(fn ($_) => is_array($_) || is_object($_) ? json_encode($_) : $_, $args));
  exit(1);
}

function main($argv)
{
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
          $operation = $argv[1];
          _parseCommandlineArguments(array_slice($argv, 2), $options, $arguments);
          break;
        case 'help':
          break;
        default:
          $arguments[] = sprintf("Invalid option(s): %s", join(' ', array_slice($argv, 1)));
      }
  };

  [$json, $status] = call_user_func(__NAMESPACE__ . '\\' . str_replace(['-'], ['_'], $operation), $options, ...$arguments);
  if ($status === 200) {
    echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
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
        fprintf(STDERR, "Can't use both username and password options and Authorization header\n");
        exit(1);
      }
    }

    $options['header'][] = "authorization: Basic " . base64_encode($options['username'] . ":" . $options['password']);
    _log('Used provided username(=%s) and password(=%s) options to create Authorization header(=%s).', $options['username'], $options['password'], end($options['header']),);
    unset($options['username']);
    unset($options['password']);
  }

  $options['rest-url'] ??= 'http://localhost:8888/wp-json';

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

  echo "Usage: impex-cli.php [operation] [options]
  
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

  if (isset($options['verbose'])) {
    curl_setopt($curl, \CURLOPT_VERBOSE, 1);
  }

  call_user_func($callback, $curl);

  curl_setopt($curl, CURLOPT_FAILONERROR, true);

  $result = curl_exec($curl);
  $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

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

function import($options, ...$args)
{
  echo "import" . PHP_EOL;

  [$result, $status] = _curl($options, 'export/profile');
}

function export_profile($options, $command = 'list', ...$args)
{
  switch ($command) {
    case 'list':
      return _curl($options, 'export/profile');
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
    (function_exists('mb_strtolower')) ?
    mb_strtolower($clean, 'UTF-8') :
    strtolower($clean) :
    $clean;
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

  _log($options, "Exporting profile(=%s) to directory(=%s)", $profile, $export_directory);
  /*
  // create export
  [$result, $status, $error] = _curl(
    $options,
    'export',
    \CURLOPT_POST,
    fn ($curl) => curl_setopt($curl, \CURLOPT_POSTFIELDS, http_build_query(['profile' => $profile]))
  );

  if ($error) {
    _die($options, "Export failed : HTTP status(=%s) : %s", $status, $error);
  }
*/


  $export_filename = "Export 'cm4all-wordpress' created by user 'admin' at 2022-02-23T11:54:16+00:00"; // $result['name']; 
  $export_filename = substr(sanitizeFilename($export_filename), 0, min(32, strlen($export_filename)));
  $export_id = '0da4ccf0-8c7a-4870-b3fe-b4ba0176c897'; // $result['id'];

  $path = "export/${export_id}/slice";

  // get export metadata
  [$result, $status, $error] = _curl(
    $options,
    // per_page=1 is a hack to get the first page of results
    "export/${export_id}/slice",
    null,
    fn ($curl) => curl_setopt($curl, \CURLOPT_HEADER, 1) && curl_setopt($curl, \CURLOPT_NOBODY, 1)
  );

  if ($error) {
    _die($options, "Receiving export slices failed : HTTP status(=%s) : %s", $status, $error);
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
  "-overwrite",
  "-username=admin",
  "-password=password",
  "-rest-url=http://localhost:8888/wp-json",
  "-profile=cm4all-wordpress",
  ".",
]);
*/

main($argv);

/*
main([
  "./impex-cli.php",
  "import",
  "-verbose",
  "-username=admin",
  "-password=password",
  "-rest-url=http://localhost:8888/wp-json", 
  "my-directory",
]);
*/
