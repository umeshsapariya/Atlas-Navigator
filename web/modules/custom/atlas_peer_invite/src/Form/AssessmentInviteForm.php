<?php

namespace Drupal\atlas_peer_invite\Form;

use Drupal\user\Entity\User;
use Drupal\node\Entity\Node;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 *
 */
class AssessmentInviteForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'assessment_invite_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // For Load first name and lastname from profile.
    $user_profile = \Drupal::entityTypeManager()
      ->getStorage('profile')
      ->loadByProperties([
      'uid' => \Drupal::currentUser()->id(),
      'type' => 'general_profile',
    ]);
    if ($user_profile) {
      $user_profile = reset($user_profile);
      $first_name = $user_profile->get('field_first_name')->value;
      $last_name = $user_profile->get('field_last_name')->value;
      $name = $first_name . " " . $last_name;
    }
    else {
      $name = "";
    }

    // Your name field.
    $form['user_name'] = [
      '#type' => 'textfield',
      '#title' => t('Your Name'),
      '#value' => $name,
      '#required' => TRUE,
      "#disabled" => TRUE,
    ];

    // Get all active assessment node's title for dropdown field.
    $query = \Drupal::entityQuery('node')
      // Published or not.
      ->condition('status', 1)
      ->condition('type', 'assessment_form');
    $nids = $query->execute();
    $nodes = Node::loadMultiple($nids);
    foreach ($nodes as $node) {
      $Roles[$node->nid->value] = $node->title->value;
    }

    // Select role field.
    $form['select_role'] = [
      '#type' => 'select',
      '#title' => 'Select Role',
      '#options' => $Roles,
    ];

    // Number of raters field.
    $form['number_of_raters'] = [
      '#type' => 'radios',
      '#title' => 'How many people do you want to provide you feedback?',
      '#description' => 'Include your manager, support ..etc',
      '#options' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
      "#required" => TRUE,
      '#ajax' => [
        'callback' => '::number_of_rater_callback',
        'event' => 'change',
        'wrapper' => 'raters-outer-container',
        'progress' => [
          'type' => 'throbber',
          'message' => t(''),
        ],
      ],
    ];

    // Load relationship terms.
    $terms = \Drupal::entityManager()->getStorage('taxonomy_term')->loadTree('raters_relationship');
    foreach ($terms as $term) {
      $relationship[$term->tid] = $term->name;
    }
    // Other value added last.
    $relationship['other'] = 'Other';

    // All raters container field.
    $form['raters'] = [
      '#type' => 'container',
      '#prefix' => '<div id="raters-outer-container">',
      '#suffix' => '</div>',
    ];

    // Load dynamically raters fieldset as number of raters value
    // #Ajax loaded fields.
    $numbers_of_raters = $form_state->getValue('number_of_raters');
    if (!is_null($numbers_of_raters)) {
      for ($i = 0; $i <= $numbers_of_raters; $i++) {
        // Each rater fieldset field.
        $form['raters']['rater-' . $i] = [
          '#type' => 'fieldset',
        ];

        // Rater name field.
        $form['raters']['rater-' . $i]['rater-name-' . $i] = [
          '#type' => 'textfield',
          '#title' => t('Raters name'),
          '#autocomplete_route_name' => 'atlas_peer_invite.autocomplete',
          '#required' => TRUE,
          '#ajax' => [
            'callback' => '::autcomplete_update_email',
            'wrapper' => "rater-email-" . $i,
            'method' => 'replace',
            'progress' => [
              'type' => 'throbber',
              'message' => "Loading...",
            ],
            'event' => 'autocompleteclose',
          ],
        ];
        foreach ($relationship as $key => $value) {
          if ($value == "Self") {
            unset($relationship[$key]);
          }
        }
        // Rater relationship field.
        $form['raters']['rater-' . $i]['relationship-' . $i] = [
          '#type' => 'select',
          '#title' => 'Relationship',
          '#options' => $relationship,
          '#attributes' => [
            'id' => 'relationship-' . $i,
          ],
          '#required' => TRUE,
        ];

        // Rater other relationship field.
        $form['raters']['rater-' . $i]['other-relationship-' . $i] = [
          '#type' => 'textfield',
          '#title' => t("Enter Relationship"),
          '#validated' => TRUE,
          '#states' => [
            'visible' => [
              'select[id="relationship-' . $i . '"]' => ['value' => 'other'],
            ],
          ],
        ];

        // Rater Email ID field.
        $form['raters']['rater-' . $i]['rater-email-' . $i] = [
          '#type' => 'email',
          '#title' => 'Email ID',
          "#prefix" => '<div id="rater-email-' . $i . '">',
          "#suffix" => "</div>",
          '#required' => TRUE,
        ];
      }
    }
    if (!is_null($numbers_of_raters)) {
      // For Submit button.
      $form['raters']['#type'] = 'actions';
      $form['raters']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
        '#prefix' => '<div class ="submit-wrapper">',
        '#suffix' => '</div>',
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $number_of_raters = $values['number_of_raters'];
    for ($i = 0; $i <= $number_of_raters; $i++) {
      for ($j = 0; $j < $i; $j++) {
        if ($values['rater-name-' . $i] == $values['rater-name-' . $j]) {
          $form_state->setErrorByName('rater-name-' . $i, t('Please enter Different Name'));
        }
        if ($values['rater-email-' . $i] == $values['rater-email-' . $j]) {
          $form_state->setErrorByName('rater-email-' . $i, t('Please enter Different Email'));
        }
      }

      if ($values['relationship-' . $i] == "other") {
        if (empty($values['other-relationship-' . $i])) {
          $form_state->setErrorByName('other-relationship-' . $i, t('Please enter relationship Name'));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Email send variables.
    $mailManager = \Drupal::service('plugin.manager.mail');
    $module = "atlas_peer_invite";
    $key = "send_assessment_link";
    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $send = TRUE;

    // Load current user name.
    $account = User::load(\Drupal::currentUser()->id());
    $sender_username = $account->get('name')->value;

    // Fetch form state values.
    $values = $form_state->getValues();
    $role_selected = $values['select_role'];

    // Fetch role name from role id.
    $node = Node::load($role_selected);
    $role_name = $node->title->value;
    $number_of_raters = $values['number_of_raters'];

    // Insert assessment invite query.
    $connection = \Drupal::database();
    $uid = \Drupal::currentUser()->id();

    $invites_same_month = $connection->select('assessment_invite', 'ai')
        ->fields('ai')
        ->where('MONTH(FROM_UNIXTIME(invited_date))= MONTH(CURDATE())')
        ->condition('role_id', $role_selected)
        ->condition('uid', $uid)
        ->execute()->fetchAll();
    if ($invites_same_month) {
      $append = count($invites_same_month);
      $assessment_id = $account->get('name')->value . '-' . $role_name . '-' . date('M-y') . '-' . $append;
    }
    else {
      $assessment_id = $account->get('name')->value . '-' . $role_name . '-' . date('M-y');
    }
    $invite_id = $connection->insert('assessment_invite')->fields(
        [
          'uid' => $uid,
          'role_id' => $role_selected,
          'invited_date' => time(),
          'assessment_id' => $assessment_id,
        ]
      )->execute();

    // If successfully invite inserted in database table.
    if ($invite_id) {
      for ($i = 0; $i <= $number_of_raters; $i++) {
        // Builds array to insert Raters info in table.
        $raters_info[$i]['invite_id'] = $invite_id;
        $raters_name = $values['rater-name-' . $i];
        $raters_email = $values['rater-email-' . $i];

        // Fetch uid from autocomplete value.
        preg_match('#\((.*?)\)#', $raters_name, $match);
        if (isset($match[1]) && is_numeric($match[1])) {
          $raters_info[$i]['raters_uid'] = $match[1];
          $raters_info[$i]['raters_name'] = "";
        }
        else {
          $raters_info[$i]['raters_uid'] = 0;
          $raters_info[$i]['raters_name'] = $raters_name;
        }
        $relationship = $values['relationship-' . $i];
        $new_relationship = $values['other-relationship-' . $i];
        if ($relationship == "other") {
          $raters_info[$i]['relationship_tid'] = 0;
          $raters_info[$i]['new_relationship'] = $new_relationship;
        }
        else {
          $raters_info[$i]['relationship_tid'] = $relationship;
          $raters_info[$i]['new_relationship'] = "";
        }

        $raters_info[$i]['new_relationship'] = $new_relationship;
        $raters_info[$i]['hash'] = hash('md5', $role_selected . '_' . $uid . '_' . time() . '_' . $i);
        $raters_info[$i]['raters_email'] = $raters_email;
      }
      $self_rater['invite_id'] = $invite_id;
      $self_rater['raters_uid'] = $uid;
      $self_rater['raters_name'] = "";
      $self_rater['new_relationship'] = "";
      $self_rater['hash'] = hash('md5', $role_selected . '_' . $uid . '_' . time() . '_' . $invite_id);

      $term_name = 'Self';
      $term = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->loadByProperties(['name' => $term_name]);
      if ($term) {
        $term_obj = reset($term);
        if (isset($term_obj)) {
          if (is_numeric($term_obj->id()) && $term_obj->getVocabularyId() == "raters_relationship") {
            $relationship_tid = $term_obj->id();
          }
        }
      }
      else {
        $relationship_tid = 0;
      }
      $self_rater['relationship_tid'] = $relationship_tid;
      $self_rater['raters_email'] = $account->get('mail')->value;
      array_push($raters_info, $self_rater);
      // Insert query raters information for assessment.
      $query = $connection->insert('assessment_invite_details')->fields(['invite_id', 'raters_uid', 'raters_name', 'relationship_tid', 'new_relationship', 'hash', 'raters_email']);

      foreach ($raters_info as $record) {
        $query->values($record);
      }

      // If successfuly inserted in table.
      if ($query->execute()) {
        $sent = 1;
        foreach ($raters_info as $record) {

          // To email.
          $to = $record['raters_email'];
          if ($record['raters_uid'] == 0) {
            $rater_name = $record['raters_name'];
          }
          else {
            $account = User::load($record['raters_uid']);
            $rater_name = $account->get('name')->value;
          }

          // Link.
          $link = Link::fromTextAndUrl(t('Link'), Url::fromUri('internal:/assessment-form/' . $record['hash'], ['absolute' => TRUE]))->toString()->getGeneratedLink();

          // Body.
          $body = 'Hi ' . $rater_name . ',<br><br>'
            . '<b>' .
            $sender_username . '</b> has requested you to rate them for <b> ' . $role_name . '</b> role at Atlas Navigator. Click here (' . $link . ') to take the assessment. <br><br> Please note that this link will stay active till the time you submit your assessment.<br><br>

Thank you,<br>
' . \Drupal::config('system.site')->get('name') . ' team';
          $params['message'] = $body;
          $params['sender_username'] = $sender_username;

          // Sends Email.
          $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
          if ($result['result'] !== TRUE) {
            $sent = 0;
          }
        }

        // Success message.
        if ($sent) {
          drupal_set_message("Your assessment invitation has been submitted and the link has been sent to the rater's email ID", 'status');
        }
        else {
          drupal_set_message("Your Assessment invitation has been submitted", 'status');
          drupal_set_message("Error while sending mail to rater's Email ID", 'error');
        }
      }
      else {
        drupal_set_message("Something went wrong Assessment invitation could not be applied", 'error');
      }
    }
    $form_state->setRedirect('atlas_peer_invite.begin_assessment_page', ['hash' => $self_rater['hash']]);
  }

  /**
   * Callback of autocomplete field.
   */
  public function autcomplete_update_email(array &$form, FormStateInterface $form_state): array {
    $element = $form_state->getTriggeringElement();
    $name = $element['#name'];
    $name_array = explode("-", $name);
    $key = $name_array[2];

    preg_match('#\((.*?)\)#', $element['#value'], $match);
    if (isset($match[1]) && is_numeric($match[1])) {
      $account = User::load($match[1]);
      $form['raters']['rater-' . $key]['rater-email-' . $key]['#value'] = $account->get('mail')->value;
      $form['raters']['rater-' . $key]['rater-email-' . $key]['#disabled'] = TRUE;
    }
    else {
      $form['raters']['rater-' . $key]['rater-email-' . $key]['#value'] = "";
    }

    return $form['raters']['rater-' . $key]['rater-email-' . $key];
  }

  /**
   * Callback of Number of raters field.
   */
  public function number_of_rater_callback(array $form, FormStateInterface $form_state): array {
    return $form['raters'];
  }

}
