<?php

declare(strict_types = 1);

namespace Drupal\php_memory_readiness_checker\Controller;

use Drupal\Core\Controller\BaseController;

/**
 * Defines an environment configuration checker controller class.
 */

class EnvController extends BaseController {

  /**
   * Retrieves environment configuration values.
   *
   * Accepts a maximum of 6 arguments of type string in any order:
   * 1. memory_limit
   * 2. max_execution_time
   * 3. realpath_cache_size
   * 4. realpath_cache_ttl
   * 5. upload_max_filesize
   * 6. post_max_size
   *
   * @param string ...$args Environment configuration keys
   *
   * @return array|int|false Returns an array containing configuration values if all arguments are provided correctly or if no arguments are provided. If some arguments are provided, it returns an array with the specified configurations. Returns an int if only one argument is provided. Returns false if there is a typo in any of the arguments.
   *
   * @throws BadFunctionCallException When too many parameters are provided.
   * @throws InvalidArgumentException When any argument is not of type string.
   */
  public function get_environment_configurations(string...$args): array | int | false {
    $num_args = count($args);

    if ($num_args > 6) {
      throw new BadFunctionCallException("Too many parameters!");
    }

    foreach ($args as $arg) {
      if (!is_string($arg)) {
          throw new InvalidArgumentException('All arguments must be of type string.');
      }
    }

    $configurations = [
      'memory_limit' => ini_get('memory_limit'),
      'max_execution_time' => ini_get('max_execution_time'),
      'realpath_cache_size' => return_bytes(ini_get('realpath_cache_size')),
      'realpath_cache_ttl' => ini_get('realpath_cache_ttl'),
      'upload_max_filesize' => return_bytes(ini_get('upload_max_filesize')),
      'post_max_size' => return_bytes(ini_get('post_max_size')),
    ];

    if ($num_args === 1) {
      return $configurations[$args[0]];
    } elseif ($num_args > 1) {
      $filteredConfigurations = array_intersect_key($configurations, array_flip($args));

      if (count($filteredConfigurations) !== $num_args) {
        return false;
      }

      return $filteredConfigurations;
    }

    return $configurations;
  }
}