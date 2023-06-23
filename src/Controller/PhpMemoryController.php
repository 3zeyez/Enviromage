<?php /** @noinspection PhpUnused */

/** @noinspection PhpUndefinedFunctionInspection */
/**
 * @file
 * Contains \Drupal\php_memory_readiness_checker\Controller\phpController.
 */

declare (strict_types=1);

namespace Drupal\php_memory_readiness_checker\Controller;

//phpinfo();

use BadFunctionCallException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Asset\AttachedAssetsInterface;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Defines phpController class.
 */
class PhpMemoryController extends ControllerBase {
//  use VersionParsingTrait;

  /**
   * Display the markup.
   *
   * @return array
   *   Return markup array.
   */
  public function content(): array {
    return [
      '#theme' => 'php_memory_readiness_checker',
      '#environment_configuration' => $this->get_environment_configuration(),
      '#modules_size' => $this->human_filesize($this->getModulesSize()[0]['total_size']),
      '#each_module' => $this->getModulesSize()[1],
    ];
    //    return [
    //      '#markup' => $this->t(implode(', ', $this->listModules())),
    //    ];
//    echo $this->human_filesize($this->getModulesSize()) . "<br/>";
//    echo "<pre>";
//    //    print_r($this->listModules());
////    print_r($this->get_environment_configuration());
//    print_r(\Drupal::service('module_handler')->getModule('automatic_updates'));
//    echo "</pre>";
//    $this->getModulesSize();
//    return [];
  }

  /**
   * Converts a human-readable size representation to bytes.
   *
   * Accepts a string representing the size, such as "2M" for 2 megabytes.
   * The supported size suffixes are "k" for kilobytes, "m" for megabytes, and
   * "g" for gigabytes. The function returns the equivalent size in bytes as an
   * integer.
   *
   * @param string $size The human-readable size string to convert.
   *
   * @return int The size in bytes as an integer.
   */
  private function return_bytes(string $size): int {
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
    }
    else {
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

  private function human_filesize($bytes, $decimals = 2) {
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
      $factor = 1024;
      $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    } else {
      $factor = 1000;
      $units = ['Bi', 'KiB', 'MiB', 'GiB', 'TiB'];
    }

    for ($i = 0; $bytes >= $factor && $i < count($units) - 1; $i++) {
      $bytes /= $factor;
    }

    return round($bytes, 2) . ' ' . $units[$i];
  }

  private function getDirectorySize($directory) {
    $total_size = 0;
    $files = scandir($directory);
    foreach ($files as $file) {
      if ($file != "." && $file != "..") {
        if (is_dir($directory . "/" . $file)) {
          $total_size += $this->getDirectorySize($directory . "/" . $file);
        }
        else {
          $total_size += filesize($directory . "/" . $file);
        }
      }
    }
    return $total_size;
  }

  /**
   * Check memory readiness based on the specified memory limit.
   *
   * This function checks if the available memory is sufficient based on the
   * provided memory limit. If the memory limit is set to -1, indicating no
   * limit, the function considers the memory as ready. Otherwise, it
   * calculates the available memory by subtracting the current memory usage
   * from the memory limit. If the available memory is below the threshold of
   * 200MB, an error message is returned. Otherwise, it indicates that the
   * memory is ready.
   *
   * @param string | int $memory_limit
   * $memory_limit The memory limit.
   *
   * @return string Returns a message indicating the memory readiness:
   *   - "Memory is ready" if the memory limit is -1 or the available memory is
   *   sufficient.
   *   - "Error: Not enough memory available" if the available memory is below
   *   the threshold.
   */
  public function check_memory_readiness(string|int $memory_limit): string {
    if ($memory_limit === -1) {
      return 'Memory is ready';
    }

    $memory_limit = $this->return_bytes($memory_limit);
    $memory_usage = memory_get_usage();
    $available_memory = $memory_limit - $memory_usage;

    // The threshold is set to 200MB (1048576 bytes = 1MB)
    if ($available_memory < 1048576 * 200) {
      return 'Error: Not enough memory available';
    }
    else {
      return 'Memory is ready';
    }
  }

  /**
   * Get the size of a variable in memory.
   *
   * Calculates the approximate size of a variable in memory. The following
   * variable types are supported:
   * - null: 0 bytes in the heap
   * - bool: 4 bytes in the heap
   * - int: 4 bytes in the heap
   * - float: 8 bytes in the heap
   * - string: Each character occupies 1 byte in the heap
   * - array: The size is calculated recursively for each key and value in the
   * array
   * - object: Loading...
   * - callable: (To Do)
   * - resource: (To Do)
   *
   * @param mixed $var The variable to inspect.
   *
   * @return int The approximate size of the variable in bytes.
   */
  public function get_var_size(mixed $var): int {
    // As every variable in PHP is a zval, a zval takes automatically 128 bits (16 bytes) in memory.
    $size = 16;

    $var_type = gettype($var);

    if ($var_type === 'boolean' || $var_type === 'integer') {
      $size += 4;
    }
    elseif ($var_type === 'double') {
      $size += 8;
    }
    elseif ($var_type === 'string') {
      $size += strlen($var);
    }
    elseif ($var_type === 'array') {
      foreach ($var as $key => $value) {
        $size += get_var_size($key);
        $size += get_var_size($value);
      }
    }
    elseif ($var_type === 'object') {
      // Loading...
      $object_vars = get_object_vars($var);
      foreach ($object_vars as $object_var) {
        $size += get_var_size($var->$object_var);
      }
    }

    return $size;
  }

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
    $config = $this->config('php_memory_readiness_checker.settings');
    $configurations = $config->get('settings_list');

    $retrieved_configurations = [];
//    echo "<pre>";
//    //    print_r($this->listModules());
//    print_r($configurations);
//    echo "</pre>";

    foreach ($configurations as $index => $key) {
      $retrieved_configurations[$key] = ini_get($key);
    }
    return $retrieved_configurations;
  }

  private function getModulesSize() {
    //    return \Drupal::service('module_handler')->loadAll();
    //    return \Drupal::service('module_handler')->getModuleList();
    $list_of_modules = \Drupal::service('module_handler')
      ->getModuleDirectories();
    $array = [0 => ['total_size' => 0],
              1 => []];
    foreach ($list_of_modules as $module_name => $path) {
//      if ($this->is_module_has_update($module_name)) {
        $module_name = \Drupal::service('module_handler')->getName($module_name);
        $module_size = $this->getDirectorySize($path);
        $array[1][$module_name] = $this->human_filesize($module_size);
        $array[0]['total_size'] += $module_size;
//          $files = \Drupal::service('file_system')->scanDirectory($path, '.*');
//          echo "<pre>";
//          print_r($files);
//          echo "</pre>";

//      }
    }
    return $array;
  }

  private function is_module_has_update(string $module): bool {
    // Get the current version of the module.
//    $current_version = \Drupal::service('module_handler')
//      ->moduleExists($module) ? \Drupal::service('module_handler')
//      ->getModuleInfoBykey($module)->version : FALSE;
    $current_version = 0;
    // Get the latest version of the module from the update feed.
    $latest_version = \Drupal::service('update.fetcher')
      ->fetchLatestVersion($module);

    // Check if the current version is less than the latest version.
    return $current_version < $latest_version;
  }

}
