<?php

namespace Drupal\atlas_developing_plan\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\Datetime;
use Drupal\Core\Form\FormStateInterface;
// Use Drupal\Component\Utility\String;.
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;

/**
 * Class DevelopmentPlanDetails for Learning managment plan.
 */
class DevelopmentPlanDetails extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'atlas_developing_plan_details_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $current_user_id = \Drupal::currentUser()->id();
    $form['#prefix'] = '<div class="dev-page-wrap" id="output-results">';
    $form['#suffix'] = '</div>';

    $form['page-head'] = [
      '#type' => 'container',
      '#prefix' => '<div class="dev-page-head">',
      '#suffix' => '</div>',
    ];

    $form['page-head']['title'] = [
      '#markup' => 'Development Plan',
    ];
    $form['page-head']['container'] = [
      '#type' => 'container',
      '#prefix' => '<div class="dev-page-head-cta">',
      '#suffix' => '</div>',
    ];

//    $form['page-head']['container']['add-plan'] = [
//      '#markup' => '<a href="/node/add/learning_activity" target="_blank" class="btn btn-primary">Add</a>',
//    ];
    $form['page-head']['container']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Mark as complete'),
      '#ajax' => [
        'callback' => "::submitMarkAsCompleteAjax",
        'event' => 'click',
        'wrapper' => 'output-results',
        'progress' => [
          'type' => 'none',
        ],
      ],
    ];
    $form['page-head']['container']['status'] = [
      '#type' => 'select',
      '#options' => [
        1 => 'In Progress',
        0 => 'Completed',
      ],
      '#ajax' => [
        'callback' => '::get_development_plan_results',
        'event' => 'change',
        'wrapper' => 'output-results',
        'progress' => [
          'type' => 'none',
        ],
      ],
      '#default_value' => 1,
    ];
    // Operations (dropbutton) column.
    $form['plan_results']['operations'] = [
      '#type' => 'operations',
      '#links' => [],
    ];

    $form['plan_results'] = [
      '#type' => 'table',
      '#empty' => t('No Result Found'),
      '#cache' => [
        'max-age' => 0,
      ],
      '#tableselect' => TRUE,
    ];

    if ($form_state->getValue('status') == NULL) {
      $status = 1;

    }
    else {
      $status = $form_state->getValue('status');
      if ($status == 1) {
        $form['page-head']['container']['actions']['submit']['#value'] = $this->t('Mark as complete');
      }
      else {
        $form['page-head']['container']['actions']['submit']['#value'] = $this->t('Mark as in progress');
      }
    }
    $rows = [];

    $rows = $this->get_development_plan_listing($current_user_id, $status);

    foreach ($rows as $key => $activity) {
      $form['plan_results'][$key]['activity_info'] = [
        '#markup' => $activity['activity_info'],
      ];
      $form['plan_results'][$key]['activity_date'] = [
        '#markup' => $activity['date'],
      ];
      $form['plan_results'][$key]['operations'] = [
        '#type' => 'operations',
        '#links' => [],
      ];
      $options = ['absolute' => TRUE];

      if (!$activity['completed']) {
        $form['plan_results'][$key]['operations']['#links']['change_due_date'] = [
          'title' => t('Change Due Date'),
          '#type' => 'link',
          'url' => Url::fromRoute('change-activity-due-date-form', ['id' => $key]),
          'attributes' => [
            'class' => ['use-ajax'],
            'data-dialog-type' => 'modal',
            'data-dialog-options' => json_encode([
              'width' => 700,
            ]),
          ],
        ];
      }
      $form['plan_results'][$key]['operations']['#links']['remove'] = [
        'title' => t('Remove'),
        'url' => Url::fromRoute('confirm-remove-activity-form', ['id' => $key]),
        'attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => json_encode([
            'width' => 700,
          ]),
        ],
      ];
    }
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
   * Custom function to get development plan rows.
   */
  public function get_development_plan_listing($uid, $status) {
    $rows = [];
    $completed = false;
    $query = \Drupal::entityQuery('node')
      ->condition('status', NODE_PUBLISHED)
      ->condition('type', 'developing_plan')
      ->condition('field_assigned_user', $uid)
      ->condition('field_completed', $status, '!=');

    $development_nids = $query->execute();

    foreach ($development_nids as $development_nid) {
      $nodes = Node::load($development_nid);
      $activity_id = $nodes->get('field_learning_activity')->getValue()[0]['target_id'];
      $activity_completed = $nodes->get('field_completed')->getValue()[0]['value'];
      $activity_info = $this->get_activity_info($activity_id);
      $development_date = '';
      if (!empty($development_nid)) {
        $development_date = $this->get_development_date($development_nid);
      }
      $rows[$development_nid] = [
        'activity_info' => $activity_info,
        'date' => $development_date,
        'completed' => $activity_completed,
      ];

    }
    return $rows;
  }

  /**
   * Custom function to get activity info.
   */
  public function get_activity_info($activity_id) {
    $actiivityblock = \Drupal::service('plugin.manager.block')->createInstance('activity_popup', []);

    if (isset($actiivityblock) && !empty($actiivityblock)) {
      $activity_popup = $actiivityblock->build($activity_id);
    }
    $activity_node = Node::load($activity_id);
    if (!empty($activity_node->get('field_activity_type')->getValue()[0]['target_id'])) {
      $activity_type = $activity_node->get('field_activity_type')->getValue()[0]['target_id'];
    }
    $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($activity_type);
    $icon_url = '';
    if (!empty($term->field_icon->entity)) {
      $icon_uri = $term->field_icon->entity->getFileUri();
      $icon_url = file_create_url($icon_uri);
    }
    $element['#markup'] = '<div class="activity-info">
        <div class="activity-image">
        <img src="' . $icon_url . '"/></div>
        <div class"activity-title">' . \Drupal::service('renderer')->render($activity_popup) . '
        </div>
     </div>';
    $activity_info = \Drupal::service('renderer')->render($element);

    return $activity_info;
  }

  /**
   * Custom function to get development date.
   */
  public function get_development_date($development_nid) {
    $nodes = Node::load($development_nid);
    $development_plan_completed_date = '';
    $development_plan_date = $nodes->get('field_due_date')->getValue()[0]['value'];

    if (isset ($nodes->get('field_completed_date')->getValue()[0]['value']))
    $development_plan_completed_date = $nodes->get('field_completed_date')->getValue()[0]['value'];
    
    // To check developement activity is completed 
    
    $development_status = $nodes->get('field_completed')->getValue()[0]['value'];
    if (isset($development_plan_completed_date) && $development_status) {
      $development_plan_date = new DrupalDateTime($development_plan_completed_date, date_default_timezone_get());
   
    }else {
      $development_plan_date = new DrupalDateTime($development_plan_date, date_default_timezone_get());
    }
    // This will convert date/time in user timezone.
    $development_plan_date->setTimezone(timezone_open(drupal_get_user_timezone()));

    // This will return the required date format.
    $date = $development_plan_date->format('M y');
    return $date;
  }

  /**
   * Custom Ajax callback.
   */
  public function get_development_plan_results(array &$form, FormStateInterface $form_state): array {
    return $form;
  }

  /**
   * Custom Ajax callback.
   */
  public function submitMarkAsCompleteAjax(array &$form, FormStateInterface $form_state) {
    $rows = $form_state->getValue('plan_results');
    $status = $form_state->getValue('status');
    // For mark as complete.
    if ($status == 1) {
      foreach ($rows as $key => $row) {
        if ($row != 0) {
          $ids[] = $key;
          $node = Node::load($key);

          // This is a Field added in to the content type.
          $node->set('field_completed', 1);
          $today = date('Y-m-d'); 
          $node->set('field_completed_date', $today);
          $node->save();
          drupal_set_message(t('Activities has been marked as Completed.'));
        }
      }
    }
    elseif ($status == 0) {
      foreach ($rows as $key => $row) {
        if ($row != 0) {
          $ids[] = $key;

          $node = Node::load($key);

          // This is a Field added in to the content type.
          $node->set('field_completed', 0);
          $node->save();
          drupal_set_message(t('Activities has been marked as In Progress.'));
        }
      }
    }
    $response = new AjaxResponse();
    $currentURL = Url::fromRoute('development-plan-details-form');
    $response->addCommand(new RedirectCommand($currentURL->toString()));

    return $response;
  }

}
