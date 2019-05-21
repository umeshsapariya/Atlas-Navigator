<?php

namespace Drupal\atlas_common\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * ModalForm class.
 */
class SwitchViewForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'switch_view_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $request = \Drupal::request();
    $session = $request->getSession();

    $form['actions']['submit'] = [
      '#type' => 'submit',
    ];
    if ($session->get('view') == 'learner') {
      $form['actions']['submit']['#value'] = t('Switch to Admin View');
    }
    else {
      $path = \Drupal::service('path.current')->getPath();
      $path_alias = \Drupal::service('path.alias_manager')->getAliasByPath($path);
      if($path_alias == "/home") {
        $query_params = \Drupal::request()->query->get('type');
        if (!$query_params) {
          return new RedirectResponse(URL::fromUserInput('/my-team/root')->toString());
          exit();
        }
      }
      $form['actions']['submit']['#value'] = t('Switch to Learner View');
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $request = \Drupal::request();
    $session = $request->getSession();

    if ($session->get('view') == 'learner') {
      $session->set('view', 'admin');
      $form_state->setRedirect('atlas_common.my-team',['userid' => 'root']);
    }
    else {
      $session->set('view', 'learner');
      $url = Url::fromRoute('<front>');
      $form_state->setRedirectUrl($url);
    }
  }

}
