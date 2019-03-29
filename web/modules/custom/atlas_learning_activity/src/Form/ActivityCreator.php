<?php

namespace Drupal\atlas_learning_activity\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * ModalForm class.
 */
class ActivityCreator extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'activity_node_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // For Load first name and lastname from profile.
    // Disable caching for the form
    $form['#cache'] = ['max-age' => 0];

    // Do not flatten nested form fields
    $form['#tree'] = TRUE;

    $form['categories_container'] = array(
      '#type' => 'fieldset',
      '#title' => t('Categories'),
      "#prefix" => '<div id="activity-wrapper">',
      "#suffix" => '</div>',
      '#collapsible' => TRUE, // Added
      '#collapsed' => FALSE, // Added
      '#weight' => 99,
    );
    $name_field = $form_state->get('num_names');
    if (empty($form_state->get('num_names'))) {
      $form_state->set('num_names', 1);
      $name_field = $form_state->get('num_names');
    }
    for ($i = 0; $i < $name_field; $i++) {
      $form['paragraph_id'] = [
          '#type' => 'hidden',
          '#value' => $i,
      ];
      $form['categories_container']['container'][$i] = array(
        '#type' => 'container',
      );
      $roles = getRoles();
      $form['categories_container']['container'][$i]['role'] = [
        '#type' => 'select',
        '#options' => $roles,
        '#title' => 'Role',
        '#ajax' => [
          'callback' => '::getAjaxCategoriesByRoles',
          'wrapper' => 'category-'.$i,
          'effect' => 'fade',
          'event' => 'change',
        ],
        '#required' => TRUE,
      ];
      $form['categories_container']['container'][$i]['role']['#validated'] = TRUE;

      $category_options = ['_none' => '- Select -'];
      $role_id = $form_state->getValues() ? $form_state->getValues()['categories_container']
        ['container'][$i]['role'] : '';
      if ($role_id != '_none' && $role_id != NULL) {
        $category_options = getCategoriesByRolesID($role_id);
      }
      $form['categories_container']['container'][$i]['category'] = [
        '#type' => 'select',
        '#options' => $category_options,
        '#default_value' => $category_options,
        '#title' => 'Category',
        '#prefix' => '<div id="category-'.$i.'">',
        '#suffix' => '</div>',
        '#ajax' => [
          'callback' => '::getAjaxSkillsByCategory',
          'wrapper' => 'skill-'.$i,
          'effect' => 'fade',
          'event' => 'change',
        ],
        '#required' => TRUE,
      ];
      $form['categories_container']['container'][$i]['category']['#validated'] = TRUE;

      $skill_options = ['_none' => '- Select -'];
      $category_id = $form_state->getValues() ? $form_state->getValues()['categories_container']
        ['container'][$i]['category'] : '';
      if ($category_id != '_none' && $category_id != NULL) {
        $skill_options = getSkillsByCategoryID($category_id);
      }
      $form['categories_container']['container'][$i]['skill'] = [
        '#type' => 'select',
        '#options' => $skill_options,
        '#default_value' => $skill_options,
        '#title' => 'Skill',
        '#prefix' => '<div id="skill-'.$i.'">',
        '#suffix' => '</div>',
        '#ajax' => [
          'callback' => '::getAjaxLevelsBySkill',
          'wrapper' => 'level-'.$i,
          'effect' => 'fade',
          'event' => 'change',
        ],
        '#required' => TRUE,
      ];
      $levels = 10;
      $options = [];
      $skill_id = $form_state->getValues() ? $form_state->getValues()['categories_container']
        ['container'][$i]['skill'] : '';
      if ($skill_id != '_none' && $skill_id != NULL) {
        $skill_para = Paragraph::load($skill_id);
        //$levels = $skill_para->field_number_of_levels->value;
        $skill_level_informations = $skill_para->field_skill_level_information->getValue();
        //\Drupal::logger('Skill info')->info('<pre>' . print_r($skill_level_informations, TRUE) . '</pre>');
        //kint($skill_level_informations);
        //exit();
        foreach ($skill_level_informations as $key => $value) {
          $sl = Paragraph::load($value['target_id']);
          $field_level_header = $sl->field_level_header->getValue();
          if (!empty($field_level_header)) {
            $options[$value['target_id']] = $key+1;
          }
        }
        //\Drupal::logger('Options')->info('<pre>' . print_r($options, TRUE) . '</pre>');
      }
      else {
        for ($j = 1; $j <= $levels; $j++) {
          $options[$j] = $j;
        }
      }
      $form['categories_container']['container'][$i]['level'] = [
        '#type' => 'checkboxes',
        '#title' => 'Level',
        '#prefix' => '<div id="level-'.$i.'">',
        '#suffix' => '</div>',
        '#options' => $options,
      ];
      $form['categories_container']['container'][$i]['skill']['#validated'] = TRUE;

      if ($name_field > 1) {
        $form['categories_container']['container'][$i]['activity_remove_item'] = array(
          '#type' => 'submit',
          '#name' => 'activity-remove-' . $i,
          '#value' => t('Remove'),
          '#submit' => array('::activity_remove_items'),
          // Since we are removing a name, don't validate until later.
          '#validated' => TRUE,
          '#prefix' => "<div id='activity-remove-item'>",
          '#suffix' => "</div>",
          '#ajax' => array(
            'callback' => '::activity_remove_item_callback', //'activity_add_item_callback',
            'wrapper' => 'activity-wrapper',
          ),
        );
      }
    }
    $form['categories_container']['activity_add_item'] = [
      '#type' => 'submit',
      '#name' => 'add_activity',
      '#value' => t('Add'),
      '#submit' => array('::activity_add_items'),
      '#validated' => TRUE,
      '#prefix' => "<div id='activity-add-item'>",
      '#suffix' => "</div>",
      '#ajax' => array(
        'callback' => '::activity_add_item_callback',
        'wrapper' => 'activity-wrapper',
      ),
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
    );

    return $form;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  function activity_remove_items(array &$form, FormStateInterface $form_state) {
    $name_field = $form_state->get('num_names');
    if ($name_field > 1) {
      $remove_button = $name_field - 1;
      $form_state->set('num_names', $remove_button);
    }
    $form_state->setRebuild();
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed
   */
  function activity_remove_item_callback(array &$form, FormStateInterface $form_state) {
    return $form['categories_container'];
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  function activity_add_items(array &$form, FormStateInterface $form_state) {
    $name_field = $form_state->get('num_names');
    $add_button = $name_field + 1;
    $form_state->set('num_names', $add_button);
    $form_state->setRebuild();
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed
   */
  function activity_add_item_callback(array &$form, FormStateInterface $form_state) {
    return $form['categories_container'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValues()['categories_container']['container'] as $key => $data) {
      $skill_id = $data['skill'];
      $levels = $data['level'];
      $skill_para = Paragraph::load($skill_id);
      $skill_level_informations = $skill_para->field_skill_level_information->getValue();
      $selected_levels = array_filter($levels);
      //kint($selected_levels);
      //exit();
      // To get skill level info.
      foreach ($selected_levels as $key => $value) {
        foreach ($skill_level_informations as $skill_level) {
          if ($skill_level['target_id'] != NULL && ($skill_level['target_id'] == $key)) {
            $sl = Paragraph::load($skill_level['target_id']);
            $skill_level_id = $skill_level['target_id'];
            //$field_level_header = $sl->field_level_header->getValue();
            if (!empty($field_level_header[0]['value'])) {
              $s1->field_assigned_activity[] = [
                'target_id' => $skill_level['target_id'],
                'target_revision_id' => $skill_level['target_id'],
              ];
            }
            kint($skill_level_id);
            kint($field_level_header);
            //exit();
          }
          //$field_level_header = $sl->field_level_header->getValue();
          //$field_level_description = $sl->field_level_description->getValue();
          /*if (!empty($field_level_header[0]['value'])) {
            $skill_data[$skill_level_id]['label'] = $field_level_header[0]['value'];
            $skill_data[$skill_level_id]['description']['value'] = $field_level_description[0]['value'];
            $skill_data[$skill_level_id]['description']['format'] = $field_level_description[0]['format'];
          }*/
        }
      }
      exit();
      //kint($skill_para->field_skill_level_information->value);
      //exit();
    }
    exit();

  }

  public static function getAjaxCategoriesByRoles($form, FormStateInterface $form_state) {
    $paragraph_id = $form_state->getValues()['paragraph_id'];
    return $form['categories_container']['container'][$paragraph_id]['category'];
  }

  public static function getAjaxSkillsByCategory($form, FormStateInterface $form_state) {
    $paragraph_id = $form_state->getValues()['paragraph_id'];
    return $form['categories_container']['container'][$paragraph_id]['skill'];
  }

  public static function getAjaxLevelsBySkill($form, FormStateInterface $form_state) {
    $paragraph_id = $form_state->getValues()['paragraph_id'];
    return $form['categories_container']['container'][$paragraph_id]['level'];
  }

}
