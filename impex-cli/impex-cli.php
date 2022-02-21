#!/usr/bin/env php
<?php

// curl -q -X 'GET'   'http://localhost:8888/wp-json/cm4all-wp-impex/v1/export/profile'   -H 'accept: application/json'   -H 'authorization: Basic YWRtaW46cGFzc3dvcmQ=' | jq

class ImpexCLI
{
  protected $operation = ['help'];
  protected $options = ['header' => []];

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
        $this->options['header'][] = $matches[1];
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

        if (!isset($this->options[$name])) {
          $this->options[$name] = $value;
        } else {
          if (!is_array($this->options[$name])) {
            $this->options[$name] = [$this->options[$name]];
          }
          $this->options[$name][] = "$value";
        }
      }
    }
  }

  function help($message = null)
  {
    if ($message) {
      echo $message . PHP_EOL;
    }

    echo "Usage: impex-cli.php [options]
  
  ";
  }

  function import()
  {
    echo "import" . PHP_EOL;

    var_dump($this);
  }

  function export()
  {
    echo "export" . PHP_EOL;

    var_dump($this);
  }
}

new ImpexCLI();
