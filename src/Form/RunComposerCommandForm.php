<?php

/**
 * @file
 * runs composer performance check
 */

namespace Drupal\enviromage\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Renderer;
use Drupal\enviromage\RunComposerCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Composer\Semver\Semver;
use Composer\Semver\VersionParser;

class RunComposerCommandForm extends FormBase {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * @var \Drupal\enviromage\Controller\EnviromageController
   */
  protected $RunComposerCommand;

  public function __construct(Renderer $renderer, RunComposerCommand $RunComposerCommand) {
    $this->renderer = $renderer;
    $this->RunComposerCommand = $RunComposerCommand;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('enviromage.run_composer_command'),
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
    $config = $this->config('enviromage.settings');

    $form['message'] = [
      '#type' => 'markup',
      '#markup' => '<p>Run the following command:
                    <code>composer update --dry-run --profile</code>.
                    This command simulate a composer update without applaying it.
                    Also, it profiles memory and time usage.</p>',
    ];

    $form['appearance'] = [
      '#type' => 'details',
      '#title' => $this->t('Appearance'),
      '#open' => TRUE,
    ];

    $form['appearance']['version_constraint'] = [
      '#type' => 'textfield',
      '#title' => t('Choose the version constraint you want to update to'),
      '#description' => t('Enter your text here.'),
    ];

    $moduleDirectories = \Drupal::service('module_handler')->getModuleDirectories();
    $moduleNames = [];
    foreach ($moduleDirectories as $moduleName => $path) {
      unset($path);
      $moduleNames[$moduleName] = $moduleName;
    }

    $form['appearance']['package'] = [
      '#type' => 'select',
      '#title' => t('Choose which package to evaluate its update'),
      '#description' => t('choose just one'),
      '#options' => $moduleNames,
      '#default_value' => $config->get('modules_list'),
    ];

    $form['message2'] = [
      '#type' => 'markup',
      '#markup' => '<div id="result-message-composer"></div>',
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
  public function runComposerCommand(array &$form, FormStateInterface $form_state): AjaxResponse {
    $version_constraint = $form_state->getValue('version_constraint');
    $semver = new Semver($version_constraint);

    $versionParser = new VersionParser();

    try {
      // The parseConstraints() method will throw an exception if the version constraint is invalid.
      $versionParser->parseConstraints($version_constraint);
      // If the version constraint is valid, you can proceed with your code here.
      // For example, you can install the package using Composer or perform other actions.
      $version_constraint = 'The version constraint is valid.';

    } catch (\UnexpectedValueException $e) {
      // Handle the case when the version constraint is invalid.
      // For example, display an error message or log the error.
      // You can also check the exception message for more details on why the constraint is invalid.
      $errorMessage = $e->getMessage();
      $version_constraint = 'The version constraint is not valid.';

    }
    $result = $this->RunComposerCommand->get_update_info_about_enabled_modules();
    $markup = [
      '#theme' => 'composer_command',
      '#result' => $result,
      '#version' => $version_constraint,
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
