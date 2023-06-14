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
      'memory_limit',
      'max_execution_time',
      'realpath_cache_size',
      'realpath_cache_ttl',
      'upload_max_filesize',
      'post_max_size',];

    $config = $this->config('php_memory_readiness_checker.settings');
    $form['environment_configuration'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Set the environment configurations to retrieve'),
      '#default_value' => $config->get('settings'),
      '#options' => $settings,
      '#description' => $this->t('You could choose which ones could effect
                      your site performance during an update.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $selected_settings = array_filter($form_state->getValue('environment_configuration'));
    sort($selected_settings);

    $this->config('php_memory_readiness_checker.settings')
      ->set('settings', $selected_settings)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
