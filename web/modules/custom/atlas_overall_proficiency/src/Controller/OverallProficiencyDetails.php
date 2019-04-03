<?php

namespace Drupal\atlas_overall_proficiency\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Class DisplayTableController.
 *
 * @package Drupal\atlas_overall_proficiency\Controller
 */
class OverallProficiencyDetails extends ControllerBase {

  /**
   * Display.
   *
   * @return array
   *   Return Table element.
   */
  public function Content($invite_id) {
    /*
     *  Left Section of page
     */

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
        $skill_link['#markup'] = '<a href="/category_details/' . $invite_id . '/' . $category_id . '/' . $skill_id . '">' . $skill_name[0]['value'] . '</a>';
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
