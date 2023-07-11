<?php

/**
 * @file
 * Contains buttons to run the function of our module
 */

namespace Drupal\php_memory_readiness_checker\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\php_memory_readiness_checker\Controller\PhpMemoryController;
use Drupal\Core\Render\Renderer;
use Symfony\Component\DependencyInjection\ContainerInterface;

class GetModulesSizeForm extends FormBase {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * @var \Drupal\php_memory_readiness_checker\Controller\PhpMemoryController
   */
  protected $PhpMemoryController;

  public function __construct(Renderer $renderer, PhpMemoryController $PhpMemoryController) {
    $this->renderer = $renderer;
    $this->PhpMemoryController = $PhpMemoryController;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('php_memory_readiness_checker.controller'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'run_functions';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['message'] = [
      '#type' => 'markup',
      '#markup' => '<p>Here you can check the memory size of your enabled
                    modules.</p>
                    <div id="result-message-modules"></div>',
    ];

    $form['actions']['#type'] = 'actions';

    $form['actions']['size_of_modules'] = [
      '#type' => 'button',
      '#value' => $this->t('Get Modules\' Size'),
      '#ajax' => [
        'callback' => '::getModulesSize',
      ],
    ];

    return $form;
  }

  public function getModulesSize(): AjaxResponse{
    $result = $this->PhpMemoryController->getModulesSize();
    $modules_size = $this->PhpMemoryController->human_filesize($result[0]['total_size']);
    $each_module = $result[1];
    $markup = [
      '#theme' => 'modules_size',
      '#modules_size' => $modules_size,
      '#each_module' => $each_module,
    ];
    $response = new AjaxResponse();
    $response->addCommand(
      new HtmlCommand(
        '#result-message-modules',
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
