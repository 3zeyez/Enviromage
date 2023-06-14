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
      'env_config.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $types = node_type_get_names();
    $config = $this->config('env_config.settings');
    $form['environment_configuration'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Set the environment configurations to retrieve'),
      '#default_value' => $config->get('settings'),
      '#options' => $types,
      '#description' => $this->t('You could choose which ones could effect
                      your site performance during an update.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $selected_allowed_types = array_filter($form_state->getValue('environment_configuration'));
    sort($selected_allowed_types);

    $this->config('env_config.settings')
      ->set('settings', $selected_allowed_types)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
