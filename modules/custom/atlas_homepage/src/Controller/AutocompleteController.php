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
    $connection = Database::getConnection();
    $current_user_id = \Drupal::currentUser()->id();
    $invite_id_query = db_select('assessment_invite', 'ai')->fields('ai', [
      'invite_id',
      'assessment_id',
    ])
      ->condition('assessment_id', '%' . db_like($input) . '%', 'LIKE')
      ->condition('uid', $current_user_id)
      ->range(0, 10)
      ->execute();
    $invite_ids = $invite_id_query->fetchAll();
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
