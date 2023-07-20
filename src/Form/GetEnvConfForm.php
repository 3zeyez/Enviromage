<?php

/**
 * @file
 * runs composer performance check
 */

namespace Drupal\php_memory_readiness_checker\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\php_memory_readiness_checker\GetEnvConf;
use Drupal\Core\Render\Renderer;
use Symfony\Component\DependencyInjection\ContainerInterface;

class GetEnvConfForm extends FormBase {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * @var \Drupal\php_memory_readiness_checker\Controller\PhpMemoryController
   */
  protected $GetEnvConf;

  public function __construct(Renderer $renderer, GetEnvConf $GetEnvConf) {
    $this->renderer = $renderer;
    $this->GetEnvConf = $GetEnvConf;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('php_memory_readiness_checker.get_env_conf'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'run_composer_command';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['message'] = [
      '#type' => 'markup',
      '#markup' => '<div id="result-message-environment"></div>',
    ];

    $form['actions']['#type'] = 'actions';

    $form['actions']['env_configuration'] = [
      '#type' => 'button',
      '#value' => $this->t('Get Environment Configuration'),
      '#ajax' => [
        'callback' => '::getEnvConfig',
      ],
    ];

    return $form;
  }

  public function getEnvConfig(): AjaxResponse{
    $result = $this->GetEnvConf->get_environment_configuration();
    $markup = [
      '#theme' => 'environment_configuration',
      '#environment_configuration' => $result,
    ];
    $response = new AjaxResponse();
    $response->addCommand(
      new HtmlCommand(
        '#result-message-environment',
        $this->renderer->render($markup)
      )
    );
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }
}
