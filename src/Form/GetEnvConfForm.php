<?php /** @noinspection PhpUnused */

/**
 * @file
 * runs composer performance check
 */

namespace Drupal\enviromage\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\enviromage\GetEnvConf;
use Drupal\Core\Render\Renderer;
use Symfony\Component\DependencyInjection\ContainerInterface;

class GetEnvConfForm extends FormBase {

  /**
   * The renderer service.
   *
   * @var object \Drupal\Core\Render\Renderer
   */
  protected object $renderer;

  /**
   * @var object \Drupal\enviromage\GetEnvConf
   */
  protected object $GetEnvConf;

  public function __construct(Renderer $renderer, GetEnvConf $GetEnvConf) {
    $this->renderer = $renderer;
    $this->GetEnvConf = $GetEnvConf;
  }

  /** @noinspection PhpParamsInspection */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('renderer'),
      $container->get('enviromage.get_env_conf'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'get_env_conf';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
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

  /** @noinspection PhpUnused */
  public function getEnvConfig(): AjaxResponse{
    $result = $this->GetEnvConf->get_environment_configuration();
    $markup = [
      '#theme' => 'environment_configuration',
      '#environment_configuration' => $result,
    ];
    $response = new AjaxResponse();

    try {
      $markup = $this->renderer->render($markup);
    } catch (\Exception) {
      $markup = "An error has happened: Try another time!";
    }

    $response->addCommand(
      new HtmlCommand(
        '#result-message-environment',
        $markup,
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
