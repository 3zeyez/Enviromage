<?php

declare(strict_types = 1);

namespace Drupal\php_memory_readiness_checker\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Define a Variable size checker controller class
*/
class VarController extends ControllerBase {

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
}