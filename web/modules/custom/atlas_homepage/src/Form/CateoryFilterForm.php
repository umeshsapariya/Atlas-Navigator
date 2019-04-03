<?php

namespace Drupal\atlas_homepage\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Database\Database;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\SettingsCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\user\Entity\User;

/**
 *
 */
class CateoryFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'categoryfilter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $connection = Database::getConnection();
    $default_invite_id = get_latest_invite_id();
    if ($default_invite_id) {
      $query = $connection->select('assessment_invite', 'ai');
      $query->fields('ai', ['assessment_id', 'uid']);
      $query->condition('ai.invite_id', $default_invite_id);
      $default_values = $query->execute()->fetchAll();
    }
    $default_username = '';
    $user_id = $default_values[0]->uid;
    $user = User::load($user_id);
    if (!empty($user)) {
      $default_username = $user->getUsername();
    }
    $current_user_id = \Drupal::currentUser()->id();
    // Default assessment id.
    $query = $connection->select('assessment_invite_details', 'aid');
    $query->Join('assessment_data', 'ad', 'ad.invite_id = aid.id');
    $query->fields('ad', ['assessment_id']);
    $query->condition('aid.invite_id', $default_invite_id);
    $query->condition('aid.raters_uid', $current_user_id);
    // $query->condition('aid.raters_uid', $current_user_id);.
    $assessment_id = $query->execute()->fetchField();

    $new_assessment_link = Link::fromTextAndUrl(t('New assessment'), Url::fromRoute('atlas_peer_invite.assessment'))->toString();
    $export_link = Link::fromTextAndUrl(t('Export'), Url::fromRoute('system.admin_config_system'))->toString();
    $filter_link = Link::fromTextAndUrl(t('Filter'), Url::fromRoute('system.admin_config_system'))->toString();
    $links = '';
    $links .= '<div class="btn_wrapper"><div class="new_assessment_link btn">' . $new_assessment_link . '</div></div>';
    $form['assessment_id'] = [
      '#type' => 'textfield',
      '#placeholder' => t('Choose Assessment ID'),
      '#autocomplete_route_name' => 'atlas_homepage_category.autocomplete',
      '#required' => TRUE,
      '#prefix' => '<div class="btn_wrapper_top"><div class="search-filter">',
      '#suffix' => '</div></div>',
      '#ajax' => [
        'callback' => '::autcomplete_update_invite_id',
        'progress' => [
          'type' => 'throbber',
        ],
        'event' => 'autocompleteclose',
      ],
      '#default_value' => $default_values[0]->assessment_id,
    ];
    $form['invite_id'] = [
      '#type' => 'hidden',
      '#default_value' => $default_invite_id,
      "#prefix" => '<div id="invite-id">',
      "#suffix" => "</div>",
    ];
    $form['user_name'] = [
      '#type' => 'markup',
      '#markup' => '<div class="user-name">' . ucfirst($default_username) . '</div>',
      "#prefix" => '<div id="user-name">',
      "#suffix" => "</div>",
    ];
    $completed_invites = get_invite_status($default_invite_id, 1);
    $pending_invites = get_invite_status($default_invite_id, 0);
    $total_invites = $completed_invites + $pending_invites;
    if ($total_invites > 0) {
      $form['invites_status'] = [
        '#type' => 'markup',
        '#markup' => '<div class="invites_status">' . $completed_invites . '/' . $total_invites . ' Completed</div>',
        "#prefix" => '<div id="invite-status">',
        "#suffix" => "</div>",
      ];
    }

    $form['markup'] = [
      '#type' => 'markup',
      '#markup' => '<div class="chart_container scroll_div_content"><div class="chart_wrap"><div id="chart_div"></div><div class="noresult"> No Result Found</div></div></div>' . $links,
      "#prefix" => '<div id="category-chart-id">',
      "#suffix" => "</div>",
    ];

    $form['#attached']['library'] = ['atlas_homepage/atlas_homepage_results_chart'];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * Callback of autocomplete field.
   */
  public function autcomplete_update_invite_id(array &$form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    $assessment_value = $element['#value'];
    $connection = Database::getConnection();
    // $current_user_id = \Drupal::currentUser()->id();
    $invite_id_query = db_select('assessment_invite', 'ai')->fields('ai', [
      'invite_id', 'uid',
    ])
      ->condition('assessment_id', $assessment_value)
      // ->condition('uid', $current_user_id)
      ->execute();
    $values = $invite_id_query->fetchAll();
    // ksm($values);
    $invite_id = $values[0]->invite_id;
    $user_id = $values[0]->uid;

    $query = $connection->select('assessment_invite_details', 'aid');
    $query->Join('assessment_data', 'ad', 'ad.invite_id = aid.id');
    $query->fields('ad', ['assessment_id']);
    $query->condition('aid.invite_id', $invite_id);
    $query->condition('aid.raters_uid', $user_id);
    // $query->condition('aid.raters_uid', $current_user_id);.
    $assessment_id = $query->execute()->fetchField();
    $form['invite_id']['#value'] = $invite_id;
    // Get Username.
    $user = User::load($user_id);
    if (!empty($user)) {
      $default_username = $user->getUsername();
    }
    // Get Invite status.
    $completed_invites = get_invite_status($invite_id, 1);
    $pending_invites = get_invite_status($invite_id, 0);
    $total_invites = $completed_invites + $pending_invites;
    if ($total_invites > 0) {
      $form['invites_status']['#markup'] = '<div class="invites_status">' . $completed_invites . '/' . $total_invites . ' completed</div>';
    }
    else {
      $form['invites_status']['#markup'] = '';
    }
    $form['user_name']['#markup'] = '<div class="user-name">' . ucfirst($default_username) . '</div>';
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand(
      '#invite-id', $form['invite_id'])
    );
    $response->addCommand(new ReplaceCommand(
      '#invite-status', $form['invites_status'])
    );

    $response->addCommand(new ReplaceCommand(
      '#user-name', $form['user_name'])
    );

    $overall_return = ['data' => 'not-found'];
    if (isset($invite_id) && is_numeric($invite_id)) {
      $overall_proficiency_results = GetAssessmentInvitesSkillData($invite_id);
      if ($overall_proficiency_results) {
        $my_strengths = 0;
        $my_opportunities = 0;
        $return = [];
        foreach ($overall_proficiency_results as $skill_id => $score360) {
          $category_skill_paragraph_ref = Paragraph::load($skill_id);
          // Load target proficiency of skill.
          $target_proficiency_level = $category_skill_paragraph_ref->field_target_proficiency->getValue();
          $normalised_target_proficiency_level = normalised_score_to_5($target_proficiency_level[0]['value'], $skill_id);
          $floor_target_level = floor($normalised_target_proficiency_level * 2) / 2;
          $gap = $score360 - $floor_target_level;
          if ($gap >= 0) {
            $my_strengths++;
          }
          else {
            $my_opportunities++;
          }
        }
        $total = $my_strengths + $my_opportunities;
        $my_overall_percentage = ($my_strengths * 100) / $total;
        $overall_return = [
          'my_strengths' => $my_strengths,
          'my_opportunities' => $my_opportunities,
          'data' => "found",
          'invite_id' => $invite_id,
          'my_overall_percentage' => round($my_overall_percentage),
        ];
      }
    }
    $response->addCommand(new SettingsCommand($overall_return, TRUE));
    return $response;
  }

}
