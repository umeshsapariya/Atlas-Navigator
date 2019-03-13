<?php

namespace Drupal\atlas_results\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Class DisplayTableController.
 *
 * @package Drupal\mydata\Controller
 */
class CategoryDetails extends ControllerBase {

  /**
   * Display.
   *
   * @return array
   *   Return Table element.
   */
  public function display($category_id = NULL) {
    $connection = Database::getConnection();
    $current_user_id = \Drupal::currentUser()->id();

    $raters_skill_data = get_raters_skill_data($current_user_id);

    $relationship_tid = get_self_relationship_tid();
    $category_details = [];
    $category_wise_ratings = [];
    $total_score = [];
    $others_score = [];
    $self_score = [];
    $category_wise_ratings_others = [];
    if (!empty($raters_skill_data)) {
      foreach ($raters_skill_data as $rater_skill) {
        if ($rater_skill->category_id == $category_id) {
          $category_wise_ratings[$rater_skill->skill_id][$rater_skill->relationship_tid][] = normalised_score_to_5($rater_skill->score, $rater_skill->skill_id);
          if ($rater_skill->relationship_tid != $relationship_tid) {
            $category_wise_ratings_others[$rater_skill->skill_id][$rater_skill->relationship_tid][] = normalised_score_to_5($rater_skill->score, $rater_skill->skill_id);
          }
        }
      }
      if (!empty($category_wise_ratings)) {
        $total_score = get_total_score($category_id, $category_wise_ratings);
        $self_score = get_self_score($category_id, $category_wise_ratings);
      }
      if (!empty($category_wise_ratings_others)) {
        $others_score = get_others_score($category_id, $category_wise_ratings_others);
      }
      // Get skill id for category.
      $skill_id_arr = get_skill_id_by_category($category_id);

      // For each Skill.
      foreach ($skill_id_arr as $skill_id) {
        $key = $skill_id->id;
        // Prepare category details array to show on rigth side of category page.
        $category_details[$key]['other'] = number_format($others_score[$key], 1);
        $category_details[$key]['self'] = number_format($self_score[$key], 1);
        $category_details[$key]['total'] = number_format($total_score[$key], 1);
        // Load skill name from skill id.
        $category_skill_paragraph_ref = Paragraph::load($key);
        $skill_name = $category_skill_paragraph_ref->field_skill->getValue();

        // Load target proficiency of skill.
        $target_proficiency_level = $category_skill_paragraph_ref->field_target_proficiency->getValue();
        $category_details[$key]['skill_name'] = $skill_name[0]['value'];
        $category_details[$key]['target_prof'] = normalised_score_to_5($target_proficiency_level[0]['value'], $key);
        $category_details[$key]['target_prof'] = number_format($category_details[$key]['target_prof'], 1);
        $category_details[$key]['gap'] = $category_details[$key]['total'] - $category_details[$key]['target_prof'];

        if ($category_details[$key]['gap'] >= 0) {
          $category_details[$key]['row_class'] = 'Strengths';
          if ($category_details[$key]['gap'] != 0) {
            $category_details[$key]['sign'] = '+';
          }
        }
        else {
          $category_details[$key]['row_class'] = 'Opportunities';
          $category_details[$key]['sign'] = '';
        }
      }

      // Prepare catagory date array to send category chart js.
      $count = 0;
      foreach ($category_details as $key => $category_detail) {
        $cat_data[$count][] = $category_detail['skill_name'];
        $cat_data[$count][] = floatval($category_detail['other']);
        $cat_data[$count][] = floatval($category_detail['other']);
        $cat_data[$count][] = floatval($category_detail['self']);
        $cat_data[$count][] = floatval($category_detail['self']);
        $cat_data[$count][] = '/category_details/' . $category_id . '/' . $key;
        $count++;
      }
      // Get category name.
      $category_skill_paragraph_ref = Paragraph::load($category_id);
      $category_name = $category_skill_paragraph_ref->field_new_category->getValue();
      $category_name = $category_name[0]['value'];
    }
    $element = [
      '#theme' => 'category_details',
      '#category_details' => $category_details,
      '#category_name' => $category_name,
      '#attached' => [
        'library' => [
          'atlas_results/category_details_chart',

        ],
      ],
    ];
    $element['#attached']['drupalSettings']['cat_data'] = $cat_data;
    return $element;
  }

}
