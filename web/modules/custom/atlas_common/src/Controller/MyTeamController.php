<?php

namespace Drupal\atlas_common\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;
use Drupal\Core\Url;

/**
 *
 */
class MyTeamController extends ControllerBase {

  /**
   *
   */
  public function content($userid) {
    if ($userid == "root") {
      $current_user_id = \Drupal::currentUser()->id();
    }
    else {
      $current_user_id = $userid;
    }

    $rows = [];
    $connection = Database::getConnection();
    $query = $connection->select('profile', 'p');
    $query->Join('profile__field_manager', 'pm', 'pm.entity_id = p.profile_id');
    $query->fields('p', ['uid']);
    $query->condition('pm.field_manager_target_id', $current_user_id);
    $child_users = $query->execute()->fetchAll();
    if ($child_users) {
      foreach ($child_users as $user) {
        $account = User::load($user->uid);
        if ($account->hasField('user_picture')) {
          $picture = $account->get('user_picture')->entity;
          if ($picture) {
            $picture = $picture->url();
          }
          else {
            global $base_url;
            $path = drupal_get_path('theme', 'yorkshire');
            $picture = $base_url . '/' . $path . '/images/user_default.jpeg';
          }
        }
        $username = $account->getUsername();
        $user_profile = \Drupal::entityTypeManager()
          ->getStorage('profile')
          ->loadByProperties([
            'uid' => \Drupal::currentUser()->id(),
            'type' => 'general_profile',
          ]);
        if ($user_profile) {
          $user_profile = reset($user_profile);
          $designation_id = $user_profile->get('field_job_title')->target_id;
          if (isset($designation_id)) {
            $designation = Term::load($designation_id);
            $designation_name = $designation->getName();
          }
        }
        $element = [];
        $username = '<a href="/my-team/' . $user->uid . '">' . $username . '</a>';
        $element['#markup'] = '<div class="dashboard-user-info">
          <div class="dashboard-user-image"><img src="' . $picture . '"/></div>
          <div class="dashboard-user-text">
            <div class="dashboard-user-title">' . $username . '</div>
            <div class="dashboard-user-designation">' . $designation_name . '</div>
          </div>
       </div>';
        $user_info = \Drupal::service('renderer')->render($element);
        $element = [];
        $element['#markup'] = '<a class="btn-primary" href="login-as-user">Login as user</a>';
        $login_link = \Drupal::service('renderer')->render($element);
        $rows[] = [
          $user_info,
          $login_link,
        ];
      }
    }
    $header = ['Name', 'Operations'];
    $form['myteam'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => t('No members found'),
      '#cache' => [
        'max-age' => 0,
      ],
    ];
    return $form;
  }

}
