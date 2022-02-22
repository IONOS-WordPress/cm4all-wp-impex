#!/usr/bin/env php
<?php

// ./impex-cli.php import -H=bzzle -H=izzle -foo=bar -foo=haar -billy=kid -verbose -verbose -username=admin -password=password -rest-url=http://web.de my-directory
// curl -q -X 'GET'   'http://localhost:8888/wp-json/cm4all-wp-impex/v1/export/profile'   -H 'accept: application/json'   -H 'authorization: Basic YWRtaW46cGFzc3dvcmQ=' | jq

namespace cm4all\wp\impex\cli;

function _noop(...$args)
{
}
class ImpexCLI
{
  const IMPEX_ENDPOINT = '/cm4all-wp-impex/v1/';
  // operation[0] will be the operation like "help", "import" etc
  // operation[>0] its interpreted as the arguments for the operation 
  protected $operation = ['help'];
  // option arguments will be stored here
  // options with same name will be converted into an array
  protected $options = ['header' => []];
  // non option arguments will be stored here
  protected $arguments = [];

  public function __construct()
  {
    global $argc, $argv;

    switch ($argc) {
      case 1:
        break;
      default:
        switch ($argv[1]) {
          case 'import':
          case 'export':
            $this->operation = [$argv[1]];
            $this->_parseOptions(array_slice($argv, 2));
            break;
          case 'help':
            break;
          default:
            $this->operation[] = sprintf("Invalid option(s): %s", join(' ', array_slice($argv, 1)));
        }
    };

    call_user_func_array([$this, $this->operation[0]], array_slice($this->operation, 1));
  }

  function _parseOptions($options)
  {
    foreach ($options as $option) {
      if (preg_match('/^-H=(.+)$/', $option, $matches)) {
        $this->options['header'][] = trim($matches[1], '\"\'');
      } else if (preg_match('/^-([^=]+)$/', $option, $matches)) {
        [, $name] = $matches;

        if (!isset($this->options[$name])) {
          $this->options[$name] = true;
        } else {
          if (!is_array($this->options[$name])) {
            $this->options[$name] = [$this->options[$name]];
          }
          $this->options[$name][] = true;
        }
      } else if (preg_match('/^-([^=]+)=(.+)$/', $option, $matches)) {
        [, $name, $value] = $matches;
        $name = trim($name, '\"\'');
        $value = trim($value, '\"\'');

        if (!isset($this->options[$name])) {
          $this->options[$name] = $value;
        } else {
          if (!is_array($this->options[$name])) {
            $this->options[$name] = [$this->options[$name]];
          }
          $this->options[$name][] = "$value";
        }
      } else {
        $this->arguments[] = $name = trim($option, '\"\'');;
      }
    }

    // convert provided username and password options to basic auth header
    if (isset($this->options['username']) && isset($this->options['password'])) {
      foreach ($this->options['header'] as $header) {
        if (preg_match('/^authorization: Basic$/', $header, $matches)) {
          fprintf(STDERR, "Can't use both username and password options and Authorization header\n");
          exit(1);
        }
      }

      $this->options['header'][] = "authorization: Basic " . base64_encode($this->options['username'] . ":" . $this->options['password']);
      $this->_log('Used provided username(=%s) and password(=%s) options to create Authorization header(=%s).', $this->options['username'], $this->options['password'], end($this->options['header']),);
      unset($this->options['username']);
      unset($this->options['password']);
    }

    $this->options['rest-url'] ??= 'http://localhost:8888/wp-json';

    $accept_header_defined = false;
    foreach ($this->options['header'] as $header) {
      if (preg_match('/^accept:$/', $header, $matches)) {
        $accept_header_defined = true;
      }
    }

    if (!$accept_header_defined) {
      $this->options['header'][] = 'accept: application/json';
    }
  }

  function help($message = null)
  {
    if ($message) {
      echo $message . PHP_EOL;
    }

    echo "Usage: impex-cli.php [operation] [options]
  
  ";
  }

  function _log($message, ...$args)
  {
    if (isset($this->options['verbose'])) {
      printf($message . PHP_EOL, ...$args);
    }
  }

  function _curl(string $endpoint, $method = null, $callback = __NAMESPACE__ . '\_noop')
  {
    $curl = curl_init();

    // Optional Authentication:
    // curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    // curl_setopt($curl, CURLOPT_USERPWD, "username:password");

    curl_setopt($curl, \CURLOPT_URL, $this->options['rest-url'] . ImpexCLI::IMPEX_ENDPOINT . $endpoint);

    if ($method !== null) {
      curl_setopt($curl, $method, 1);
    }
    curl_setopt($curl, \CURLOPT_RETURNTRANSFER, 1);

    curl_setopt($curl, \CURLOPT_HTTPHEADER, $this->options['header']);

    if (isset($this->options['verbose'])) {
      curl_setopt($curl, \CURLOPT_VERBOSE, 1);
    }

    call_user_func($callback, $curl);

    curl_setopt($curl, CURLOPT_FAILONERROR, true);

    $result = curl_exec($curl);
    $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    if (curl_errno($curl)) {
      $error = curl_error($curl);
      if (isset($this->options['verbose'])) {
        $this->_log("curl returned an error : '%s'", $error);
      }
    }

    curl_close($curl);

    return [$result, $http_status];
  }

  function import()
  {
    echo "import" . PHP_EOL;

    var_dump($this);

    [$result, $status] = $this->_curl('export/profile');

    $json = json_decode($result);

    echo json_encode($json, JSON_PRETTY_PRINT) . PHP_EOL;
  }

  function export()
  {
    echo "export" . PHP_EOL;

    var_dump($this);
  }
}

new ImpexCLI();
