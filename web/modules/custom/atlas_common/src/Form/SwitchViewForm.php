<?php

namespace Drupal\atlas_common\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

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
    }
    else {
      $session->set('view', 'learner');
    }
  }

}
