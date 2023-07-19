<?php
 /**
 * @file
 * Contains \Drupal\php_memory_readiness_checker\Controller\phpController.
 */

declare (strict_types=1);

namespace Drupal\php_memory_readiness_checker\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Defines PhpMemoryController class.
 */
class PhpMemoryController extends ControllerBase {

  /**
   * Display the markup.
   * For now, I am using it as a page to try out things:
   *   . debugging
   *   . displaying variable's content
   *   . trying new methods and their behavior
   *
   * @return array
   *   Return markup array.
   */
  public function content(): array {
    echo "</br></br></br></br>";
//    $return = $this->get_update_info_about_enabled_modules();
//    echo "<pre>"; print_r($return); echo "</pre>";
    $this->run_composer_command();
    $environment_configuration = $this->get_environment_configuration();
    $result = $this->getModulesSize();
    $modules_size = $this->human_filesize($result[0]['total_size']);
    $each_module = $result[1];
    return [
      '#theme' => 'php_memory_readiness_checker',
      '#environment_configuration' => $environment_configuration,
      '#modules_size' => $modules_size,
      '#each_module' => $each_module,
    ];
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

  /**
   * This function converts bytes into a human-readable format.
   * @param int $bytes
   * @param int $decimals
   *
   * @return string
   */
  public function human_filesize(int $bytes, int $decimals = 2): string {
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

    return round($bytes, $decimals) . ' ' . $units[$i];
  }

  /**
   * This function calculates the memory size of a directory
   * @param string $directory
   *  a string contains the directory's path
   *
   * @return int
   *   size of directory in bytes
   */
  private function getDirectorySize(string $directory): int {
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
        $size += $this->get_var_size($key);
        $size += $this->get_var_size($value);
      }
    }
    elseif ($var_type === 'object') {
      // Loading...
      $object_vars = get_object_vars($var);
      foreach ($object_vars as $object_var) {
        $size += $this->get_var_size($var->$object_var);
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
    $configurations = $this
      ->config('php_memory_readiness_checker.settings')
      ->get('settings_list');

    $retrieved_configurations = [];

    foreach ($configurations as $key) {
      $retrieved_configurations[$key] = ini_get($key);
    }
    return $retrieved_configurations;
  }

  /**
   * @return array
   *   an array that contains the total size of all modules in bytes
   *   and the size of each module in a human-readable format
   */
  public function getModulesSize(): array {
    $list_of_modules1 = \Drupal::service('module_handler')
      ->getModuleDirectories();

    $list_of_modules2 = $this
      ->config('php_memory_readiness_checker.settings')
      ->get('modules_list');

    $list_of_modules = [];
    foreach ($list_of_modules1 as $module_name => $path) {
      if ( in_array($module_name, $list_of_modules2)) {
        $list_of_modules[$module_name] = $path;
      }
    }

    $array = [0 => ['total_size' => 0],
              1 => []];
    foreach ($list_of_modules as $module_name => $path) {
        $module_size = $this->getDirectorySize($path);
        $array[1][$module_name] = $this->human_filesize($module_size);
        $array[0]['total_size'] += $module_size;
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

  public function get_update_info_about_enabled_modules(): array {
    $result = $this->run_composer_command();
    if (is_null($result)) {
      return ['Composer command could not run!'];
    } elseif (is_string($result)) {
      if ($result === 'command not working') {
        return ['Composer command could not run!'];
        /**
         * @todo // display proper output of failure, so that it helps the user avoid it.
         */
      }
      $output = $result;
    }
    unset($result);

    $return = [];

    if (isset($output)) {
      $lines = explode(PHP_EOL, $output);
      array_pop($lines);

      $total_memory = 0.0;

      $key = '';
      $lf_label = 'lock_file_operation';
      $p_label = 'package_operation';
      $toBeInstalledLf = [];
      $toBeUpdatedLf = [];
      $toBeRemovedLf = [];
      $toBeInstalledPk = [];
      $toBeUpdatedPk = [];
      $toBeRemovedPk = [];
      foreach ($lines as $line) {
        $closingBracketPos = strpos($line, ']');
        $substring = substr($line, 1, $closingBracketPos - 1);

        $number = (float) $substring;
        $unit = substr($substring, strlen((string) $number), 1);
        $memory_string = $number . $unit;
        $memory = $this->return_bytes($memory_string);
        $total_memory += $memory;

        $restOfLine1 = trim(substr($line, strlen($substring) + 2));

        // Lock file operations:
        $beginOfLine = substr($restOfLine1, 0, strlen('Lock file operations:'));
        if ($beginOfLine === 'Lock file operations:') {
          $this->retrieve_IUR($lf_label, $restOfLine1, $beginOfLine, $return);
          $key = 'Lock file operations';
        }
        unset($beginOfLine); // end of Lock file operations

        // Package operations:s
        $beginOfLine = substr($restOfLine1, 0, strlen('Package operations:'));
        if ($beginOfLine === 'Package operations:') {
          $this->retrieve_IUR($p_label, $restOfLine1, $beginOfLine, $return);
          $key = 'Package operations';
        }
        unset($beginOfLine); // end of Package operations

        // Files to be installed
        $beginOfLine = substr($restOfLine1, 0, strlen('- Installing'));
        if ($beginOfLine === '- Installing') {
          $this->add_op_to_array(
            $toBeInstalledLf,
            $toBeInstalledPk,
            $key,
            $restOfLine1,
            $beginOfLine
          );
        }
        unset($beginOfLine); // end of Files to be installed

        // Files to be updated
        $beginOfLine = substr($restOfLine1, 0, strlen('- Upgrading'));
        if ($beginOfLine === '- Upgrading') {
          $this->add_op_to_array(
            $toBeUpdatedLf,
            $toBeUpdatedPk,
            $key,
            $restOfLine1,
            $beginOfLine
          );
        }
        unset($beginOfLine); // end of Files to be updated

        // Files to be removed
        $beginOfLine = substr($restOfLine1, 0, strlen('- Removing'));
        if ($beginOfLine === '- Removing') {
          $this->add_op_to_array(
            $toBeRemovedLf,
            $toBeRemovedPk,
            $key,
            $restOfLine1,
            $beginOfLine
          );
        }
        unset($beginOfLine); // end of Files to be removed
      }

      $avg_memory_usage = $total_memory / count($lines);

      $return['memory_avg_usage'] = $this->human_filesize((int) $avg_memory_usage);
      $return['time_usage'] = substr(
        $lines[count($lines) - 1],
        strpos($lines[count($lines) - 1],
          'time: ') + 6
      );
      sort($toBeInstalledLf);
      sort($toBeInstalledPk);
      sort($toBeUpdatedLf);
      sort($toBeUpdatedPk);
      sort($toBeRemovedLf);
      sort($toBeRemovedPk);

      $return['message'][$lf_label] = [];
      $return['message'][$p_label] = [];

      if (empty($toBeInstalledLf)) {
        $return['message'][$lf_label][] = 'There is nothing new to install!';
      }
      else {
        foreach ($toBeInstalledLf as $item) {
          $return['message'][$lf_label][] = "Install - $item";
        }
      }

      if (empty($toBeInstalledPk)) {
        $return['message'][$p_label][] = 'There is nothing new to install!';
      }
      else {
        foreach ($toBeInstalledPk as $item) {
          $return['message'][$p_label][] = "Install - $item";
        }
      }

      if (empty($toBeUpdatedLf)) {
        $return['message'][$lf_label][] = 'There is no update!';
      }
      else {
        foreach ($toBeUpdatedLf as $item) {
          $return['message'][$lf_label][] = "Update - $item";
        }
      }

      if (empty($toBeUpdatedPk)) {
        $return['message'][$p_label][] = 'There is no update!';
      }
      else {
        foreach ($toBeUpdatedPk as $item) {
          $return['message'][$p_label][] = "Update - $item";
        }
      }

      if (empty($toBeRemovedLf)) {
        $return['message'][$lf_label][] = 'There is nothing to remove!';
      }
      else {
        foreach ($toBeRemovedLf as $item) {
          $return['message'][$lf_label][] = "Remove - $item";
        }
      }

      if (empty($toBeRemovedPk)) {
        $return['message'][$p_label][] = 'There is nothing to remove!';
      }
      else {
        foreach ($toBeRemovedPk as $item) {
          $return['message'][$p_label][] = "Remove - $item";
        }
      }
    }

    return $return;
  }

  private function retrieve_IUR(
    string $label,
    string $restOfLine1,
    string $beginOfLine1,
    array &$return): void {

    $return[$label] = [];

    // Installs
    $restOfLine2 = trim(substr($restOfLine1, strlen($beginOfLine1)));
    $numberOfInstalls = (int) $restOfLine2;
    $return[$label]['numberOfInstalls'] = $numberOfInstalls;

    // Updates
    $restOfLine2 = substr($restOfLine2, strlen("$numberOfInstalls installs,"));
    $numberOfUpdates = (int) $restOfLine2;
    $return[$label]['numberOfUpdates'] = $numberOfUpdates;

    // Removes
    $restOfLine2 = substr($restOfLine2, strlen("$numberOfUpdates updates,"));
    $return[$label]['numberOfRemoves'] = ((int) $restOfLine2);
  }

  public function run_composer_command(): string | NULL {
    // Run the shell command within the DDEV environment.
    $descriptors = [
      1 => ['pipe', 'w'], // Capture standard output
      2 => ['pipe', 'w'], // Capture standard error output
    ];
    $process = proc_open('composer update --dry-run --profile',
      $descriptors, $pipes, '/var/www/html');

    if (is_resource($process)) {
      // Get the standard error output
      // It is composer's developer choice
      $errorOutput = stream_get_contents($pipes[2]);

      // Close the pipes
      fclose($pipes[1]);
      fclose($pipes[2]);

      // Close the process and get status code of command running
      $status_code = proc_close($process);

      // Try Catch Composer Command fail @TO-DO
      if ($status_code !== 0) {
        return "command not working";
      }

      return $errorOutput;
    }
    return NULL;
  }

  /**
   * This function chooses if the operation (install, update or remove)
   * belongs to lock file operations or to package operations.
   * @param array $toBeOpLf  array of lock file operations
   * @param array $toBeOpPk  array of package operations
   * @param string $key
   *   key says if the operation is a lock file or a package operation
   * @param string $restOfLine1  the line output by composer
   * @param string $beginOfLine  string to escape from the beginning
   *
   * @return void
   */
  private function add_op_to_array(
    array &$toBeOpLf,
    array &$toBeOpPk,
    string $key,
    string $restOfLine1,
    string $beginOfLine
  ): void {
    $restOfLine2 = trim(substr($restOfLine1, strlen($beginOfLine)));
    if ($key === 'Lock file operations') {
      $toBeOpLf[] = $restOfLine2;
    } else {
      $toBeOpPk[] = $restOfLine2;
    }
  }

  private function sort_array_from_other_array(array &$arr1, array $arr2): void {
    $order = array_flip($arr2);

    // Sort $arr2 using the order in $arr1
    usort($arr1, function($a, $b) use ($order) {
      return $order[$a] - $order[$b];
    });
  }

}
