<?php

declare(strict_types = 1);

namespace Drupal\php_memory_readiness_checker\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Defines a content controller class.
 */
class ContentController extends ControllerBase {

  /**
   * Display the markup.
   *
   * @return array
   *   Return markup array.
   */
  public function content() {
    $markup = "<h2>Hi! I am working on it!</h2><br /></p>";

    $memory_limit = EnvController::get_environment_configurations('memory_limit');

    $fontSize25 = "font-size: 25px;";
    if (PhpMemoryController::check_memory_readiness($memory_limit)) {
      $markup .= "<p style='color: green; $fontSize25'>The memory limit is `$memory_limit`. There is enough memory to update.</p>";
    } else {
      $markup .= "<p style='color: red; $fontSize25'>The memory limit is `$memory_limit`. There is not enough memory to update.</p>";
    }

    return [
        '#type' => 'markup',
        '#markup' => $this->t($markup),
    ];
  }
}