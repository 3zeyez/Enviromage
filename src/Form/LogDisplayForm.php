<?php

/**
 * @file
 * display logged data into database
 */

namespace Drupal\enviromage\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Renderer;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LogDisplayForm extends FormBase {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * @var \Drupal\enviromage\...
   */
//  protected $GetEnvConf;

  public function __construct(Renderer $renderer) {
    $this->renderer = $renderer;
//    $this->GetEnvConf = $GetEnvConf;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
//      $container->get('enviromage.get_env_conf'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'log_display_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['message'] = [
      '#type' => 'markup',
      '#markup' => '<div id="log-display"></div>',
    ];

    $form['actions']['#type'] = 'actions';

    $form['actions']['run_button'] = [
      '#type' => 'button',
      '#value' => $this->t('Show previous performance checks records'),
      '#ajax' => [
        'callback' => '::logDisplay',
      ],
    ];

    return $form;
  }

  public function logDisplay(array &$form, FormStateInterface $form_state): AjaxResponse{
    $database = \Drupal::database();
    $select_query = $database->select('enviromage_log', 'log');
    $select_query->addField('log', 'id');
    $select_query->addField('log', 'uid');
    $select_query->addField('log', 'avgM');
    $select_query->addField('log', 'timeExec');
    $select_query->addField('log', 'nbIL');
    $select_query->addField('log', 'nbUL');
    $select_query->addField('log', 'nbRL');
    $select_query->addField('log', 'nbIP');
    $select_query->addField('log', 'nbUP');
    $select_query->addField('log', 'nbRP');
    $entries = $select_query->execute()->fetchAll(\PDO::FETCH_ASSOC);

    $markup = [
      '#theme' => 'log_display',
      '#result' => $entries,
    ];
    $response = new AjaxResponse();
    $response->addCommand(
      new HtmlCommand(
        '#log-display',
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
