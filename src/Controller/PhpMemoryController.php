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
class PhpMemoryController extends ControllerBase {

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
   * @return bool Returns a message indicating the memory readiness:
   *   - TRUE if the memory limit is -1 or the available memory is sufficient.
   *   - FALSE if the available memory is below the threshold.
   */
  static public function check_memory_readiness(string | int $memory_limit): bool {
    if ($memory_limit === -1) {
      return TRUE;
    }

    $memory_limit = UtilsController::return_bytes($memory_limit);
    $memory_usage = memory_get_usage();
    $available_memory = $memory_limit - $memory_usage;

    // The threshold is set to 200MB (1048576 bytes = 1MB)
    if ($available_memory < 1048576 * 200) {
      return FALSE;
    } else {
      return TRUE;
    }
  }
}