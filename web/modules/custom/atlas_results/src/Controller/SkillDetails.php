<?php

namespace Drupal\atlas_results\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;

/**
 * Class DisplayTableController.
 *
 * @package Drupal\mydata\Controller
 */
class SkillDetails extends ControllerBase {

  /**
   * Display.
   *
   * @return array
   *   Return Table element.
   */
  public function display($assessment_id = NULL, $category_id = NULL, $skill_id = NULL) {
    $connection = Database::getConnection();
    $current_user_id = \Drupal::currentUser()->id();

    $query = $connection->select('assessment_invite', 'ai');
    $query->Join('assessment_invite_details', 'aid', 'aid.invite_id= ai.invite_id');
    $query->Join('assessment_data', 'ad', 'ad.invite_id = aid.id');
    $query->Join('assessment_skill_data', 'asd', 'asd.assessment_id = ad.assessment_id');
    $query->fields('ai');
    $query->fields('aid', ['relationship_tid', 'id']);
    $query->fields('ad', ['assessment_id']);
    $query->fields('asd');
    $query->condition('aid.invite_id', $assessment_id);
    $query->condition('asd.category_id', $category_id);
    $query->condition('asd.skill_id', $skill_id);
    $query->condition('asd.score', 0, '>');
    $query->condition('aid.completed', 1);
    $raters_skill_data = $query->execute()->fetchAll();
    $relationship_tid = get_self_relationship_tid();
    $uid = $raters_skill_data[0]->uid;
    $user = User::load($uid);
    $default_username = '';
    if ($user) {
      $default_username = $user->getUsername();
    }
    if (!empty($raters_skill_data)) {
      foreach ($raters_skill_data as $rater_skill) {
        $category_wise_ratings[$rater_skill->skill_id][$rater_skill->relationship_tid][] = normalised_score_to_5($rater_skill->score, $rater_skill->skill_id);
        if ($rater_skill->relationship_tid != $relationship_tid) {
          $category_wise_ratings_others[$rater_skill->skill_id][$rater_skill->relationship_tid][] = normalised_score_to_5($rater_skill->score, $rater_skill->skill_id);
        }
      }
      $rel_arr = get_relationship_skill_rating($category_wise_ratings[$skill_id]);

      // Get total score.
      $total_score = get_total_score($category_id, $category_wise_ratings);
      $skill_details[$skill_id]['total'] = $total_score[$skill_id];
      $skill_details[$skill_id]['total'] = number_format($skill_details[$skill_id]['total'], 1);

      // Load target proficiency of skill.
      $category_skill_paragraph_ref = Paragraph::load($skill_id);
      $target_proficiency_level = $category_skill_paragraph_ref->field_target_proficiency->getValue();
      // Title for page.
      $skill_paragraph_ref = Paragraph::load($skill_id);
      $skill_name = $skill_paragraph_ref->field_skill->getValue();
      $skill_details[$skill_id]['skill_name'] = $skill_name[0]['value'];
      $skill_details[$skill_id]['target_prof'] = normalised_score_to_5($target_proficiency_level[0]['value'], $skill_id);
      $skill_details[$skill_id]['target_prof'] = number_format($skill_details[$skill_id]['target_prof'], 1);
      $skill_details[$skill_id]['gap'] = $skill_details[$skill_id]['total'] - $skill_details[$skill_id]['target_prof'];
      if ($skill_details[$skill_id]['gap'] >= 0) {
        $skill_details[$skill_id]['row_class'] = 'Strengths';
        if ($skill_details[$skill_id]['gap'] != 0) {
          $skill_details[$skill_id]['sign'] = '+';
        }
      }
      else {
        $skill_details[$skill_id]['row_class'] = 'Opportunities';
        $skill_details[$skill_id]['sign'] = '';
      }

      $count = 0;

      foreach ($rel_arr as $rel => $score) {
        if ($score['score'] < $skill_details[$skill_id]['target_prof']) {
          $skill_color = '#FF2F2F';
        }
        else {
          $skill_color = '#018EC4';
        }
        if ($rel == 0) {
          $rel_name = 'Others';
          $rel_data[$count][] = $rel_name;
          $rel_data[$count][] = floatval(number_format($score['score'], 1));
          $rel_data[$count][] = $skill_color;
          $rel_data[$count][] = floatval(number_format($score['score'], 1));
          $rel_data[$count][] = floatval($skill_details[$skill_id]['target_prof']);
          $count++;
        }
        else {
          $term = Term::load($rel);
          $rel_name = $term->getName();
          $rel_data[$count][] = $rel_name;
          $rel_data[$count][] = floatval(number_format($score['score'], 1));
          $rel_data[$count][] = $skill_color;
          $rel_data[$count][] = floatval(number_format($score['score'], 1));
          $rel_data[$count][] = floatval($skill_details[$skill_id]['target_prof']);
          $count++;
        }
      }
      $count = $count + 1;
      // Calculate peer benchmark.
      $peer_benchmark_data_arr = get_peer_benchmark_skill_data($current_user_id);
      foreach ($peer_benchmark_data_arr as $rater_skill) {
        if ($rater_skill->category_id == $category_id) {
          $peer_benchmark_data[$rater_skill->skill_id][$rater_skill->relationship_tid][] = normalised_score_to_5($rater_skill->score, $rater_skill->skill_id);
        }
      }

      $skill360score_benchmark[$category_id] = get_total_score($category_id, $peer_benchmark_data);

      $rel_count = count($rel_data);
      if (!empty($skill360score_benchmark[$category_id][$skill_id])) {
        $skill_color = '#D5D5D5';
        $rel_name = 'Peer Benchmark';
        $rel_data[$rel_count][] = $rel_name;
        $rel_data[$rel_count][] = floatval(number_format($skill360score_benchmark[$category_id][$skill_id], 1));
        $rel_data[$rel_count][] = $skill_color;
        $rel_data[$rel_count][] = floatval(number_format($skill360score_benchmark[$category_id][$skill_id], 1));
        $rel_data[$rel_count][] = floatval($skill_details[$skill_id]['target_prof']);
      }
      $skill_details = $skill_details[$skill_id];
    }
    // For skill popup.
    $skillblock = \Drupal::service('plugin.manager.block')->createInstance('skill_popup', []);
    if (isset($skillblock) && !empty($skillblock)) {
      $skill_popup = $skillblock->build($skill_id);
    }

    $element = [
      '#theme' => 'skill_details',
      '#skill_details' => $skill_details,
      '#skill_name' => $skill_name[0]['value'],
      '#skill_popup' => $skill_popup,
      '#user_name' => ucfirst($default_username),
      '#attached' => [
        'library' => [
          'atlas_results/skill_details_chart',
        ],
      ],
    ];
    $element['#attached']['drupalSettings']['rel_data'] = $rel_data;
    return $element;
  }

}
