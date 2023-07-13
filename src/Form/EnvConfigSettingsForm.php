<?php

/**
 * @file
 * Contains the settings for administering the environment configuration
 */

namespace Drupal\php_memory_readiness_checker\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class EnvConfigSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'env_config_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'php_memory_readiness_checker.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings = [
      'memory_limit' => 'memory_limit',
      'max_execution_time' => 'max_execution_time',
      'realpath_cache_size' => 'realpath_cache_size',
      'realpath_cache_ttl' => 'realpath_cache_ttl',
      'upload_max_filesize' => 'upload_max_filesize',
      'post_max_size' => 'post_max_size',];

    $config = $this->config('php_memory_readiness_checker.settings');
    $form['env_conf'] = [
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

    $form['modules'] = [
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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $selected_settings = array_filter($form_state->getValue('env_conf'));

    if (count($selected_settings) == 0) {
      \Drupal::messenger()->addWarning(t("Environment Configuration : One item should be selected at least!
      We will not make any changes."));
    }

    if (count($selected_settings) != 0) {
      $this->config('php_memory_readiness_checker.settings')
        ->set('settings_list', $selected_settings)
        ->save();
    }

    $selected_modules = array_filter($form_state->getValue('modules'));

    if (count($selected_modules) == 0) {
      \Drupal::messenger()->addWarning(t("Modules Size: One item should be selected at least!
      We will not make any changes."));
    }

    if (count($selected_modules) != 0) {
      $this->config('php_memory_readiness_checker.settings')
        ->set('modules_list', $selected_modules)
        ->save();
    }

    parent::submitForm($form, $form_state);
  }

}
