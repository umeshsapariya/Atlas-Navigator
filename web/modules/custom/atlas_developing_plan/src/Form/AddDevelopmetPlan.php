<?php

namespace Drupal\atlas_developing_plan\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;
use Drupal\Core\Ajax\RedirectCommand;

/**
 * Class SkillDevelopingPlan for Learning managment plan.
 */
class AddDevelopmetPlan extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_development_plan_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#prefix'] = '<div id="add_development_plan_form">';
    $form['#suffix'] = '</div>';
    //$form['#validate'][] = 'custom_add_plan_url_validate';
    $form['status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -10,
    ];
    $form['activity_name'] = [
      '#type' => 'textfield',
      '#title' => t('Activity Name:'),
      '#required' => TRUE,
    ];
    $form['body'] = [
      '#type' => 'text_format',
      '#title' => t('Description:'),
      // '#format' => 'filter_html',.
    ];
    $activity_type_terms = \Drupal::entityManager()->getStorage('taxonomy_term')->loadTree("learning_activity_type");

    $activity_type['_none'] = 'Select';
    foreach ($activity_type_terms as $activity_type_term) {
      $activity_type[$activity_type_term->tid] = $activity_type_term->name;
    }
    $form['activity_type'] = [
      '#type' => 'select',
      '#title' => t('Activity Type:'),
      '#options' => $activity_type,
    ];
    $form['url'] = [
      //'#type' => 'url',
      '#type' => 'textfield',
      '#title' => t('URL:'),
    ];
    $form['date'] = [
      '#title' => $this->t('Due Date'),
      '#type' => 'date',
      '#required' => TRUE,
      // '#default_value' => DrupalDateTime::createFromTimestamp(strtotime($development_plan_date)),
    ];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['send'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Activity'),
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
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * AJAX callback handler that displays any errors or a success message.
   */
  public function submitDueDateFormAjax(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    // If there are any form errors, re-display the form.
    if ($form_state->hasAnyErrors()) {
      $response->addCommand(new ReplaceCommand('#add_development_plan_form', $form));
    }
    else {
      $title = $form_state->getValue('activity_name');
      $date = $form_state->getValue('date');
      $activity_type = $form_state->getValue('activity_type');
      $url = $form_state->getValue('url');
      $current_user_id = \Drupal::currentUser()->id();

      $body = [
        'value' => $form_state->getValue('body')['value'],
        'format' => $form_state->getValue('body')['format'],
      ];

      // Save activity.
      $node_activity = Node::create([
        'type'        => 'learning_activity',
        'title'       => $title,
        'field_activity_url' => $url,
        'body' => $body,
        'uid' => $current_user_id,
        'status' => 1,
      ]);
      if ($activity_type != '_none') {
        $node_activity->set('field_activity_type', ['target_id' => $activity_type]);
      }
      $node_activity->save();

      if (!empty($node_activity->id())) {
        $node_plan = Node::create([
          'type'                     => 'developing_plan',
          'title'                    => $title,
          'field_learning_activity'  => [$node_activity->id()],
          'field_assigned_user'      => $current_user_id,
          'field_due_date'           => $date,

        ]);
        $node_plan->save();
      }
      // Save Development plan.
      $message = 'Development Plan "' . $title . '" added successfully.';
      $currentURL = Url::fromRoute('development-plan-details-form');
      $response->addCommand(new RedirectCommand($currentURL->toString()));
      drupal_set_message(t($message));
      $response->addCommand(new CloseModalDialogCommand());
    }
    return $response;
  }
  
  
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /*$activity_url = $form_state->getValue('url');
    if (isset($activity_url) && !empty($activity_url)) {
      $activity_url = strpos($activity_url, 'http') !== 0 ? "http://".$activity_url : $activity_url;
      if(!preg_match( '/^(http|https):\\/\\/[a-z0-9_]+([\\-\\.]{1}[a-z_0-9]+)*\\.[_a-z]{2,5}'.'((:[0-9]{1,5})?\\/.*)?$/i' ,$activity_url)){
        $form_state->setErrorByName('url', t('Not a valid URL'));
      } 
    }*/
  }
}
