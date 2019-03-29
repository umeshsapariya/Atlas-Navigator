<?php

namespace Drupal\atlas_homepage\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\node\Entity\Node;

/**
 * Defines a route controller for Developing plan update.
 */
class DevelopingPlan extends ControllerBase {

  /**
   * Handler for autocomplete request.
   */
  public function update(Request $request) {
    $input = $request->query->get('development_id');
    $updated = "no";
    if ($input) {
      $node = Node::load($input);
      $node->set("field_completed", "1");
      $node->set("field_completed_date", date('Y-m-d'));
      $node->save();
      $updated = date('M d');
      drupal_set_message("Development completed successfully", 'status');
    }

    return new JsonResponse($updated);
  }

}
