<?php
namespace Drupal\atlas_user\Access;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;

/**
 * Checks access for displaying configuration translation page.
 */
class CustomAccessCheck implements AccessInterface{

  /**
   * A custom access check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account) {
    return ($this->viewAccessCheck($account)) ? AccessResult::allowed() : AccessResult::forbidden();
  }
  
  public function viewAccessCheck($account) {
    $request = \Drupal::request();
    $session = $request->getSession();
    // Set Admin view by default for superadmin and resctricted user.
    if ($session->get('view') == 'learner') {
      $route = \Drupal::routeMatch()->getRouteName();
      if ($route == 'atlas_common.my-team') {
        return false;
      }else {
          return true;
      }
    }else {
      return true;
    }
  }
}

