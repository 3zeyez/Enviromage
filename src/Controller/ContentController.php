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
    return [
        '#type' => 'markup',
        '#markup' => $this->t("<h2>Hi! I am working on it!</h2>
                              <br /> 
                              <p>I am going to split this controller into multiple controllers.</p>"),
    ];
  }
}