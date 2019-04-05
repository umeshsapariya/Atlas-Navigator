<?php

namespace Drupal\atlas_common\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;
use Drupal\Core\Url;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

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

        $account = User::load($user->uid);
        $login_link = $account->toUrl('masquerade')->toString();
        $button[$user->uid] = [
          '#type' => 'operations',
          '#links' => [],
        ];
        $button[$user->uid]['#links']['edit'] = [
          'url' => Url::fromRoute('entity.user.edit_form', ['user' => $user->uid]),
          'title' => t('edit'),
        ];
        $button[$user->uid]['#links']['login-as-user'] = [
          'url' => $account->toUrl('masquerade'),
          'title' => t('login-as-user'),
        ];
        $element['#markup'] = '<a class="btn-primary" href="' . $login_link . '">Login as user</a>';
        $rows[] = [
          $user_info,
          render($button[$user->uid]),
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

  /**
   * Custom access callback for my team page.
   */
  public function access(AccountInterface $account, $userid) {
    $all_members = [];
    $request = \Drupal::request();
    $session = $request->getSession();
    $access = FALSE;
    if ($session->get('view') == 'admin') {
      $all_members = get_all_team_members(\Drupal::currentUser()->id(), $all_members);
      if (!empty($all_members)) {
        if (in_array($userid, $all_members) || $userid == "root") {
          $access = TRUE;
        }
      }
    }
    if ($access) {
      return AccessResult::allowed();
    }
    else {
      return AccessResult::forbidden();
    }
  }

}
