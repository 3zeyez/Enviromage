<?php

/**
 * @file
 */

declare(strict_types=1);

namespace Drupal\enviromage;

use Symfony\Component\DependencyInjection\ContainerInterface;

class RunComposerCommand {

  /**
   * @var object \Drupal\enviromage\Utility
   */
  protected object $utility;

  public function __construct(Utility $utility) {
    $this->utility = $utility;
  }

  /** @noinspection PhpParamsInspection */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('enviromage.utility'),
    );
  }

  public function get_update_info_about_enabled_modules(string $command): array {
    $result = $this->run_composer_command($command);
    if (is_null($result)) {
      return ['Composer command could not run!'];
    } elseif (is_string($result)) {
      if ($result === 'command not working') {
        return ['Composer command could not run!'];
        /**
         * @todo // display proper output of failure, so that it helps the user avoid it.
         */
      }
      $output = $result;
    }
    unset($result);

    $return = [];

    if (isset($output)) {
      $lines = explode(PHP_EOL, $output);
      array_pop($lines);

      $total_memory = 0.0;

      $key = '';
      $lf_label = 'lock_file_operation';
      $p_label = 'package_operation';
      $toBeInstalledLf = [];
      $toBeUpdatedLf = [];
      $toBeRemovedLf = [];
      $toBeInstalledPk = [];
      $toBeUpdatedPk = [];
      $toBeRemovedPk = [];
      foreach ($lines as $line) {
        $closingBracketPos = strpos($line, ']');
        $substring = substr($line, 1, $closingBracketPos - 1);

        $number = (float) $substring;
        $unit = substr($substring, strlen((string) $number), 3);
        $memory_string = $number . $unit;
        $memory = $this->utility->return_bytes($memory_string);
        $total_memory += $memory;

        $restOfLine1 = trim(substr($line, strlen($substring) + 2));

        // Lock file operations:
        $beginOfLine = substr($restOfLine1, 0, strlen('Lock file operations:'));
        if ($beginOfLine === 'Lock file operations:') {
          $this->retrieve_IUR($lf_label, $restOfLine1, $beginOfLine, $return);
          $key = 'Lock file operations';
        }
        unset($beginOfLine); // end of Lock file operations

        // Package operations:s
        $beginOfLine = substr($restOfLine1, 0, strlen('Package operations:'));
        if ($beginOfLine === 'Package operations:') {
          $this->retrieve_IUR($p_label, $restOfLine1, $beginOfLine, $return);
          $key = 'Package operations';
        }
        unset($beginOfLine); // end of Package operations

        // nothing to modify
        $beginOfLine = substr($restOfLine1, 0, strlen('Nothing'));
        if ($beginOfLine === 'Nothing') {
          $return[$lf_label]['numberOfInstalls'] = 0;
          $return[$lf_label]['numberOfUpdates'] = 0;
          $return[$lf_label]['numberOfRemoves'] = 0;
          $return[$p_label]['numberOfInstalls'] = 0;
          $return[$p_label]['numberOfUpdates'] = 0;
          $return[$p_label]['numberOfRemoves'] = 0;
        }
        unset($beginOfLine); // end nothing to modify

        // Files to be installed
        $beginOfLine = substr($restOfLine1, 0, strlen('- Installing'));
        if ($beginOfLine === '- Installing') {
          $this->add_op_to_array(
            $toBeInstalledLf,
            $toBeInstalledPk,
            $key,
            $restOfLine1,
            $beginOfLine
          );
        }
        unset($beginOfLine); // end of Files to be installed

        // Files to be updated
        $beginOfLine = substr($restOfLine1, 0, strlen('- Upgrading'));
        if ($beginOfLine === '- Upgrading') {
          $this->add_op_to_array(
            $toBeUpdatedLf,
            $toBeUpdatedPk,
            $key,
            $restOfLine1,
            $beginOfLine
          );
        }
        unset($beginOfLine); // end of Files to be updated

        // Files to be removed
        $beginOfLine = substr($restOfLine1, 0, strlen('- Removing'));
        if ($beginOfLine === '- Removing') {
          $this->add_op_to_array(
            $toBeRemovedLf,
            $toBeRemovedPk,
            $key,
            $restOfLine1,
            $beginOfLine
          );
        }
        unset($beginOfLine); // end of Files to be removed
      }

      $avg_memory_usage = $total_memory / count($lines);

      $return['memory_avg_usage'] = $this->utility->human_filesize((int) $avg_memory_usage);
      $return['time_usage'] = substr(
        $lines[count($lines) - 1],
        strpos($lines[count($lines) - 1],
          'time: ') + 6
      );
      sort($toBeInstalledLf);
      sort($toBeInstalledPk);
      sort($toBeUpdatedLf);
      sort($toBeUpdatedPk);
      sort($toBeRemovedLf);
      sort($toBeRemovedPk);

      $return['message'][$lf_label] = [];
      $return['message'][$p_label] = [];

      if (empty($toBeInstalledLf)) {
        $return['message'][$lf_label][] = 'There is nothing new to install!';
      }
      else {
        foreach ($toBeInstalledLf as $item) {
          $return['message'][$lf_label][] = "Install - $item";
        }
      }

      if (empty($toBeInstalledPk)) {
        $return['message'][$p_label][] = 'There is nothing new to install!';
      }
      else {
        foreach ($toBeInstalledPk as $item) {
          $return['message'][$p_label][] = "Install - $item";
        }
      }

      if (empty($toBeUpdatedLf)) {
        $return['message'][$lf_label][] = 'There is no update!';
      }
      else {
        foreach ($toBeUpdatedLf as $item) {
          $return['message'][$lf_label][] = "Update - $item";
        }
      }

      if (empty($toBeUpdatedPk)) {
        $return['message'][$p_label][] = 'There is no update!';
      }
      else {
        foreach ($toBeUpdatedPk as $item) {
          $return['message'][$p_label][] = "Update - $item";
        }
      }

      if (empty($toBeRemovedLf)) {
        $return['message'][$lf_label][] = 'There is nothing to remove!';
      }
      else {
        foreach ($toBeRemovedLf as $item) {
          $return['message'][$lf_label][] = "Remove - $item";
        }
      }

      if (empty($toBeRemovedPk)) {
        $return['message'][$p_label][] = 'There is nothing to remove!';
      }
      else {
        foreach ($toBeRemovedPk as $item) {
          $return['message'][$p_label][] = "Remove - $item";
        }
      }
    }

    return $return;
  }

  private function retrieve_IUR(
    string $label,
    string $restOfLine1,
    string $beginOfLine1,
    array &$return): void {

    $return[$label] = [];

    // Installs
    $restOfLine2 = trim(substr($restOfLine1, strlen($beginOfLine1)));
    $numberOfInstalls = (int) $restOfLine2;
    $return[$label]['numberOfInstalls'] = $numberOfInstalls;

    // Updates
    $restOfLine2 = substr($restOfLine2, strlen("$numberOfInstalls installs,"));
    $numberOfUpdates = (int) $restOfLine2;
    $return[$label]['numberOfUpdates'] = $numberOfUpdates;

    // Removes
    $restOfLine2 = substr($restOfLine2, strlen("$numberOfUpdates updates,"));
    $return[$label]['numberOfRemoves'] = ((int) $restOfLine2);
  }

  public function run_composer_command(string $command): string | NULL {
    // Run the shell command within the DDEV environment.
    $descriptors = [
      1 => ['pipe', 'w'], // Capture standard output
      2 => ['pipe', 'w'], // Capture standard error output
    ];
    $process = proc_open($command,
      $descriptors, $pipes, '/var/www/html');

    if (is_resource($process)) {
      // Get the standard error output
      // It is composer's developer choice
      $errorOutput = stream_get_contents($pipes[2]);

      // Close the pipes
      fclose($pipes[1]);
      fclose($pipes[2]);

      // Close the process and get status code of command running
      $status_code = proc_close($process);

      // Try Catch Composer Command fail @TO-DO
      if ($status_code !== 0) {
        return "command not working";
      }

      return $errorOutput;
    }
    return NULL;
  }

  /**
   * This function chooses if the operation (install, update or remove)
   * belongs to lock file operations or to package operations.
   * @param array $toBeOpLf  array of lock file operations
   * @param array $toBeOpPk  array of package operations
   * @param string $key
   *   key says if the operation is a lock file or a package operation
   * @param string $restOfLine1  the line output by composer
   * @param string $beginOfLine  string to escape from the beginning
   *
   * @return void
   */
  private function add_op_to_array(
    array &$toBeOpLf,
    array &$toBeOpPk,
    string $key,
    string $restOfLine1,
    string $beginOfLine
  ): void {
    $restOfLine2 = trim(substr($restOfLine1, strlen($beginOfLine)));
    if ($key === 'Lock file operations') {
      $toBeOpLf[] = $restOfLine2;
    } else {
      $toBeOpPk[] = $restOfLine2;
    }
  }
}
