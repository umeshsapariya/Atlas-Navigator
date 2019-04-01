<?php

namespace Drupal\atlas_homepage\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Database\Database;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Defines a route controller for entity autocomplete form elements.
 */
class GetChartDataByID extends ControllerBase {

  /**
   * Handler for autocomplete request.
   */
  public function getdata(Request $request) {
    $assessment_id = $request->query->get('invite_id');
    $connection = Database::getConnection();
       // Get Assessment ID
    $query = $connection->select('assessment_invite_details', 'aid');
    $query->Join('assessment_data', 'ad', 'ad.invite_id = aid.id');
    $query->fields('aid', ['invite_id']);
    $query->condition('ad.assessment_id', $assessment_id);
    //$query->condition('aid.raters_uid', $current_user_id);
    $input = $query->execute()->fetchField();
    $input = $assessment_id;
    $connection = Database::getConnection();
    $current_user_id = \Drupal::currentUser()->id();
    $raters_skill_data = get_raters_skill_data($current_user_id);
    $relationship_tid = get_self_relationship_tid();

    foreach ($raters_skill_data as $rater_skill) {
      $category_wise_ratings[$rater_skill->invite_id][$rater_skill->category_id][$rater_skill->skill_id][$rater_skill->relationship_tid][] = normalised_score_to_5($rater_skill->score, $rater_skill->skill_id);
      if ($rater_skill->relationship_tid != $relationship_tid) {
        $category_wise_ratings_others[$rater_skill->invite_id][$rater_skill->category_id][$rater_skill->skill_id][$rater_skill->relationship_tid][] = normalised_score_to_5($rater_skill->score, $rater_skill->skill_id);
      }
    }
    $category_wise_ratings = $category_wise_ratings[$input];
    $category_wise_ratings_others = $category_wise_ratings_others[$input];
    // Find Skill wise rating for each cat,.
    foreach ($category_wise_ratings as $cat => $skill_wise_ratings) {
      $score = 0;
      $count = count($skill_wise_ratings);
      $skill_total = 0;
      foreach ($skill_wise_ratings as $skill => $skill_wise_rating) {
        $self_score[$cat][$skill] = $skill_wise_rating[$relationship_tid][0];
        $skill_total = $skill_total + $skill_wise_rating[$relationship_tid][0];
        $category_360[$cat]['skills'][$skill] = $skill;
      }
      $self_score[$cat]['avg'] = $skill_total / count($skill_wise_ratings);
      $category_360[$cat]['id'] = $cat;
      $category = Paragraph::load($cat);

      $category_name = $category->field_new_category->getValue();
      $category_360[$cat]['category_name'] = $category_name[0]['value'];
      $category_360[$cat]['avg_self'] = number_format($self_score[$cat]['avg'], 1);

    }
    // To calculate others score.
    foreach ($category_wise_ratings_others as $cat => $category_wise_ratings_other) {
      $others_score = get_other_score_category($cat, $category_wise_ratings_other);
      $category_360[$cat]['avg_others'] = number_format($others_score[$cat]['avg'], 1);
    }
    $count = 0;
        
  
    
    foreach ($category_360 as $key => $category) {
      $cat_data[$count][] = $category['category_name'];
      $cat_data[$count][] = floatval($category['avg_others']);
      $cat_data[$count][] = floatval($category['avg_others']);
      $cat_data[$count][] = floatval($category['avg_self']);
      $cat_data[$count][] = floatval($category['avg_self']);
      $cat_data[$count][] = 'category_details/' .$assessment_id.'/'. $key;
      $count++;
    }
    return new JsonResponse($cat_data);
  }

}
