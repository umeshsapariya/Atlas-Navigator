<?php

namespace Drupal\atlas_developing_plan\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

/**
 *
 */
class ActivityPopupController extends ControllerBase {

  /**
   *
   */
  public function display($activity_id = NULL) {
    // Title for popup.
    $nid = $activity_id;
    $node = Node::load($nid);

    $activity_type_id = $node->get('field_activity_type')->getValue()[0]['target_id'];
    $activity_desc = $node->get('body')->getValue()[0]['value'];
    $activity_url = $node->get('field_activity_url')->getValue()[0]['uri'];

    $activity_type_term = Term::load($activity_type_id);
    $term = Term::load($tid);
    $activity_type_name = $activity_type_term->getName();
    $output = '<div class="activity_type"> Activity Type : ' . $activity_type_name . '</div>
            <div class="activity_description"> Activity Description :' . $activity_desc . '</div>
            <div class="activity_url"> Activity url :' . file_create_url($activity_url) . '</div>';

    $options = [
      'dialogClass' => 'popup-dialog-class rating_desc_popup',
      'width' => '50%',
    ];
    $response = new AjaxResponse();
    $response->addCommand(new OpenModalDialogCommand($node->get('title')->value, $output, $options));

    return $response;
  }

}
