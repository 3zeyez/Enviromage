<?php

declare(strict_types = 1);

namespace Drupal\php_memory_readiness_checker\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Defines an utils controller class.
 */
class UtilsController extends ControllerBase {

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
  static public function return_bytes(string $size): int
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
}