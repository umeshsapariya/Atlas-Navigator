<?php

namespace Drupal\atlas_homepage\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\node\Entity\Node;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Provides a 'Developing Plan Homepage' Block.
 *
 * @Block(
 *   id = "DevelopingPlanBlock",
 *   admin_label = @Translation("Developing plan Homepage"),
 *   category = @Translation("Developing Plan Homepage"),
 * )
 */
class DevelopingPlanBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form['in-progress'] = [
      '#type' => 'button',
      '#value' => 'In progress',
    ];

    $form['select-check'] = [
      '#type' => 'checkbox',
      '#title' => ' ',
      '#attributes' => ['class' => ['dev-checkbox']],
    ];
    $form['completed'] = [
      '#type' => 'button',
      '#value' => 'completed',
      '#attributes' => ['class' => ['inactive']],
    ];
    $development_plan = [];
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'developing_plan')
      ->condition('field_assigned_user', \Drupal::currentUser()->id());
    $development_nids = $query->execute();
    if ($development_nids) {
      foreach ($development_nids as $nid) {
        // Development node id
        $development_node = Node::load($nid);

        // Development due date
        $development_due_date_field = $development_node->field_due_date->getValue();
        $development_due_date = $development_due_date_field[0]['value'];
        $date_obj = new DrupalDateTime($development_due_date, 'UTC');
        $due_date = $date_obj->format('M d');

        // Check development status
        $due_date_time = strtotime($development_due_date_field[0]['value']);
        if ($due_date_time > time()) {
          $current_status = "not_expired";
        }
        else {
          $current_status = "expired";
        }

        // Development completed or not
        $completed_or_not = $development_node->field_completed->getValue();
        if ($completed_or_not[0]['value']) {
          $developent_status = "plan_completed";
        }
        else {
          $developent_status = "plan_in_pregress";
        }

        // Get activity title
        $assigned_activity_field = $development_node->field_learning_activity->getValue();
        $assigned_activity = $assigned_activity_field[0]['target_id'];
        $activity_node = Node::load($assigned_activity);
        $activity_title = $activity_node->getTitle();

        // Get activity description
        $activity_body = $activity_node->body->getValue();
        $activity_description = '';
        if (isset($activity_body[0]['value'])) {
          $activity_description = substr(strip_tags($activity_body[0]['value']), 0, 10);
        }
        $development_plan[] = [
          'checkbox' => $form['select-check'],
          'nid' => $nid,
          'activity_title' => $activity_title,
          'activity_detail' => $activity_description,
          'due_date' => $due_date,
          'expired' => $current_status,
          'class' => $developent_status,
        ];
      }
    }

    $element = [
      '#theme' => 'developing_plan_home',
      '#development_plan' => $development_plan,
      '#progress_button' => $form['in-progress'],
      '#complete_button' => $form['completed'],
      '#attached' => [
        'library' => ['atlas_homepage/atlas_homepage_developing_plan'],
      ],
    ];

    return $element;
  }

}
