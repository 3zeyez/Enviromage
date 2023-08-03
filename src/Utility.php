<?php

/**
 * @file This file contains utility functions for my module
 */

declare(strict_types=1);

namespace Drupal\enviromage;

class Utility {
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
  public function return_bytes(string $size): int {
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
  public function getDirectorySize(string $directory): int {
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

  public function sort_array_from_other_array(array &$arr1, array $arr2): void {
    $order = array_flip($arr2);

    // Sort $arr2 using the order in $arr1
    usort($arr1, function($a, $b) use ($order) {
      return $order[$a] - $order[$b];
    });
  }
}
