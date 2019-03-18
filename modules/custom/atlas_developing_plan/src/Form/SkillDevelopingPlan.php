<?php

namespace Drupal\atlas_developing_plan\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Class SkillDevelopingPlan for Learning managment plan.
 */
class SkillDevelopingPlan extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'atlas_skill_developing_plan_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Development top container.
    $assigned_activities = [];
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'developing_plan')
      ->condition('field_assigned_user', \Drupal::currentUser()->id());
    $development_nids = $query->execute();
    foreach ($development_nids as $development_nid) {
      $development_node = Node::load($development_nid);
      $assigned_activity_arr = $development_node->field_learning_activity->getValue();
      $assigned_activities[] = $assigned_activity_arr[0]['target_id'];
    }
    $current_path = \Drupal::service('path.current')->getPath();
    $current_path_array = explode('/', $current_path);
    $skill_paragraph_id = end($current_path_array);

    $skill_paragraph = Paragraph::load($skill_paragraph_id);
    $level_description_paragraph_ids = $skill_paragraph->field_skill_level_information->getValue();
    $number_of_level_field = $skill_paragraph->field_number_of_levels->getValue();
    $skill_related_activity = [];
    if (isset($number_of_level_field[0]['value'])) {
      $number_of_level = $number_of_level_field[0]['value'];

      $activity = 1;
      for ($delta = 1; $delta <= $number_of_level; $delta++) {
        $skill_level_target_id = $level_description_paragraph_ids[$delta - 1]['target_id'];
        $paragraph_skill_level = Paragraph::load($skill_level_target_id);
        if ($paragraph_skill_level->field_assigned_activity->getValue()) {
          $activity_nids = $paragraph_skill_level->field_assigned_activity->getValue();
          foreach ($activity_nids as $value) {
            if (!in_array($value['target_id'], $assigned_activities)) {
              $skill_related_activity[] = ['nid' => $value['target_id'], 'level' => $delta, 'Total level' => $number_of_level];
              $activity++;
            }
          }
        }
      }
    }

    $form['head'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['dev-head']],
    ];
    // Development title.
    // Development select list.

    if ($skill_related_activity) {
      $form['head']['title'] = [
        '#markup' => '<div class="box_title blue_title" rel="box1">Development plan</div>',
      ];
      $form['head']['level_select'] = [
        '#type' => 'select',
        '#options' => [1, 2, 3, 4, 5],
      ];

      // Add to plan button.
      $form['submit'] = [
        '#type' => 'submit',
        '#prefix' => '<div class="dev-addplan">
<div class="addplan_dev">',
        '#suffix' => '</div>
</div>',
        '#value' => t('Add to Plan'),
      ];

      // Activites container.
      $form['activites'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['dev-plan-cont scroll_div_content']],
      ];
      foreach ($skill_related_activity as $delta => $activity) {
        // Each activity container.
        $class_count = $delta + 1;
        $row_class = 'level_' . (string) $class_count;
        $form['activites']['activity-' . $delta] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['dev_row_cont change_ques ' . $row_class]],
        ];
        // Activity logo.
        $form['activites']['activity-' . $delta]['logo-' . $delta] = [
          '#markup' => '<div class="title-logo"></div>',
        ];

        $form['activites']['activity-' . $delta]['nid-' . $delta] = [
          '#type' => 'hidden',
          '#value' => $activity['nid'],
        ];
        // Activity name.
        $activity_node = Node::load($activity['nid']);
        $form['activites']['activity-' . $delta]['name-' . $delta] = [
          '#markup' => $activity_node->getTitle(),
          '#prefix' => '<div class="title-plan">',
          '#suffix' => '</div>',
        ];
        $form['activites']['activity-' . $delta]['title-' . $delta] = [
          '#type' => 'hidden',
          '#value' => $activity_node->getTitle(),
        ];
        // Activity select field.
        $form['activites']['activity-' . $delta]['select-check-' . $delta] = [
          '#type' => 'checkbox',
          '#title' => ' ',
          '#attributes' => ['class' => ['dev-checkbox']],
        ];
        // Activity date field.
        $pre_date = '';
        if ($form_state->getValue('date-' . $delta)) {
          $pre_date = $form_state->getValue('date-' . $delta);
        }
        $form['activites']['activity-' . $delta]['date-' . $delta] = [
          '#type' => 'date',
          '#attributes' => ['class' => ['calender-btn']],
          '#format' => 'm/d/Y',
          '#suffix' => '<span class="dev-date">' . $pre_date . '</span>',
        ];
      }
    }
    else {
      $form['head']['title'] = [
        '#markup' => '<div class="box_title blue_title" rel="box1">No development plan found</div>',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $submit_data = $form_state->getValues();
    foreach ($submit_data as $key => $value) {
      if (preg_match("/select-check-/", $key) && $submit_data[$key] == 1) {
        $key_array = explode('-', $key);
        $selected_checkbox = $key_array[2];
        if (empty($submit_data['date-' . $selected_checkbox])) {
          $activity = $submit_data['title-' . $selected_checkbox];
          $form_state->setErrorByName('date-' . $selected_checkbox, "Please enter Due date of " . $activity);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $submit_data = $form_state->getValues();
    $selected_checkbox = [];
    $uid = \Drupal::currentUser()->id();
    $user = user_load($uid);
    $username = $user->name;
    foreach ($submit_data as $key => $value) {
      if (preg_match("/select-check-/", $key) && $submit_data[$key] == 1) {
        $key_array = explode('-', $key);
        $selected_checkbox[] = $key_array[2];
      }
    }
    if (!empty($selected_checkbox)) {
      foreach ($selected_checkbox as $selected) {
        $activity_nid = $submit_data['nid-' . $selected];
        $due_date = $submit_data['date-' . $selected];
        $activity_node = node_load($activity_nid);
        $account = User::load($uid);
        $name = $account->getUsername();
        $account = User::load($uid);
        $name = $account->getUsername();
        // Create node object.
        $node = Node::create([
            'type' => 'developing_plan',
            'title' => $name . ' ' . $activity_node->getTitle() . ' Developing plan',
            'field_assigned_user' => $uid,
            'field_due_date' => $due_date,
            'field_learning_activity' => $activity_nid,
        ]);
        $node->save();
      }
    }
    drupal_set_message("Developing plan added successfully", 'status');
  }

}
