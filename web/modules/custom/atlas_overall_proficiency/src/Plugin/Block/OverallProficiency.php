<?php

namespace Drupal\atlas_overall_proficiency\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Provides a 'Overall Proficiency' Block.
 *
 * @Block(
 *   id = "OverallProficiency",
 *   admin_label = @Translation("Overall Proficiency"),
 *   category = @Translation("Overall Proficiency"),
 * )
 */
class OverallProficiency extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $invite_id = get_latest_invite_id();
    $overall_return['data'] = "not-found";
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
    return [
      '#type' => 'markup',
      '#markup' => '<div class="box_title blue_title" rel="box3">Overall Proficiency</div>
<div class="overall-proficiency-homepage-block box_content box3">
   <div class="donut_single_wrap">
      <div class="donut_home_wrap">
         <div id="donut_home"></div>
         <div class="centerLabel"></div>
      </div>
   </div>
   <div class ="donut_single_labels">
      <div class ="proficient_skill"></div>
      <div class ="skill_gap"></div>
   </div>

</div><div class="overall-noresult">No result found</div>',
      '#attached' => [
        'library' => ['atlas_overall_proficiency/overall_proficiency_home'],
        'drupalSettings' => $overall_return,
      ],
    ];
  }

}
