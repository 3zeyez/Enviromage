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
    $select_query->addField('log', 'msgL');
    $select_query->addField('log', 'msgP');
    $select_query->addField('log', 'created');
    $select_query->orderBy('id', 'DESC');
    $entries = $select_query->execute()->fetchAll(\PDO::FETCH_ASSOC);

    $form['actions']['#type'] = 'actions';

    foreach ($entries as $entry) {
      $record_id = $entry['id'];

      $date = date('Y-m-d H:i:s', $entry['created']);

      $form[$record_id] = [
        '#type' => 'details',
        '#title' => t("Record $record_id - $date"),
        '#open' => TRUE,
      ];

      $form[$record_id]['row1'] = [
        '#type' => 'table',
        '#header' => [
          'User Name',
          'Average Memory Usage',
          'Command Time Execution',
          'Lock File Operation',
          'Package Operation',
        ],

        '#empty' => 'No data available now',
      ];

      $form['actions']['run_button'] = [
        '#type' => 'button',
        '#value' => $this->t('Extend'),
        '#ajax' => [
          'callback' => '::extendDisplay',
        ],
      ];

      $nbIL = $entry['nbIL'];
      $nbUL = $entry['nbUL'];
      $nbRL = $entry['nbRL'];
      $lockFileMsg =
        "<ul>
          <li>$nbIL Installs</li>
          <li>$nbUL Updates</li>
          <li>$nbRL Removes</li>
        </ul>";

      $nbIP = $entry['nbIP'];
      $nbUP = $entry['nbUP'];
      $nbRP = $entry['nbRP'];
      $packageMsg =
        "<ul>
          <li>$nbIP Installs</li>
          <li>$nbUP Updates</li>
          <li>$nbRP Removes</li>
        </ul>";

      $user = \Drupal\user\Entity\User::load($entry['uid']);
      if ($user) {
        $user_name = $user->getDisplayName();
      } else {
        $user_name = 'Unknown user';
      }

      $form[$record_id]['row1'][] = [
        'User Name' => ['#markup' => $user_name],
        'Average Memory Usage' => ['#markup' => $entry['avgM']],
        'Command Time Execution' => ['#markup' => $entry['timeExec'] . 's'],
        'Lock File Operation' => ['#markup' => $lockFileMsg],
        'Package Operation' => ['#markup' => $packageMsg],
      ];

      $form[$record_id]['extend'] = [
        '#type' => 'details',
        '#title' => t("See More Details!"),
        '#open' => FALSE,
      ];

      $form[$record_id]['extend']['row2'] = [
        '#type' => 'table',
        '#header' => [
          'Lock File Operation',
          'Package Operation',
        ],

        '#empty' => 'No data available now',
      ];

      $msgL_lines = explode('\n', $entry['msgL']);
      $msgP_lines = explode('\n', $entry['msgP']);

      $msgL = "<p>" . implode("</p></p>", $msgL_lines) . "</p>";
      $msgP = "<p>" . implode("</p></p>", $msgP_lines) . "</p>";

      $form[$record_id]['extend']['row2'][] = [
        'Lock File Operation' => ['#markup' => $msgL],
        'Package Operation' => ['#markup' => $msgP],
      ];

      $form['record_id'] = $record_id;
    }

    unset($form['actions']['run_button']);

    return $form;
  }

  public function logDisplay(array &$form, FormStateInterface $form_state): AjaxResponse{
    $record_id = $form['record_id'];

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
