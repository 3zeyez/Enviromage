<?php
 /**
 * @file
 * Contains \Drupal\enviromage\Controller\phpController.
 */

declare (strict_types=1);

namespace Drupal\enviromage\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\enviromage\RunComposerCommand;
use Drupal\enviromage\Utility;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines EnviromageController class.
 */
class EnviromageController extends ControllerBase {

  /**
   * @var \Drupal\enviromage\Utility
   */
  protected $utility;

  protected $composer;

  public function __construct(Utility $utility, RunComposerCommand $composer) {
    $this->utility = $utility;
    $this->composer = $composer;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('enviromage.utility'),
      $container->get('enviromage.run_composer_command'),
    );
  }
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
    $database = \Drupal::database();
    $select_query = $database->select('enviromage_command', 'c');
    $select_query->addField('c', 'command');
    $entries = $select_query->execute()->fetchAll(\PDO::FETCH_ASSOC);

    $select_query = $database->select('enviromage_log', 'log');
    $select_query->addField('log', 'id', 'id');
    $select_query->addField('log', 'uid', 'uid');
    $select_query->addField('log', 'avgM', 'avg_memory');
    $select_query->addField('log', 'timeExec', 'time_exec');
    $select_query->addField('log', 'nbIL', 'nbil');
    $select_query->addField('log', 'nbUL', 'nbul');
    $select_query->addField('log', 'nbRL', 'nbrl');
    $select_query->addField('log', 'nbIP', 'nbip');
    $select_query->addField('log', 'nbUP', 'nbup');
    $select_query->addField('log', 'nbRP', 'nbrp');
    $entries = $select_query->execute()->fetchAll(\PDO::FETCH_ASSOC);

    echo "<pre>"; print_r($entries); echo "</pre>";

    foreach ($entries[0] as $key => $value) {
      $entries[0][$key] = (int) $value;
    }
    echo "<pre>"; print_r($entries); echo "</pre>";

//    $result = $this->composer->get_update_info_about_enabled_modules($entries[count($entries) - 1]['command']);

//    echo "<pre>"; print_r($result); echo "</pre>";
//    $this->run_composer_command();
//    $environment_configuration = $this->get_environment_configuration();
//    $result = $this->getModulesSize();
//    $modules_size = $this->utility->human_filesize($result[0]['total_size']);
//    $each_module = $result[1];
//    return [
//      '#theme' => 'php_memory_readiness_checker',
//      '#environment_configuration' => $environment_configuration,
//      '#modules_size' => $modules_size,
//      '#each_module' => $each_module,
//    ];
    return [];
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

    $memory_limit = $this->utility->return_bytes($memory_limit);
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
}
