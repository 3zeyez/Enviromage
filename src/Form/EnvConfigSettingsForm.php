<?php

/**
 * @file
 * Contains the settings for administering the environment configuration
 */

namespace Drupal\enviromage\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class EnvConfigSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'env_config_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'enviromage.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $settings = [
      'memory_limit' => 'memory_limit',
      'max_execution_time' => 'max_execution_time',
      'realpath_cache_size' => 'realpath_cache_size',
      'realpath_cache_ttl' => 'realpath_cache_ttl',
      'upload_max_filesize' => 'upload_max_filesize',
      'post_max_size' => 'post_max_size',];

    $config = $this->config('enviromage.settings');

    $form['retrieve_env_config'] = [
      '#type' => 'details',
      '#title' => $this->t('Your environment configuration retrieve approach settings'),
      '#open' => TRUE,
    ];

    $form['retrieve_env_config']['env_conf'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Set the environment configurations to retrieve'),
      '#default_value' => $config->get('settings_list'),
      '#options' => $settings,
      '#description' => $this->t('You could choose which ones could effect
                      your site performance during an update.'),
    ];

    $moduleDirectories = \Drupal::service('module_handler')->getModuleDirectories();
    $moduleNames = [];
    foreach ($moduleDirectories as $moduleName => $path) {
      unset($path);
      $moduleNames[$moduleName] = $moduleName;
    }

    $form['get_modules_size_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Get Modules Memory Size Settings'),
      '#open' => TRUE,
    ];

    $form['get_modules_size_settings']['modules'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Choose which modules to display'),
      '#default_value' => $config->get('modules_list'),
      '#options' => $moduleNames,
      '#description' => $this->t('We could display each module separately.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    $selected_settings = array_filter($form_state->getValue('env_conf'));

    if (count($selected_settings) == 0) {
      \Drupal::messenger()->addWarning(t("Environment Configuration : One item should be selected at least!
      We will not make any changes."));
    }

    if (count($selected_settings) != 0) {
      $this->config('enviromage.settings')
        ->set('settings_list', $selected_settings)
        ->save();
    }

    $selected_modules = array_filter($form_state->getValue('modules'));

    if (count($selected_modules) == 0) {
      \Drupal::messenger()->addWarning(t("Modules Size: One item should be selected at least!
      We will not make any changes."));
    }

    if (count($selected_modules) != 0) {
      $this->config('enviromage.settings')
        ->set('modules_list', $selected_modules)
        ->save();
    }

    parent::submitForm($form, $form_state);
  }

}
