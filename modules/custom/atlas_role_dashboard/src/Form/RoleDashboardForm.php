<?php

namespace Drupal\atlas_role_dashboard\Form;

use Drupal\user\Entity\User;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Database\Database;

/**
 * Returns Role Page.
 */
class RoleDashboardForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'role_dashboard_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $role_id = NULL) {
    $form['#prefix'] = '<div class="role-container">';
    $form['#suffix'] = '</div>';
    $form_state->setFormState([
      'role_id' => $role_id,
    ]);
    $form['lef_section'] = [
      '#type' => 'container',
      '#prefix' => '<div id="left-outer-section"  class="outer-section"><div class="insert_position_label">insert position name</div>',
      '#suffix' => '</div>',
      '#attached' =>
      [
        'library' => ['atlas_role_dashboard/role_page_dashbord'],
      ],
    ];

    $form['lef_section']['filter'] = [
      '#type' => 'container',
      '#prefix' => '<div id="filter-container">',
      '#suffix' => '</div>',
    ];

    $filter_by_role = [
      t('Select Role'),
      t('Primary role only'),
      t('Other roles'),
    ];
    $form['lef_section']['filter']['filter_by_role'] = [
      '#type' => 'select',
      '#options' => $filter_by_role,
      '#ajax' => [
        'callback' => '::get_role_results',
        'event' => 'change',
        'wrapper' => 'output-results',
        'progress' => [
          'type' => 'none',
        ],
      ],
      '#default_value' => 0,
    ];

    $filter_by_relationship['All'] = 'Everyone';
    // Get ralationship filter.
    $relationship_terms = \Drupal::entityManager()->getStorage('taxonomy_term')->loadTree("raters_relationship");
    foreach ($relationship_terms as $relationship_term) {
      $filter_by_relationship[$relationship_term->tid] = $relationship_term->name;
    }

    $filter_by_relationship['All'] = 'Everyone';

    $form['lef_section']['filter']['filter_by_relationship'] = [
      '#type' => 'select',
      '#options' => $filter_by_relationship,
      '#ajax' => [
        'callback' => '::get_role_results',
        'event' => 'change',
        'wrapper' => 'output-results',
        'progress' => [
          'type' => 'none',
        ],
      ],
      '#default_value' => $relationship_term->tid,
    ];

    $filter_by_date = [
      'All' => 'All time',
      '-90 days' => 'Last 90 days',
      '-6 months' => 'Last 6 months',
      '-12 months' => 'Last 12 months',
    ];

    $form['lef_section']['filter']['filter_by_date'] = [
      '#type' => 'select',
      '#options' => $filter_by_date,
      '#ajax' => [
        'callback' => '::get_role_results',
        'event' => 'change',
        'wrapper' => 'output-results',
        'progress' => [
          'type' => 'none',
        ],
      ],
    ];

    $filter_by_status = ['All' => 'All Users', 1 => 'Active User', 0 => 'Inactive User'];

    $form['lef_section']['filter']['filter_by_status'] = [
      '#type' => 'select',
      '#options' => $filter_by_status,
      '#ajax' => [
        'callback' => '::get_role_results',
        'event' => 'change',
        'wrapper' => 'output-results',
        'progress' => [
          'type' => 'none',
        ],
      ],
    ];

    $header_table = ['Name', 'Average skill score'];
    $element['#markup'] = '<div class="dashboard-user-info">
        <div class="dashboard-user-image">
        <img src="../themes/custom/druadmin_lte_theme/images/role_dashboard_user.png"/></div>
        <div class="dashboard-user-text">
          <div class="dashboard-user-title">test user name</div>
          <div class="dashboard-user-designation">test designation</div>
        </div>
     </div>
     ';
    $user_info = \Drupal::service('renderer')->render($element);
    $rows = $this->get_default_role_results($role_id);
    $form['lef_section']['role_results'] = [
      '#type' => 'table',
      '#rows' => $rows,
      '#header' => $header_table,
      '#empty' => t('No Result Found'),
      '#prefix' => '<div class="role-tab-container" id="output-results">',
      "#suffix" => '</div>',
      '#cache' => [
        'max-age' => 0,
      ],
    ];
    /*
     *   Right Section of page
     */
    // outer container.
    $form['right_section'] = [
      '#type' => 'container',
      '#prefix' => '<div id="right-outer-section" class="outer-section">',
      '#suffix' => '</div>',
    ];
    // Getroleassociates
    $connection = Database::getConnection();
    $associates_query = $connection->select('assessment_invite', 'ai');
    $associates_query->Join('assessment_invite_details', 'aid', 'aid.invite_id= ai.invite_id');
    $associates_query->fields('ai', ['uid']);
    $associates_query->distinct();
    $associates_query->condition('aid.completed', 1);
    $associates_query->condition('ai.role_id', $role_id);
    $associates_results = $associates_query->execute()->fetchAll();
    if ($associates_results) {
      $count_associates = count($associates_results);
    }
    else {
      $count_associates = "0";
    }
    $form['right_section']['associates'] = [
      '#markup' => '<div class="outer_Wrap"><div class="associates-people"><div class="total_no">' . $count_associates . '</div><div class="text"> Associates</div></div>',
    ];
    $form['right_section']['skill_analysis'] = [
      '#markup' => '<div class="skill_analysis"><div class="image_area"><img src="../themes/custom/druadmin_lte_theme/images/skill_analysis.png"/></div><div class="text">skill analysis</div></div></div>',
    ];
    $strengths_rows = [];
    $number = 5;
    $top_strenghts = get_top_strengths_opportunities($role_id, "strengths", $number);
    if ($top_strenghts) {
      foreach ($top_strenghts as $strength) {
        $element = [];
        $star_percentage = ($strength['360_score'] * 100) / 5;
        $element['#markup'] = '<div class="ratings"><div class="star-percentage">' . $star_percentage . '</div><div class="empty-stars"></div><div class="full-stars"></div></div>';
        $star_rating = \Drupal::service('renderer')->render($element);
        $strengths_rows[] = [$strength['name'], $star_rating, $strength['360_score']];
      }
    }

    $form['right_section']['top_strenghts'] = [
      '#type' => 'table',
      '#rows' => $strengths_rows,
      '#caption' => t('Top Strengths'),
      '#prefix' => '<div class="strengths-opportunities-wrap"><div class="top-strengths">',
      '#suffix' => '</div>',
      '#empty' => t('No top strengths found'),
      '#cache' => [
        'max-age' => 0,
      ],
    ];
    $opportunities_rows = [];
    $number = 5;
    $top_opportunities = get_top_strengths_opportunities($role_id, "opportunities", $number);
    if ($top_opportunities) {
      foreach ($top_opportunities as $opportunities) {
        $element = [];
        $star_percentage = ($opportunities['360_score'] * 100) / 5;
        $element['#markup'] = '<div class="ratings"><div class="star-percentage">' . $star_percentage . '</div><div class="empty-stars"></div><div class="full-stars"></div></div>';
        $star_rating = \Drupal::service('renderer')->render($element);
        $opportunities_rows[] = [$opportunities['name'], $star_rating, $opportunities['360_score']];
      }
    }
    $form['right_section']['top_opportunities'] = [
      '#type' => 'table',
      '#rows' => $opportunities_rows,
      '#caption' => t('Top Opportunities'),
      '#prefix' => '<div class="top-opportunities">',
      '#suffix' => '</div></div>',
      '#empty' => t('No top opportunities found'),
      '#cache' => [
        'max-age' => 0,
      ],
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

  }

  /**
   * Custom Ajax callback.
   */
  public function get_role_results(array &$form, FormStateInterface $form_state): array {

    $role_id = $form_state->get('role_id');
    $rows = [];
    $user_ids = [];
    $skill_wise_ratings = [];
    $relationship_tid = get_self_relationship_tid();
    $filter_by_role = $form_state->getValue('filter_by_role');
    $status = $form_state->getValue('filter_by_status');
    $relationship = $form_state->getValue('filter_by_relationship');
    $filter_by_date = $form_state->getValue('filter_by_date');
    $required_date = strtotime($filter_by_date);
    // To get associate user to role.
    $query = \Drupal::database()->select('assessment_invite_details', 'aid');
    $query->join('assessment_invite', 'ai', 'ai.invite_id = aid.invite_id');

    $query->fields('ai', ['uid', 'invited_date']);
    $query->fields('aid', ['invite_id']);
    $query->condition('ai.role_id', $role_id);
    if ($filter_by_role == 0) {
      $query->join('profile', 'pr', 'pr.uid = ai.uid');
      $query->join('profile__field_360_role', 'p', 'p.entity_id = pr.profile_id');
    }
    elseif ($filter_by_role == 1) {
      $query->join('profile', 'pr', 'pr.uid = ai.uid');
      $query->join('profile__field_360_role', 'p', 'p.entity_id = pr.profile_id');
      $query->condition('p.field_360_role_target_id', $role_id, '=');
    }
    else {
      $query->join('profile', 'pr', 'pr.uid = ai.uid');
      $query->join('profile__field_360_role', 'p', 'p.entity_id = pr.profile_id');
      $query->condition('p.field_360_role_target_id', $role_id, '!=');
    }
    if ($status != 'All') {
      $query->join('users_field_data', 'u', 'u.uid = ai.uid');
      $query->condition('u.status', (int) $status);
    }
    if ($required_date) {
      $query->condition('ai.invited_date', $required_date, '>=');
    }
    $query->condition('aid.completed', 1);
    $query->condition('aid.relationship_tid', $relationship_tid);
    $query->orderBy('aid.invite_id', 'DESC');
    $query->distinct();
    $user_data = $query->execute()->fetchAll();

    foreach ($user_data as $data) {
      $user_ids[$data->uid][] = $data->invite_id;
    }

    foreach ($user_ids as $uid => $invites) {
      $raters_skill_data = get_raters_skill_data($uid);
      $invite_total = 0;
      $user_info = $this->get_user_info($uid);
      foreach ($invites as $invite) {
        foreach ($raters_skill_data as $rater_skill) {

          if ($rater_skill->invite_id == $invite) {
            if ($relationship == 'All') {
              $category_wise_ratings[$rater_skill->invite_id][$rater_skill->category_id][$rater_skill->skill_id][$rater_skill->relationship_tid][] = normalised_score_to_5($rater_skill->score, $rater_skill->skill_id);
            }
            elseif ($relationship) {
              if ($relationship == $rater_skill->relationship_tid) {
                $category_wise_ratings[$rater_skill->invite_id][$rater_skill->category_id][$rater_skill->skill_id][$rater_skill->relationship_tid][] = normalised_score_to_5($rater_skill->score, $rater_skill->skill_id);
              }
            }
            if (isset($category_wise_ratings[$rater_skill->invite_id][$rater_skill->category_id][$rater_skill->skill_id])) {
              $skill_wise_ratings[$rater_skill->skill_id] = $category_wise_ratings[$rater_skill->invite_id][$rater_skill->category_id][$rater_skill->skill_id];
            }
          }
        }
        // Calculate Ratings.
        $total_rating = 0;
        foreach ($skill_wise_ratings as $skill_key => $skill_wise_rating) {
          $total = 0;
          foreach ($skill_wise_rating as $rel_key => $scores) {
            $total_score = 0;
            foreach ($scores as $score) {
              $total_score = $total_score + $score;
            }

            $avg_score = $total_score / count($scores);
            $avg_score = number_format($avg_score, 1);
            $total = $total + $avg_score;
          }
          $total = $total / count($skill_wise_rating);
          $total_rating = $total_rating + $total;
        }
        $total_rating = $total_rating / count($skill_wise_ratings);
        $total_rating = number_format($total_rating, 1);
        $invite_total = $invite_total + $total_rating;
      }
      $invite_total = $invite_total / count($invites);
      $invite_total = number_format($invite_total, 1);
      $rows[] = [$user_info, $invite_total];
    }

    $form['lef_section']['role_results']['#rows'] = $rows;

    return $form['lef_section']['role_results'];
  }

  /**
   * Custom function to get role results.
   */
  public function get_default_role_results($role_id) {
    $rows = [];
    $user_ids = [];
    $relationship_tid = get_self_relationship_tid();
    $skill_wise_ratings = [];
    // To get associate user to role.
    $query = \Drupal::database()->select('assessment_invite_details', 'aid');
    $query->join('assessment_invite', 'ai', 'ai.invite_id = aid.invite_id');
    $query->join('assessment_invite', 'ai', 'ai.invite_id = aid.invite_id');
    $query->join('profile', 'pr', 'pr.uid = ai.uid');
    $query->fields('ai', ['uid']);
    $query->fields('aid', ['invite_id']);
    $query->condition('ai.role_id', $role_id);
    $query->condition('aid.completed', 1);
    $query->condition('aid.relationship_tid', $relationship_tid);
    $query->orderBy('aid.invite_id', 'DESC');
    $query->distinct();
    $user_data = $query->execute()->fetchAll();

    foreach ($user_data as $data) {
      $user_ids[$data->uid][] = $data->invite_id;
    }

    foreach ($user_ids as $uid => $invites) {
      $raters_skill_data = get_raters_skill_data($uid);
      $invite_total = 0;
      $user_info = $this->get_user_info($uid);
      foreach ($invites as $invite) {
        
        foreach ($raters_skill_data as $rater_skill) {

          if ($rater_skill->invite_id == $invite) {
              if ($relationship_tid == $rater_skill->relationship_tid) {
                $category_wise_ratings[$rater_skill->invite_id][$rater_skill->category_id][$rater_skill->skill_id][$rater_skill->relationship_tid][] = normalised_score_to_5($rater_skill->score, $rater_skill->skill_id);
              }
            if (isset($category_wise_ratings[$rater_skill->invite_id][$rater_skill->category_id][$rater_skill->skill_id])) {
              $skill_wise_ratings[$rater_skill->skill_id] = $category_wise_ratings[$rater_skill->invite_id][$rater_skill->category_id][$rater_skill->skill_id];
            }
          }
        }
        // Calculate Ratings.
        $total_rating = 0;
        if (!empty($skill_wise_ratings)) {
          foreach ($skill_wise_ratings as $skill_key => $skill_wise_rating) {
            $total = 0;
            foreach ($skill_wise_rating as $rel_key => $scores) {
              $total_score = 0;
              foreach ($scores as $score) {
                $total_score = $total_score + $score;
              }

              $avg_score = $total_score / count($scores);
              $avg_score = number_format($avg_score, 1);
              $total = $total + $avg_score;
            }
            $total = $total / count($skill_wise_rating);
            $total_rating = $total_rating + $total;
          }
          $total_rating = $total_rating / count($skill_wise_ratings);
        }
        
        $total_rating = number_format($total_rating, 1);
        $invite_total = $invite_total + $total_rating;
      }
      $invite_total = $invite_total / count($invites);
      $invite_total = number_format($invite_total, 1);
      $rows[] = [$user_info, $invite_total];
    }

    return $rows;
  }

  /**
   * Custom function to get user inforation for role page .
   */
  public function get_user_info($uid) {
    $account = User::load($uid);
    if ($account->hasField('user_picture')) {
      $picture = $account->get('user_picture')->entity;
      if ($picture) {
        $picture = $picture->url();
      }
      else {
        global $base_url;
        $path = drupal_get_path('theme', 'yorkshire');
        $picture = $base_url . '/' . $path . '/images/user_default.jpeg';
      }
    }
    $username = $account->getUsername();
    $user_profile = \Drupal::entityTypeManager()
      ->getStorage('profile')
      ->loadByProperties([
      'uid' => \Drupal::currentUser()->id(),
      'type' => 'general_profile',
    ]);
    if ($user_profile) {
      $user_profile = reset($user_profile);
      $designation_id = $user_profile->get('field_job_title')->target_id;
      if (isset($designation_id)) {
        $designation = Term::load($designation_id);
        $designation_name = $designation->getName();
      }
    }
    $element['#markup'] = '<div class="dashboard-user-info">
          <div class="dashboard-user-image"><img src="' . $picture . '"/></div>
          <div class="dashboard-user-text">
            <div class="dashboard-user-title">' . $username . '</div>
            <div class="dashboard-user-designation">' . $designation_name . '</div>
          </div>
       </div>';
    $user_info = \Drupal::service('renderer')->render($element);
    return $user_info;
  }

}
