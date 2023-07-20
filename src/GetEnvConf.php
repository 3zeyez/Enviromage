<?php

/**
 * @file
 */

declare(strict_types=1);

namespace Drupal\php_memory_readiness_checker;

class GetEnvConf {

  /**
   * Retrieves environment configuration values from API configuration file:
   *
   * 1. memory_limit
   * 2. max_execution_time
   * 3. realpath_cache_size
   * 4. realpath_cache_ttl
   * 5. upload_max_filesize
   * 6. post_max_size
   *
   *
   * @return array Returns an array containing configuration values
   */
  public function get_environment_configuration(): array {
    $configurations = $this
      ->config('php_memory_readiness_checker.settings')
      ->get('settings_list');

    $retrieved_configurations = [];

    foreach ($configurations as $key) {
      $retrieved_configurations[$key] = ini_get($key);
    }
    return $retrieved_configurations;
  }
}
