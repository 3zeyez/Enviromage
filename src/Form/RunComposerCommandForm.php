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
use Drupal\php_memory_readiness_checker\Controller\PhpMemoryController;
use Drupal\Core\Render\Renderer;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RunComposerCommandForm extends FormBase {

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
    return 'run_composer_command';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['message'] = [
      '#type' => 'markup',
      '#markup' => '<p>Run the following command:
                    <code>composer update --dry-run --profile</code>.
                    This command simulate a composer update without applaying it.
                    Also, it profiles memory and time usage.</p>
                    <div id="result-message-composer"></div>',
    ];

    $form['actions']['#type'] = 'actions';

    $form['actions']['run_composer'] = [
      '#type' => 'button',
      '#value' => $this->t('Run Composer Command'),
      '#ajax' => [
        'callback' => '::runComposerCommand',
      ],
    ];

    return $form;
  }

  /**
   * Submit handler for PHP benchmark AJAX.
   */
  public function runComposerCommand(): AjaxResponse {
    $result = $this->PhpMemoryController->get_update_info_about_enabled_modules();
    //    echo "<pre>"; print_r($markup); echo "</pre>";
    $markup = [
      '#theme' => 'composer_command',
      '#result' => $result,
    ];
    $response = new AjaxResponse();
    $response->addCommand(
      new HtmlCommand(
        '#result-message-composer',
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
