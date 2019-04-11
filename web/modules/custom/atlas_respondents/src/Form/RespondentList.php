<?php

namespace Drupal\atlas_respondents\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Database\Database;

/**
 *
 */
class RespondentList extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'respondents_email_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $options = NULL) {

    // Get Current user UID.
    $current_user = \Drupal::currentUser();
    $uid = $current_user->id();

    $form['#prefix'] = '<div class="ver-comments-wrapper">';
    $form['#suffix'] = '</div>';

    $form['title'] = [
      '#markup' => '<div class="ver-comments-header"><span class="page-title">Respondents</span>',
    ];
    $form['respondent_name'] = [
      '#type' => 'textfield',
      '#autocomplete_route_name' => 'atlas_respondents.respondent_autocomplete',
      '#prefix' => '<span class="search-filter">',
      '#suffix' => '</span></div>',
      '#maxlength' => 512,
      '#ajax' => [
        'callback' => '::getMatchingResondents',
        'wrapper' => 'respondents-content',
        'method' => 'replace',
        'event' => 'autocompleteclose',
        'progress' => [
          'type' => 'throbber',
          'message' => "Loading...",
        ],
      ],
    ];
    $header = [
      'rater_name' => t('Name'),
      'rater_mail' => t('Email'),
      'relationship' => t('Relationship'),
      'status' => t('Status'),
      'select' => t('Select'),
      // 'invite_id' => t('Invite Id'),
      // 'role_id' => t('Role Id'),
      // 'assessment_hash' => t('Hashcode'),.
    ];
    // \Drupal::logger('Search name')->debug('<pre>' . print_r($search_name, TRUE) . '</pre>');
    // Fetch all respondents data.
    $db = \Drupal::database();
    $query = $db->select('assessment_invite', 'ai');
    $query->innerJoin('assessment_invite_details', 'aid', 'aid.invite_id = ai.invite_id');
    $query->fields('aid');
    $query->fields('ai', ['role_id']);
    $query->condition('ai.uid', $uid);

    $respondents = $query->execute()->fetchAll();
    $form['respondents-wrapper'] = [
      '#type' => 'fieldset',
      '#prefix' => '<div class="respondents-content" id="respondents-content"><div class="respondents-wrapper">',
      '#suffix' => '</div></div>',
    ];

    $rows = [];
    foreach ($respondents as $key => $data) {
      $relationship = $raters_name = '';
      // Populate the rows.
      if ($data->raters_uid == 0) {
        $raters_name = $data->raters_name;
        $raters_email = $data->raters_email;
      }
      else {
        $account = User::load($data->raters_uid);
        $raters_name = $account->getUsername();
        $raters_email = $account->get('mail')->value;
      }

      // Relationship name.
      if ($data->relationship_tid == 0) {
        $relationship = $data->new_relationship;
      }
      else {
        $term = Term::load($data->relationship_tid);
        $relationship = $term->getName();
      }
      if (isset($form_state->getValues()['respondent_name'])) {
        $search_name = $form_state->getValues()['respondent_name'];
        if (strpos(strtolower($raters_name), strtolower($search_name)) !== FALSE) {
          // Completion status.
          if ($data->completed == 1) {
            $status = 'Complete';
          }
          else {
            $status = 'Pending';
          }
          $rows[] = [
            'role_id' => $data->role_id,
            'rater_name' => $raters_name,
            'rater_mail' => $raters_email,
            'relationship' => $relationship,
            'assessment_hash' => $data->hash,
            'status' => $status,
          ];
        }
      }
      else {
        // Completion status.
        if ($data->completed == 1) {
          $status = 'Complete';
        }
        else {
          $status = 'Pending';
        }
        $rows[] = [
          // 'invite_id' => $data->invite_id,.
          'role_id' => $data->role_id,
          'rater_name' => $raters_name,
          'rater_mail' => $raters_email,
          'relationship' => $relationship,
          'assessment_hash' => $data->hash,
          'status' => $status,
        ];
      }
    }
    $form['respondents-wrapper']['list'] = [
      '#prefix' => '<div id="respondents-table">',
      '#suffix' => '</div>',
      '#tree' => TRUE,
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => [],
    ];
    for ($i = 0; $i < count($rows); $i++) {
      $fname = [];
      if ($rows[$i]['status'] == 'Pending') {
        $fname = [
          '#id' => 'row-' . $i . '-value',
          '#type' => 'checkbox',
          '#title' => t('chk'),
        ];
      }
      /* $invite_id = [
      '#id' => 'row-' . $i . '-invite_id',
      '#type' => 'hidden',
      '#value' => $rows[$i]['invite_id'],
      ]; */
      $role_id = [
        '#id' => 'row-' . $i . '-role_id',
        '#type' => 'hidden',
        '#value' => $rows[$i]['role_id'],
      ];
      $status = [
        '#id' => 'row-' . $i . '-status',
        '#type' => 'hidden',
        '#value' => $rows[$i]['status'],
      ];
      $rater_name = [
        '#id' => 'row-' . $i . '-rater_name',
        '#type' => 'hidden',
        '#value' => $rows[$i]['rater_name'],
      ];
      $rater_mail = [
        '#id' => 'row-' . $i . '-rater_mail',
        '#type' => 'hidden',
        '#value' => $rows[$i]['rater_mail'],
      ];
      $relationship = [
        '#id' => 'row-' . $i . '-relationship',
        '#type' => 'hidden',
        '#value' => $rows[$i]['relationship'],
      ];
      $assessment_hash = [
        '#id' => 'row-' . $i . '-hash',
        '#type' => 'hidden',
        '#value' => $rows[$i]['assessment_hash'],
      ];
      $form['respondents-wrapper']['list'][] = [
        'rater_name' => &$rater_name,
        'rater_mail' => &$rater_mail,
        'relationship' => &$relationship,
        'status' => &$status,
        'fname' => &$fname,
        // 'invite_id' => &$invite_id,.
        'role_id' => &$role_id,
        'assessment_hash' => &$assessment_hash,
      ];

      $form['respondents-wrapper']['list']['#rows'][] = [
        ['data' => &$rows[$i]['rater_name']],
        ['data' => &$rows[$i]['rater_mail']],
        ['data' => &$rows[$i]['relationship']],
        ['data' => &$rows[$i]['status']],
        ['data' => &$fname],
        // array('data' => &$rows[$i]['invite_id']),
        // array('data' => &$rows[$i]['role_id']),
        // array('data' => &$rows[$i]['assessment_hash']),.
      ];
      unset($rater_mail);
      unset($rater_name);
      unset($relationship);
      unset($status);
      unset($fname);
      // unset($invite_id);
      unset($role_id);
      unset($assessment_hash);
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Send Reminder'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $mailManager = \Drupal::service('plugin.manager.mail');
    $module = "atlas_respondents";
    $module_key = "send_assessment_reminder";
    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $tableValues = $form_state->getValues()['list'];
    foreach ($tableValues as $key => $data) {
      // Send meail to only selected raters.
      if (isset($data['fname']) && $data['fname'] == 1) {
        $user = \Drupal::currentUser();
        $role_details = Node::load($data['role_id']);
        $to = $data['rater_mail'];
        $options = ['absolute' => TRUE];
        $assessment_link = Link::fromTextAndUrl(t('Assessment Link'), Url::fromUri('internal:/assessment-form/' . $data['assessment_hash'], ['absolute' => TRUE]))->toString();
        $params['message'] = t('Hi @rater_name,
        <p>Gentle reminder! @enduser has requested you to rate them for @rolename role at @siteadmin.
        Click here @link to take the assessment.</p>
        <p>Please note that this link will stay active till the time you submit your assessment.</p>
        <p>Thank you,<br>
        @siteadmin team</p>', [
          '@rater_name' => $data['rater_name'],
          '@enduser' => $user->getAccountName(),
          '@rolename' => $role_details->getTitle(),
          '@link' => $assessment_link,
          '@siteadmin' => \Drupal::config('system.site')->get('name'),
        ]);
        $params['sender_username'] = $user->getAccountName();
        $result = $mailManager->mail($module, $module_key, $to, $langcode, $params, NULL, TRUE);
        if ($result['result'] !== TRUE) {
          drupal_set_message(t('There was a problem sending your message and it was not sent.'), 'error');
        }
        else {
          drupal_set_message(t('Raters are notified.'));
        }
      }
    }
  }

  /**
   *
   */
  public static function getMatchingResondents($form, FormStateInterface $form_state) {
    return $form['respondents-wrapper'];
  }

}
