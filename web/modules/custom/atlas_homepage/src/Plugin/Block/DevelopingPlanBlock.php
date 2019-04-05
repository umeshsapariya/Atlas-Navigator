<?php

namespace Drupal\atlas_homepage\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\node\Entity\Node;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\taxonomy\Entity\Term;
use Drupal\file\Entity\File;

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
        // Development node id.
        $development_node = Node::load($nid);

        // Development completed or not.
        $completed_or_not = $development_node->field_completed->getValue();
        if ($completed_or_not[0]['value']) {
          $developent_status = "plan_completed";
          $development_due_date_field = $development_node->field_completed_date->getValue();
          $development_due_date = $development_due_date_field[0]['value'];
          $date_obj = new DrupalDateTime($development_due_date, 'UTC');
          $due_date = $date_obj->format('M d');
        }
        else {
          // Development due date.
          $development_due_date_field = $development_node->field_due_date->getValue();
          $development_due_date = $development_due_date_field[0]['value'];
          $date_obj = new DrupalDateTime($development_due_date, 'UTC');
          $due_date = $date_obj->format('M d');
          $developent_status = "plan_in_pregress";
        }
        // Check development status.
        $due_date_time = strtotime($development_due_date_field[0]['value']);
        if ($due_date_time > time()) {
          $current_status = "not_expired";
        }
        else {
          $current_status = "expired";
        }
        // Get activity title.
        $assigned_activity_field = $development_node->field_learning_activity->getValue();
        $assigned_activity = $assigned_activity_field[0]['target_id'];
        $activity_node = Node::load($assigned_activity);
        $activity_title = '';
        if ($activity_node) {
          $activity_title = $activity_node->getTitle();
          $activity_url_arr = $activity_node->field_activity_url->getValue();
          if (isset($activity_url_arr[0]['value'])) {
            $options = [
              'attributes' => [
                'target' => [
                  '_blank',
                ],
              ],
            ];
            $activity_url = $activity_url_arr[0]['value'];
            $activity_url = strpos($activity_url, 'http') !== 0 ? "http://".$activity_url : $activity_url;
            $activity_url_obj = Url::fromUri($activity_url, $options);
            $activity_title = Link::fromTextAndUrl($activity_title, $activity_url_obj)->toString();
          }
          // Activity icon.
          $icon_src = '';
          $activity_type_tid_array = $activity_node->field_activity_type->getValue();
          if (isset($activity_type_tid_array[0]['target_id'])) {
            $activity_type_tid = $activity_type_tid_array[0]['target_id'];
            if ($activity_type_tid) {
              $activity_type_term = Term::load($activity_type_tid);
              $activity_type_term_array = $activity_type_term->field_icon->getValue();
              if (isset($activity_type_term_array[0]['target_id']) && $activity_type_term_array[0]['target_id']) {
                $icon_fid = $activity_type_term_array[0]['target_id'];
                $icon = File::load($icon_fid);
                $icon_url = $icon->url();
                $icon_src = '<img src="' . $icon_url . '">';
              }
            }
          }
        }

        $form['logo'] = [
          '#markup' => $icon_src,
        ];
        if ($activity_title != '') {
          $development_plan[] = [
            'nid' => $nid,
            'icon' => $form['logo'],
            'activity_title' => $activity_title,
            'due_date' => $due_date,
            'expired' => $current_status,
            'class' => $developent_status,
          ];
        }
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
