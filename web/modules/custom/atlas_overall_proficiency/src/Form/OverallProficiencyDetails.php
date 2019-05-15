<?php
/**
 * @file
 * Contains \Drupal\resume\Form\ResumeForm.
 */
namespace Drupal\atlas_overall_proficiency\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\user\Entity\User;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\SettingsCommand;
use Drupal\Core\Ajax\ReplaceCommand;

class OverallProficiencyDetails extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'resume_form';
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $invite_id=NULL) {
    /*
     *  Left Section of page
     */
    $connection = Database::getConnection();

    $form = [
      "#prefix" => '<div id="invite-id">',
      "#suffix" => "</div>",
    ];

    $current_user_id = \Drupal::currentUser()->id();
    // Default assessment id.
    $query = $connection->select('assessment_invite', 'ai');
    $query->fields('ai', ['assessment_id']);
    $query->condition('ai.invite_id', $invite_id);
    $query->condition('ai.uid', $current_user_id);
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
        'wrapper' => 'invite-id',
        'progress' => [
          'type' => 'throbber',
        ],
        'event' => 'autocompleteclose',
      ],
        
      '#default_value' => $assessment_id,
    ];

    $form['invite_id'] = [
      '#type' => 'textfield',
      '#default_value' => $invite_id,
    ];

    // Outer container.
    $form['lef_sidebar'] = [
      '#type' => 'container',
      '#prefix' => '<div class="overall-proficiency-wrapper"><div id="overall-proficiency-left">',
      '#suffix' => '</div>',
    ];

    /*
     *   Right Section of page
     */
    // outer container.
    $form['right_sidebar'] = [
      '#type' => 'container',
      '#prefix' => '<div id="overall-proficiency-right">',
      '#suffix' => '</div></div>',
    ];
    $connection = Database::getConnection();

    $role_id = $connection->select('assessment_invite', 'ai')
      ->fields('ai', ['role_id'])
      ->condition('ai.invite_id', $invite_id)
      ->execute()
      ->fetchAll();
    if ($role_id) {
      $node = node_load($role_id[0]->role_id);
    }

    // Calculate table data.
    $overall_proficiency_results = GetAssessmentInvitesSkillData($invite_id);
    if ($overall_proficiency_results) {
      // Builds tables formatted data.
      $rows = [];
      $my_strengths = 0;
      $my_opportunities = 0;
      foreach ($overall_proficiency_results as $skill_id => $score360) {
        $element = [];
        $skill_link = [];
        // Load skill name from skill id.
        $category_skill_paragraph_ref = Paragraph::load($skill_id);
        $skill_name = $category_skill_paragraph_ref->field_skill->getValue();
        $category_id = $category_skill_paragraph_ref->parent_id->getValue()[0]['value'];
        // Load target proficiency of skill.
        $target_proficiency_level = $category_skill_paragraph_ref->field_target_proficiency->getValue();
        $normalised_target_proficiency_level = normalised_score_to_5($target_proficiency_level[0]['value'], $skill_id);
        $floor_target_level = floor($normalised_target_proficiency_level * 2) / 2;

        // Build star rating html.
        $star_percentage = ($score360 * 100) / 5;
        $element['#markup'] = '<div class="ratings"><div class="star-percentage">' . $star_percentage . '</div><div class="empty-stars"></div><div class="full-stars"></div></div>';
        $rating_render = \Drupal::service('renderer')->render($element);
        $gap = $score360 - $floor_target_level;
        if ($gap > 0) {
          $gap = '+' . $gap;
          $row_class = "Strengths";
          $my_strengths++;
        }
        elseif ($gap == 0) {
          $row_class = "Strengths";
          $my_strengths++;
        }
        else {
          $row_class = "Opportunities";
          $my_opportunities++;
        }

        // Table row.
        $skill_link['#markup'] = '<a href="/category-details/' . $invite_id . '/' . $category_id . '/' . $skill_id . '">' . $skill_name[0]['value'] . '</a>';
        $skill_link_render = \Drupal::service('renderer')->render($skill_link);
        $url = Url::fromRoute('atlas_results.result_360_skill_relationship', ['category' => 2, 'skill' => 3]);
        $link = Link::fromTextAndUrl('naresh', $url);
        // If you need some attributes.
        $rows[] = [
          'data' => [
            $skill_link_render,
            $rating_render,
            $score360,
            $gap,
          ],
          'class' => [$row_class],
        ];
      }

      $form['right_sidebar']['strength'] = [
        '#type' => 'button',
        '#value' => 'Strengths',
        '#attributes' => ['class' => ['active']],
      ];
      $form['right_sidebar']['opportunities'] = [
        '#type' => 'button',
        '#value' => 'Opportunities',
        '#attributes' => ['class' => ['active']],
      ];

      // Table header.
      $header_table = [
        'Skills' => t('Skills'),
        'Stars' => t('Stars'),
        '360 Score' => t('360 Score'),
        'Gap' => 'Gap',
      ];
      $form['right_sidebar']['table'] = [
        '#type' => 'table',
        '#rows' => $rows,
        '#header' => $header_table,
        '#empty' => t('No Assessment Invitation found'),
        '#cache' => [
          'max-age' => 0,
        ],
      ];
      $total = $my_strengths + $my_opportunities;
      $my_overall_percentage = ($my_strengths * 100) / $total;
      // Donut chart.
      $form['lef_sidebar']['markup'] = [
        '#markup' => '<div class="proficiency-title">Assessment Results - ' . $node->getTitle() . '</div><div class="donutCell"><div id="donut_single"></div><div class="centerLabel">' . round($my_overall_percentage) . '%</div></div><div class="donut_single_labels">
             <div class="proficient_skill">' . $my_strengths . ' Proficient Skills</div>
             <div class="skill_gap">' . $my_opportunities . ' Skill Gaps</div>
         </div>',
        '#attached' =>
        [
          'library' => ['atlas_overall_proficiency/overall_proficiency_innerpage'],
          'drupalSettings' => ['my_strengths' => $my_overall_percentage],
        ],
      ];
    }
    else {
      return [
        '#type' => 'markup',
        '#markup' => '<div class="box_title blue_title">No result found</div>',
      ];
    }

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
   
   }
   
   /**
   * Callback of autocomplete field.
   */
  public function autcomplete_update_invite_id(array &$form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    $assessment_value = $element['#value'];
    $connection = Database::getConnection();
    $invite_id_query = db_select('assessment_invite', 'ai')->fields('ai', [
      'invite_id', 'uid',
    ])
      ->condition('assessment_id', $assessment_value)
      ->execute();
    $values = $invite_id_query->fetchAll();

    $invite_id = $values[0]->invite_id;
    $user_id = $values[0]->uid;

    $query = $connection->select('assessment_invite_details', 'aid');
    $query->Join('assessment_data', 'ad', 'ad.invite_id = aid.id');
    $query->fields('ad', ['assessment_id']);
    $query->condition('aid.invite_id', $invite_id);
    $query->condition('aid.raters_uid', $user_id);
    $assessment_id = $query->execute()->fetchField();
    $form['invite_id']['#value'] = $invite_id;
        $connection = Database::getConnection();

    $role_id = $connection->select('assessment_invite', 'ai')
      ->fields('ai', ['role_id'])
      ->condition('ai.invite_id', $invite_id)
      ->execute()
      ->fetchAll();
    if ($role_id) {
      $node = node_load($role_id[0]->role_id);
    }
    $overall_proficiency_results = GetAssessmentInvitesSkillData($invite_id);
    if ($overall_proficiency_results) {
      // Builds tables formatted data.
      $rows = [];
      $my_strengths = 0;
      $my_opportunities = 0;
      foreach ($overall_proficiency_results as $skill_id => $score360) {
        $element = [];
        $skill_link = [];
        // Load skill name from skill id.
        $category_skill_paragraph_ref = Paragraph::load($skill_id);
        $skill_name = $category_skill_paragraph_ref->field_skill->getValue();
        $category_id = $category_skill_paragraph_ref->parent_id->getValue()[0]['value'];
        // Load target proficiency of skill.
        $target_proficiency_level = $category_skill_paragraph_ref->field_target_proficiency->getValue();
        $normalised_target_proficiency_level = normalised_score_to_5($target_proficiency_level[0]['value'], $skill_id);
        $floor_target_level = floor($normalised_target_proficiency_level * 2) / 2;

        // Build star rating html.
        $star_percentage = ($score360 * 100) / 5;
        $element['#markup'] = '<div class="ratings"><div class="star-percentage">' . $star_percentage . '</div><div class="empty-stars"></div><div class="full-stars"></div></div>';
        $rating_render = \Drupal::service('renderer')->render($element);
        $gap = $score360 - $floor_target_level;
        if ($gap > 0) {
          $gap = '+' . $gap;
          $row_class = "Strengths";
          $my_strengths++;
        }
        elseif ($gap == 0) {
          $row_class = "Strengths";
          $my_strengths++;
        }
        else {
          $row_class = "Opportunities";
          $my_opportunities++;
        }

        // Table row.
        $skill_link['#markup'] = '<a href="/category-details/' . $invite_id . '/' . $category_id . '/' . $skill_id . '">' . $skill_name[0]['value'] . '</a>';
        $skill_link_render = \Drupal::service('renderer')->render($skill_link);
        $url = Url::fromRoute('atlas_results.result_360_skill_relationship', ['category' => 2, 'skill' => 3]);
        $link = Link::fromTextAndUrl('naresh', $url);
        // If you need some attributes.
        $rows[] = [
          'data' => [
            $skill_link_render,
            $rating_render,
            $score360,
            $gap,
          ],
          'class' => [$row_class],
        ];
      }

      $form['right_sidebar']['strength'] = [
        '#type' => 'button',
        '#value' => 'Strengths',
        '#attributes' => ['class' => ['active']],
      ];
      $form['right_sidebar']['opportunities'] = [
        '#type' => 'button',
        '#value' => 'Opportunities',
        '#attributes' => ['class' => ['active']],
      ];

      // Table header.
      $header_table = [
        'Skills' => t('Skills'),
        'Stars' => t('Stars'),
        '360 Score' => t('360 Score'),
        'Gap' => 'Gap',
      ];
      $form['right_sidebar']['table'] = [
        '#type' => 'table',
        '#rows' => $rows,
        '#header' => $header_table,
        '#empty' => t('No Assessment Invitation found'),
        '#cache' => [
          'max-age' => 0,
        ],
      ];
      $total = $my_strengths + $my_opportunities;
      $my_overall_percentage = ($my_strengths * 100) / $total;
      // Donut chart.
      $form['lef_sidebar']['markup'] = [
        '#markup' => '<div class="proficiency-title">Assessment Results - ' . $node->getTitle() . '</div><div class="donutCell"><div id="donut_single"></div><div class="centerLabel">' . round($my_overall_percentage) . '%</div></div><div class="donut_single_labels">
             <div class="proficient_skill">' . $my_strengths . ' Proficient Skills</div>
             <div class="skill_gap">' . $my_opportunities . ' Skill Gaps</div>
         </div>',
        '#attached' =>
        [
          'library' => ['atlas_overall_proficiency/overall_proficiency_innerpage'],
          'drupalSettings' => ['my_strengths' => $my_overall_percentage],
        ],
      ];
    }
    else {
      return [
        '#type' => 'markup',
        '#markup' => '<div class="box_title blue_title">No result found</div>',
      ];
    }
    return $form;
  }
}
