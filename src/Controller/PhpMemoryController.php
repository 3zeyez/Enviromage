<?php
/**
 * @file
 * Contains \Drupal\php_memory_readiness_checker\Controller\phpController.
 */

declare (strict_types = 1);

namespace Drupal\php_memory_readiness_checker\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Defines phpController class.
 */
class phpController extends ControllerBase
{
  /**
   * Display the markup.
   *
   * @return array
   *   Return markup array.
   */
  public function content() {
    $markup = "<h2>Hi! I am working on it!</h2><br /></p>";

    $memory_limit = get_environment_configurations('memory_limit');

    $fontSize25 = "font-size: 25px;";
    if (check_memory_readiness($memory_limit)) {
      $markup .= "<p style='color: green; $fontSize25'>The memory limit is `$memory_limit`. There is enough memory to update.</p>";
    } else {
      $markup .= "<p style='color: red; $fontSize25'>The memory limit is `$memory_limit`. There is not enough memory to update.</p>";
    }

    return [
        '#type' => 'markup',
        '#markup' => $this->t($markup),
    ];
  }

  /**
   * Converts a human-readable size representation to bytes.
   *
   * Accepts a string representing the size, such as "2M" for 2 megabytes.
   * The supported size suffixes are "k" for kilobytes, "m" for megabytes, and "g" for gigabytes.
   * The function returns the equivalent size in bytes as an integer.
   *
   * @param string $size The human-readable size string to convert.
   *
   * @return int The size in bytes as an integer.
   */
  private function return_bytes(string $size): int
  {
    $last = strtolower($size[strlen($size) - 1]);
    $size = (int) substr($size, 0, -1);

    // Check the operating system to determine the base multiplier for size conversion.
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
      // On Windows, use binary (1024) base for size conversion.
      switch ($last) {
        case 'g':
          $size *= 1024;
        case 'm':
          $size *= 1024;
        case 'k':
          $size *= 1024;
      }
    } else {
      // On non-Windows systems, use decimal (1000) base for size conversion.
      switch ($last) {
        case 'g':
          $size *= 1000;
        case 'm':
          $size *= 1000;
        case 'k':
          $size *= 1000;
        }
    }

    return $size;
  }

  /**
   * Check memory readiness based on the specified memory limit.
   *
   * This function checks if the available memory is sufficient based on the provided memory limit.
   * If the memory limit is set to -1, indicating no limit, the function considers the memory as ready.
   * Otherwise, it calculates the available memory by subtracting the current memory usage from the memory limit.
   * If the available memory is below the threshold of 200MB, an error message is returned.
   * Otherwise, it indicates that the memory is ready.
   *
   * @param int $memory_limit The memory limit in bytes.
   *
   * @return string Returns a message indicating the memory readiness:
   *   - "Memory is ready" if the memory limit is -1 or the available memory is sufficient.
   *   - "Error: Not enough memory available" if the available memory is below the threshold.
   */
  public function check_memory_readiness(int $memory_limit): string
  {
    if ($memory_limit === -1) {
      return 'Memory is ready';
    }

    $memory_limit = return_bytes($memory_limit);
    $memory_usage = memory_get_usage();
    $available_memory = $memory_limit - $memory_usage;

    // The threshold is set to 200MB (1048576 bytes = 1MB)
    if ($available_memory < 1048576 * 200) {
      return 'Error: Not enough memory available';
    } else {
      return 'Memory is ready';
    }
  }

  /**
   * Get the size of a variable in memory.
   *
   * Calculates the approximate size of a variable in memory. The following variable types are supported:
   * - null: 0 bytes in the heap
   * - bool: 4 bytes in the heap
   * - int: 4 bytes in the heap
   * - float: 8 bytes in the heap
   * - string: Each character occupies 1 byte in the heap
   * - array: The size is calculated recursively for each key and value in the array
   * - object: (To Do)
   * - callable: (To Do)
   * - resource: (To Do)
   *
   * @param mixed $var The variable to inspect.
   *
   * @return int The approximate size of the variable in bytes.
   */
  public function get_var_size(mixed $var): int
  {
    // As every variable in PHP is a zval, a zval takes automatically 128 bits (16 bytes) in memory.
    $size = 16;

    $var_type = gettype($var);

    if ($var_type === 'boolean' || $var_type === 'integer') {
      $size += 4;
    } elseif ($var_type === 'double') {
      $size += 8;
    } elseif ($var_type === 'string') {
      $size += strlen($var);
    } elseif ($var_type === 'array') {
      foreach ($var as $key => $value) {
        $size += get_var_size($key);
        $size += get_var_size($value);
      }
    }

    return $size;
  }

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
  public function get_environment_configurations(string...$args): array | int | false
  {
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
