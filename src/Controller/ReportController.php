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
      '#markup' => t('Below is a list of all Event RSVPs including username,
               email address and the name of the event they will be attending.')
    ];

    $headers = [
      t('Package Name'),
      t('Current Version'),
      t('Update To'),
    ];

    // Because load() returns an associative array with each table row
    // as its own array, we can simply define the HTML table rows like this:
    $table_rows = ['Dupal', 'V1', 'V2'];

    // However, as an example, if load() did not return the results in
    // a structure compatible with what we need, we could populate the
    // $table_rows variable like so:
    /*
    $table_rows = [];
    // Load the entries from database.
    $rsvp_entries = $this->>load();

    // Go through each entry and add it to $rows.
    // Ultimately each array will be rendered as a rwo in an HTML table.
    foreach($rsvp_entries as $entry) {
      $table_rows[] = $entry;
    }
    */

    // Create the render array for rendering an HTML table.
    $content['table'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $table_rows,
      '#empty' => t('No entries available.'),
    ];

    // Do not cache this page by setting the max-age to 0.
    $content['#cache']['max-age'] = 0;

    // Return the populated render array.
    return $content;
  }
}
