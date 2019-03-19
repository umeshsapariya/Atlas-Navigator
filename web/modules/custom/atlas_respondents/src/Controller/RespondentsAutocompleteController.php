<?php

namespace Drupal\atlas_respondents\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Database\Database;
use Drupal\user\Entity\User;

/**
 *
 */
class RespondentsAutocompleteController extends ControllerBase {

  /**
   * Handler for autocomplete request.
   */
  public function handleRespondentsAutocomplete(Request $request) {
    // Get Current user UID.
    $current_user = \Drupal::currentUser();
    $uid = $current_user->id();

    $results = [];

    // Get the typed string from the URL, if it exists.
    if ($input = $request->query->get('q')) {
      // Fetch all respondents data.
      $db = \Drupal::database();
      $query = $db->select('assessment_invite', 'ai');
      $query->innerJoin('assessment_invite_details', 'aid', 'aid.invite_id = ai.invite_id');
      $query->fields('aid');
      $query->condition('ai.uid', $uid);

      $respondents = $query->execute()->fetchAll();
      $checkDuplicates = [];
      if (!empty($respondents)) {
        foreach ($respondents as $data) {
          // Populate the rows.
          if ($data->raters_uid == 0) {
            $raters_name = $data->raters_name;
            // $raters_email = $data->raters_email;.
          }
          else {
            $account = User::load($data->raters_uid);
            $raters_name = $account->getUsername();
            // $raters_email = $account->get('mail')->value;;.
          }
          if (!in_array(strtolower($raters_name), $checkDuplicates)) {
            $checkDuplicates[] = strtolower($raters_name);
            if (strpos(strtolower($raters_name), strtolower($input)) !== FALSE) {
              $results[] = [
                'value' => $raters_name/* . " (" . $data->raters_uid . ")"*/,
                'label' => $raters_name, /* . " (" . $data->raters_uid . ")"*/
              ];
            }
          }
        }
      }
      else {
        $results[] = [];
      }
    }
    return new JsonResponse($results);
  }

}
