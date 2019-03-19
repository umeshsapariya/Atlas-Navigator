<?php

namespace Drupal\atlas_peer_invite\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a route controller for entity autocomplete form elements.
 */
class AutocompleteController extends ControllerBase {

  /**
   * Handler for autocomplete request.
   */
  public function handleAutocomplete(Request $request) {
    $input = $request->query->get('q');

    $user_query = db_select('users_field_data', 'f')->fields('f', [
      'uid',
      'name',
    ])
      ->condition('name', '%' . db_like($input) . '%', 'LIKE')
      ->range(0, 10)
      ->execute();
    $users = $user_query->fetchAll();
    if ($users) {
      foreach ($users as $user) {
        $results[] = [
          'value' => $user->name . " (" . $user->uid . ")",
          'label' => $user->name . " (" . $user->uid . ")",
        ];
      }
    }
    else {
      $results[] = [];
    }
    return new JsonResponse($results);
  }

}
