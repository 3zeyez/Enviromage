<?php /** @noinspection PhpUnused */

/**
 * @file
 * display logged data into database
 */

namespace Drupal\enviromage\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class LogDisplayForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'log_display_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
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

    if (count($entries) === 0) {
      $form['message'] = [
        '#type' => '#markup',
        '#markup' => t('<h2>The database table is empty!</h2>
                     <h2> Do some performance checks and go back :)</h2>'),
      ];
    } else {
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
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }
}
