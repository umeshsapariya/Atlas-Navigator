<?php

namespace Drupal\atlas_developing_plan\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;
use Drupal\Core\Ajax\RedirectCommand;

/**
 * Class SkillDevelopingPlan for Learning managment plan.
 */
class ChangeActivityDueDate extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'change_activity_due_date_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
    $nodes = Node::load($id);

    $development_plan_date = $nodes->get('field_due_date')->getValue()[0]['value'];

    $form_state->setFormState([
      'nodes' => $nodes,
    ]);
    $form['#prefix'] = '<div id="change_activity_due_date_form">';
    $form['#suffix'] = '</div>';
    // The status messages that will contain any form errors.
    $form['status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -10,
    ];
    $form['date'] = [
      '#title' => $this->t('Date'),
      '#type' => 'datetime',
      '#required' => TRUE,
      '#default_value' => DrupalDateTime::createFromTimestamp(strtotime($development_plan_date)),
    ];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['send'] = [
      '#type' => 'submit',
      '#value' => $this->t('Change Due Date'),
      '#attributes' => [
        'class' => [
          'use-ajax',
        ],
      ],
      '#ajax' => [
        'callback' => [$this, 'submitDueDateFormAjax'],
        'event' => 'click',
        'progress' => [
          'type' => 'none',
        ],
      ],
    ];

    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Nothing.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * AJAX callback handler that displays any errors or a success message.
   */
  public function submitDueDateFormAjax(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    // If there are any form errors, re-display the form.
    if ($form_state->hasAnyErrors()) {
      $response->addCommand(new ReplaceCommand('#change_activity_due_date_form', $form));
    }
    else {
      $node = $form_state->get("nodes");
      // Clear the form errors.
      $form_state->clearErrors();

      $date = $form_state->getValue('date')->format('Y-m-d', drupal_get_user_timezone());
      // This is a Field added in to the content type.
      $node->set('field_due_date', $date);
      $node->save();
      $currentURL = Url::fromRoute('development-plan-details-form');
      $response->addCommand(new RedirectCommand($currentURL->toString()));
      drupal_set_message(t('Activity Due date changed successfully.'));
      $response->addCommand(new CloseModalDialogCommand());
    }
    return $response;
  }

}
