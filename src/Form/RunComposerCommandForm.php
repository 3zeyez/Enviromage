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
use Drupal\Core\Render\Renderer;
use Drupal\enviromage\RunComposerCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Composer\Semver\VersionParser;

class RunComposerCommandForm extends FormBase {

  /**
   * The renderer service.
   *
   * @var object \Drupal\Core\Render\Renderer
   */
  protected object $renderer;

  /**
   * @var object \Drupal\enviromage\RunComposerCommand
   */
  protected object $RunComposerCommand;

  public function __construct(Renderer $renderer, RunComposerCommand $RunComposerCommand) {
    $this->renderer = $renderer;
    $this->RunComposerCommand = $RunComposerCommand;
  }

  /** @noinspection PhpParamsInspection */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('renderer'),
      $container->get('enviromage.run_composer_command'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'run_composer_command';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('enviromage.settings');

    $form['customize_command'] = [
      '#type' => 'details',
      '#title' => $this->t('Customize your command'),
      '#open' => TRUE,
    ];

    $form['customize_command']['version_constraint'] = [
      '#type' => 'textfield',
      '#title' => t('Choose the version constraint you want to update to'),
      '#description' => t('Enter your text here.'),
    ];

    $moduleDirectories = \Drupal::service('module_handler')->getModuleDirectories();
    $moduleNames = [];
    $moduleNames[''] = '-- all Drupal Modules --';
    foreach ($moduleDirectories as $moduleName => $path) {
      unset($path);
      $moduleNames[$moduleName] = $moduleName;
    }

    $form['customize_command']['package'] = [
      '#type' => 'select',
      '#title' => t('Choose which package to evaluate its update'),
      '#description' => t('choose just one'),
      '#options' => $moduleNames,
      '#default_value' => $config->get('modules_list'),
    ];

    $form['customize_command']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Customize Command'),
    ];

    $database = \Drupal::database();
    $select_query = $database->select('enviromage_command', 'c');
    $select_query->addField('c', 'command');
    $entries = $select_query->execute()->fetchAll(\PDO::FETCH_ASSOC);

    if ($entries === []) {
      $command = 'composer update --dry-run --profile';
    } else {
      $command = $entries[count($entries) - 1]['command'];
    }

    $form['run_command'] = [
      '#type' => 'details',
      '#title' => $this->t('Run your command'),
      '#open' => TRUE,
    ];

    $form['run_command']['message1'] = [
      '#type' => 'markup',
      '#markup' => "<p>Run the following command:
                    <strong>`<code>$command</code>`</strong>.</br>
                    <em><u>This command simulate a composer update without applaying it.
                    Also, it profiles <b>memory</b> and <b>time usage</b>.</u></em></p>",
    ];

    $form['run_command']['message2'] = [
      '#type' => 'markup',
      '#markup' => '<div id="result-message-composer"></div>',
    ];

    $form['run_command']['actions']['#type'] = 'actions';

    $form['run_command']['actions']['run_composer'] = [
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
    $database = \Drupal::database();
    $select_query = $database->select('enviromage_command', 'c');
    $select_query->addField('c', 'command');
    $entries = $select_query->execute()->fetchAll(\PDO::FETCH_ASSOC);

    if ($entries === []) {
      $command = 'composer update --dry-run --profile';
    } else {
      $command = $entries[count($entries) - 1]['command'];
    }

    $result = $this->RunComposerCommand->get_update_info_about_enabled_modules($command);

    if ($result === ['Composer command could not run!']) {
      $markup = [
        '#theme' => 'composer_fail',
        '#status_code' => 2
      ];
    } else {
      $markup = [
        '#theme' => 'composer_command',
        '#result' => $result,
      ];
    }

    try {
      // Begin Phase 1: initiate variables to save.

      // Get current user ID.
      $uid = \Drupal::currentUser()->id();

      $current_time = \Drupal::time()->getRequestTime();


      // End Phase 1

      // Begin Phase 2: save the values to the database

      // Start to build a query builder object $query.
      // https://www.drupal.org/docs/8/api/database-api/insert-queries
      $query = \Drupal::database()->insert('enviromage_log');

      // Specify the fields that the query will insert into.
      $query->fields([
        'uid',
        'avgM',
        'timeExec',
        'nbIL',
        'nbIP',
        'nbUL',
        'nbUP',
        'nbRL',
        'nbRP',
        'msgL',
        'msgP',
        'created',
      ]);

      // Set the values of the fields we selected.
      // Note that they must be in the same order as we defined them
      // in the $query->fields([...]) above.
      $query->values([
        $uid,
        $result['memory_avg_usage'],
        (float) $result['time_usage'],
        $result['lock_file_operation']['numberOfInstalls'],
        $result['package_operation']['numberOfInstalls'],
        $result['lock_file_operation']['numberOfUpdates'],
        $result['package_operation']['numberOfUpdates'],
        $result['lock_file_operation']['numberOfRemoves'],
        $result['package_operation']['numberOfRemoves'],
        implode('\n', $result['message']['lock_file_operation']),
        implode('\n', $result['message']['package_operation']),
        $current_time,
      ]);

      // Execute the query!
      // Drupal handle the exact syntax of the query automatically!
      $query->execute();
      // End Phase 2

      // Begin Phase 3: Display a success message

      // Provide the form submitter a nice message.
      \Drupal::messenger()->addMessage(
        t('Your performance check was logged!')
      );
      // End Phase 3
    } catch (\Exception $e) {
      \Drupal::messenger()->addError(
        t($e . 'Unable to log your performance check!')
      );
    }

    try {
      $markup = $this->renderer->render($markup);
    } catch (\Exception) {
      $markup = "An error has happened: Try another time!";
    }

    $response = new AjaxResponse();
    $response->addCommand(
      new HtmlCommand(
        '#result-message-composer',
        $markup,
      )
    );
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
//    // begin version constraint

    $version_constraint = $form_state->getValue('version_constraint');
    $package = $form_state->getValue('package');

    $versionParser = new VersionParser();

    try {
      // The parseConstraints() method will throw an exception if the version constraint is invalid.
      $versionParser->parseConstraints($version_constraint);
      // If the version constraint is valid, you can proceed with your code here.
      // For example, you can install the package using Composer or perform other actions.
      \Drupal::messenger()->addMessage(t('The version constraint is valid.'));
      $command = "composer update drupal/$package:$version_constraint --dry-run --profile";

    } catch (\UnexpectedValueException $e) {
      // Handle the case when the version constraint is invalid.
      // For example, display an error message or log the error.
      // You can also check the exception message for more details on why the constraint is invalid.
      $errorMessage = $e->getMessage();

      if ($package === '' && $version_constraint === ''){
        $command = "composer update --dry-run --profile";
      } else if ($version_constraint === '') {
        \Drupal::messenger()->addMessage(t('No version constraint is specified'));
        $command = "composer update drupal/$package --dry-run --profile";
      } else {
        \Drupal::messenger()->addError(t('The version constraint is not valid.'));
      }

    }

    if (isset($command)) {
      \Drupal::messenger()->addMessage(t("Your customized command is : `$command`"));

      try {
        \Drupal::database()
          ->insert('enviromage_command')
          ->fields(['command'])
          ->values([$command])
          ->execute();
      } catch (\Exception $e) {
        \Drupal::messenger()->addError(
          t($e . 'Unable to log your customized command!')
        );
      }
    }

    // end version constraint
  }
}
