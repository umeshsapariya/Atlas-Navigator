<?php

namespace Drupal\atlas_results\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\paragraphs\Entity\Paragraph;

/**
 *
 */
class SkillPopupController extends ControllerBase {

  /**
   *
   */
  public function display($skill_id = NULL) {
    // Title for popup.
    $skill_paragraph_ref = Paragraph::load($skill_id);
    $skill_name = $skill_paragraph_ref->field_skill->getValue();
    $skill_level_informations = $skill_paragraph_ref->field_skill_level_information->getValue();
    // To get skill level info.
    foreach ($skill_level_informations as $skill_level) {
      $sl = Paragraph::load($skill_level['target_id']);
      $skill_level_id = $skill_level['target_id'];
      $field_level_header = $sl->field_level_header->getValue();
      $field_level_description = $sl->field_level_description->getValue();

      if (!empty($field_level_header[0]['value'])) {
        $skill_data[$skill_level_id]['label'] = $field_level_header[0]['value'];
        $skill_data[$skill_level_id]['description']['value'] = $field_level_description[0]['value'];
        $skill_data[$skill_level_id]['description']['format'] = $field_level_description[0]['format'];
      }
    }
    $markup = '<div class="rating_main_wrapper">
    <div class="content_wrapper">';
    foreach ($skill_data as $level) {
      $markup .= '<div class="rating_outer_box"><div class="rating_label">' . $level['label'] . '</div><div class="rating_desc">' . $level['description']['value'] . '</div></div>';
    }
    $markup .= '</div></div>';
    $options = [
      'dialogClass' => 'popup-dialog-class rating_desc_popup',
      'width' => '50%',
    ];
    $response = new AjaxResponse();
    $response->addCommand(new OpenModalDialogCommand($skill_name[0]['value'], $markup, $options));

    return $response;
  }

}
