<?php

/**
 * @file
 * Contains \Drupal\php_memory_readiness_checker\Controller\ReportController.
 */

declare(strict_types = 1);

namespace Drupal\php_memory_readiness_checker\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\php_memory_readiness_checker\Controller\PhpMemoryController;

class ReportController extends ControllerBase {
  /**
   * Creates the composer command performance report page.
   *
   * @return array
   *  Render array for the report output.
   */
  public function report() {
    $content = [];

    $content['message'] = [
      '#markup' => t('<h1>Packages To Be Updated:</h1>')
    ];

    $headers = [
      t('Package Name'),
      t('Current Version'),
      t('Update To'),
    ];

    $table_rows = [
      ['Dupal', 'V1', 'V2'],
      ['Automatic_updates', '10.1.0', '10.1.1'],
    ];

    // Create the render array for rendering an HTML table.
    $content['table'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $table_rows,
      '#empty' => t('No Updates!'),
    ];

    // Do not cache this page by setting the max-age to 0.
    $content['#cache']['max-age'] = 0;

    // Return the populated render array.
    return $content;
  }
}
