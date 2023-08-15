<?php

/**
 * @file
 * Contains buttons to run the function of our module
 */

namespace Drupal\enviromage\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\enviromage\GetModulesSize;
use Drupal\enviromage\Utility;
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
   * @var \Drupal\enviromage\GetModulesSize
   */
  protected $GetModulesSize;

  /**
   * @var \Drupal\enviromage\Utility
   */
  protected $utility;

  public function __construct(
    Renderer $renderer,
    GetModulesSize $GetModulesSize,
    Utility $utility
  ) {
    $this->renderer = $renderer;
    $this->GetModulesSize = $GetModulesSize;
    $this->utility= $utility;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('enviromage.get_modules_size'),
      $container->get('enviromage.utility'),
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
    $result = $this->GetModulesSize->getModulesSize();
    $modules_size = $this->utility->human_filesize($result[0]['total_size']);
    $each_module = $result[1];

    try {
      foreach ($each_module as $module_name => $memory_size) {
        $query = \Drupal::database()->insert('enviromage_msize');
        $query->fields([
          'id',
          'module_name',
          'memory_size',
        ]);
        $query->values([
          $this->utility->return_bytes($modules_size),
          $module_name,
          $this->utility->return_bytes($memory_size),
        ]);
        $query->execute();
      }

      \Drupal::messenger()->addMessage(t('Your Modules Size retrieval was logged!'));
    } catch (\Exception $e) {
      \Drupal::messenger()->addError(t($e . 'Sorry! We could not store this into Database!'));
    }


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
