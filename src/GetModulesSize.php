<?php

/**
 * @file
 */

declare(strict_types=1);

namespace Drupal\php_memory_readiness_checker;

use Symfony\Component\DependencyInjection\ContainerInterface;

class GetModulesSize {

  /**
   * @var \Drupal\php_memory_readiness_checker\Utility
   */
  protected $utility;

  public function __construct(Utility $utility) {
    $this->utility = $utility;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('php_memory_readiness_checker.utility'),
    );
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
      $module_size = $this->utility->getDirectorySize($path);
      $array[1][$module_name] = $this->utility->human_filesize($module_size);
      $array[0]['total_size'] += $module_size;
    }

    return $array;
  }

  private function is_module_has_update(string $module): bool {
    /**
     * @todo try to make this function work properly
     */
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
