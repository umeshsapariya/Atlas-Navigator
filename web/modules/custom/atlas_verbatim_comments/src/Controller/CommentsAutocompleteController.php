<?php

namespace Drupal\atlas_verbatim_comments\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 */
class CommentsAutocompleteController extends ControllerBase {

  /**
   * Handler for autocomplete request.
   */
  public function handleCommentsAutocomplete(Request $request) {
    // Get Current user UID.
    $current_user = \Drupal::currentUser();
    $uid = $current_user->id();

    $matches = $results = [];

    // Get the typed string from the URL, if it exists.
    if ($input = $request->query->get('q')) {
      $verbatim_questions = getQuestionsList($uid);
      if (!empty($verbatim_questions)) {
        foreach ($verbatim_questions as $question) {
          if (strpos(strtolower($question), strtolower($input)) !== FALSE) {
            $results[] = $question;
          }
        }
      }
      foreach ($results as $question) {
        $matches[] = ['value' => $question, 'label' => $question];
      }
    }
    return new JsonResponse($matches);
  }

}
