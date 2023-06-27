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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Render\Markup;

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
  public function content(Request $request): array {
    $this->get_update_info_about_enabled_modules();
    return [
      '#theme' => 'php_memory_readiness_checker',
      '#environment_configuration' => $this->get_environment_configuration(),
      '#modules_size' => $this->human_filesize($this->getModulesSize($request)[0]['total_size']),
      '#each_module' => $this->getModulesSize($request)[1],
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

  private function getModulesSize(Request $request) {
    //    return \Drupal::service('module_handler')->loadAll();
    //    return \Drupal::service('module_handler')->getModuleList();
    $list_of_modules = \Drupal::service('module_handler')
      ->getModuleDirectories();
    $array = [0 => ['total_size' => 0],
              1 => []];
    foreach ($list_of_modules as $module_name => $path) {
//      if ($this->is_module_has_update($module_name)) {
//        $module_name = \Drupal::service('module_handler')->getName($module_name);
//        echo "<pre>";
//        print_r(\Drupal::service('module_handler')->getModule($module_name));
//        echo "</pre>";
//        $module_size = $this->getDirectorySize($path);
      /** @var Request $request */
//      $module_size = $this->getMemoryUsage($request, $module_name);
       $module_size = $this->my_module_measure_memory_usage();
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

  public function getMemoryUsage(Request $request, string $module_name)
  {
    $module = $request->get($module_name);

    return memory_get_peak_usage(true);

//    $response = new Response();
//    $response->setContent(json_encode([
//      'module' => $module,
//      'memoryUsage' => $memoryUsage,
//    ]));

//    return [
//      'module' => $module,
//      'memoryUsage' => $memoryUsage,
//    ];
//    return $response;
  }


  private function my_module_measure_memory_usage() {
    $memoryUsage = memory_get_usage(true);
    $formattedMemoryUsage = Markup::create('<pre>' . format_size($memoryUsage) . '</pre>');
    dpm($formattedMemoryUsage, 'Memory Usage');
    return $memoryUsage;
  }

//  private function get_update_info_about_enabled_modules() {
////    $available = update_get_available();
////    $module_info = update_calculate_project_data($available);
////    foreach ($module_info as $module => $info) {
////      echo "<h1>$module</h1>";
////      echo "<pre>"; print_r($info); echo "</pre>";
////    }
//
//    // Your custom module code.
//
////    $output = NULL;
////    $result_code = NULL;
////    $return_value = exec('ddev exec ls');
////    // Display the output.
////    echo gettype($output) . "</br>";
////    if ($return_value === '') echo '$return_value is empty string!';
////    echo "<pre>"; print_r($output); echo "</pre>";
////
////    // Run the Composer command.
////    $return_value = exec('ddev exec composer update --dry-run --profile',
////                  $output,
////                    $result_code);
////
////    // Display the output.
////    echo gettype($return_value) . "</br>";
////    if ($return_value === '') echo '$return_value is empty string!';
////    echo "<pre>"; print_r($output); echo "</pre>";
//    // Run the Composer command.
////    $command = 'ddev exec composer update --dry-run --profile';
////    $command = 'composer update --dry-run --profile';
////    $command = 'echo foo';
////    $command = 'cd .. && composer update --dry-run --profile';
////    $command = 'cd .. && ls -la';
////    $command = 'cd .. && composer -V';
//    $command = 'cd .. && composer update --dry-run';
//    $output = NULL;
//    $result_code = NULL;
//    $return_value = exec($command, $output, $result_code);
//
//    // Display the output.
//    echo gettype($return_value) . "</br>";
//    if ($return_value === '') echo '$return_value is empty string!</br>';
//    else echo $return_value . "</br>";
//    if ($result_code == 0) echo "it's a zero</br>";
//    echo "$result_code</br>";
//    echo "<pre>"; print_r($output); echo "</pre>";
//  }

  private function get_update_info_about_enabled_modules() {
    $result = $this->run_composer_command();

    if (is_null($result)) {
      \Drupal::messenger()->addError('Composer command could not run!');
      exit(1);
    } elseif (is_array($result)) {
      $output = $result['output'];
      $errorOutput = $result['errorOutput'];
    } elseif (is_string($result)) {
      $output = $result;
    }
    unset($result);
    if (isset($output)){
      $lines = explode(PHP_EOL, $output);
      array_pop($lines);
      echo "</br></br></br></br>";
      echo "<pre>"; print_r($lines); echo "</pre>";

      $total_memory = 0.0;
      foreach ($lines as $line) {
        $closingBracketPos = strpos($line, ']');
        $substring = substr($line, 1, $closingBracketPos - 1);

        $memory = (float) $substring;
        $total_memory += $memory;

        $restOfLine1 = trim(substr($line, strlen($substring) + 2));

        // Lock file operations:
        $beginOfLine = substr($restOfLine1, 0, strlen('Lock file operations:'));
        if ($beginOfLine === 'Lock file operations:') {
          $this->retrieve_IUR('Lock file operations:', $restOfLine1, $beginOfLine);
        }

        unset($beginOfLine); // end of Lock file operations

        // Package operations:
        $beginOfLine = substr($restOfLine1, 0, strlen('Package operations:'));
        if ($beginOfLine === 'Package operations:') {
          $this->retrieve_IUR('Package operations:', $restOfLine1, $beginOfLine);
        }
      }

      $avg_memory_usage = $total_memory / count($lines);
      echo "Average memory usage equals to: $avg_memory_usage";
      echo "</br>";
    }
  }

  private function retrieve_IUR($label, $restOfLine1, $beginOfLine1) {
    echo "$label";
    echo "</br>";

    // Installs
    $restOfLine2 = trim(substr($restOfLine1, strlen($beginOfLine1)));
    $numberOfInstalls = (int) $restOfLine2;
    echo "We have $numberOfInstalls Installs.";
    echo "</br>";

    // Updates
    $restOfLine2 = substr($restOfLine2, strlen('0 installs,'));
    $numberOfUpdates = (int) $restOfLine2;
    echo "We have $numberOfUpdates Updates.";
    echo "</br>";

    // Removes
    $restOfLine2 = substr($restOfLine2, strlen('10 updates,'));
    $numberOfRemoves = (int) $restOfLine2;
    echo "We have $numberOfRemoves Removes.";
    echo "</br>";
  }

  // Your custom module code.
  private function run_composer_command(): string | array | NULL {
    // Run the shell command within the DDEV environment.
    $descriptors = [
      1 => ['pipe', 'w'], // Capture standard output
      2 => ['pipe', 'w'], // Capture standard error output
    ];
    $process = proc_open('composer update --dry-run --profile',
      $descriptors, $pipes, '/var/www/html');

    if (is_resource($process)) {
      // Get the standard output
      $output = stream_get_contents($pipes[1]);

      // Get the standard error output
      $errorOutput = stream_get_contents($pipes[2]);

      // Close the pipes
      fclose($pipes[1]);
      fclose($pipes[2]);

      // Close the process
      proc_close($process);

      if ($output == '') {
        if ($errorOutput == '') {
          return '';
        } else {
          return $errorOutput;
        }
      } else {
        return ['output' => $output, 'errorOutput' => $errorOutput];
      }
    }
    return NULL;
  }
}
