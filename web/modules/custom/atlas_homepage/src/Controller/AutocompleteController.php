<?php

namespace Drupal\atlas_homepage\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Database\Database;

/**
 * Defines a route controller for entity autocomplete form elements.
 */
class AutocompleteController extends ControllerBase {

  /**
   * Handler for autocomplete request.
   */
  public function handleAutocomplete(Request $request) {
    $input = $request->query->get('q');

    $invite_id_query = db_select('assessment_invite', 'ai')->fields('ai', [
      'invite_id',
      'assessment_id',
    ]);
    $invite_id_query->condition('assessment_id', '%' . db_like($input) . '%', 'LIKE');

    $roles = \Drupal::currentUser()->getRoles();
    if (!in_array('super_admin', $roles) && !in_array('administrator', $roles)) {
      $current_user_id = \Drupal::currentUser()->id();
      $members_uids = get_team_members_uid($current_user_id);
      $invite_id_query->condition('uid', $members_uids, 'IN');
    }
    $invite_id_query->range(0, 10);

    $invite_ids = $invite_id_query->execute()->fetchAll();

    if ($invite_ids) {
      foreach ($invite_ids as $invite_id) {
        $results[] = [
          'value' => $invite_id->assessment_id,
        ];
      }
    }
    else {
      $results[] = [];
    }
    return new JsonResponse($results);
  }

}
