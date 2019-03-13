<?php

namespace Drupal\atlas_clone_paragraphs\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\InvokeCommand;

/**
 * ModalForm class.
 */
class CloneSkills extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'modal_form_copy_skill_modal_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $options = NULL) {

    $roles = getRoles();
    $form['#disable_inline_form_errors'] = TRUE;
    $form['#prefix'] = '<div id="copy_skill_modal_form">';
    $form['#suffix'] = '</div>';

    // The status messages that will contain any form errors.
    $form['status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -10,
    ];

    $cat_id = \Drupal::request()->query->get('cat_id');
    $form['cat_id'] = [
      '#type' => 'hidden',
      '#value' => $cat_id,
    ];

    $skill_id = \Drupal::request()->query->get('skill_id');
    $form['skill_id'] = [
      '#type' => 'hidden',
      '#value' => $skill_id,
    ];

    $form['role'] = [
      '#type' => 'select',
      '#options' => $roles,
      '#title' => 'Role',
      '#ajax' => [
        'callback' => '\Drupal\atlas_clone_paragraphs\Form\CloneSkills::getCategoriesByRoles',
        'wrapper' => 'category',
        // 'method' => 'replace',.
        'effect' => 'fade',
        'event' => 'change',
      ],
      '#required' => TRUE,
    ];
    $form['role']['#validated'] = TRUE;

    $category_options = ['_none' => '- Select -'];
    $role_id = $form_state->getValues() ? $form_state->getValues()['role'] : '';
    if ($role_id != '_none' && $role_id != NULL) {
      $category_options = getCategoriesByRolesID($role_id);
    }
    $form['category'] = [
      '#type' => 'select',
      '#options' => $category_options,
      '#default_value' => $category_options,
      '#title' => 'Category',
      '#prefix' => '<div id="category">',
      '#suffix' => '</div>',
      '#ajax' => [
        'callback' => '\Drupal\atlas_clone_paragraphs\Form\CloneSkills::getSkillsByCategory',
        'wrapper' => 'skill',
        // 'method' => 'replace',.
        'effect' => 'fade',
        'event' => 'change',
      ],
      '#required' => TRUE,
    ];
    $form['category']['#validated'] = TRUE;

    $skill_options = ['_none' => '- Select -'];
    $category_id = $form_state->getValues() ? $form_state->getValues()['category'] : '';
    if ($category_id != '_none' && $category_id != NULL) {
      $skill_options = getSkillsByCategoryID($category_id);
    }
    $form['skill'] = [
      '#type' => 'select',
      '#options' => $skill_options,
      '#default_value' => $skill_options,
      '#title' => 'Skill',
      '#prefix' => '<div id="skill">',
      '#suffix' => '</div>',
      '#required' => TRUE,
    ];
    $form['skill']['#validated'] = TRUE;

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['send'] = [
      '#type' => 'submit',
      '#value' => $this->t('Copy Skill'),
      '#attributes' => [
        'class' => [
          'use-ajax copy-skill-submit',
        ],
      ],
      '#ajax' => [
        'callback' => [$this, 'submitSkillFormAjax'],
        'event' => 'click',
      ],
    ];

    // $form['#attached']['library'][] = 'core/drupal.dialog.ajax';.
    $form['#attached']['library'][] = 'atlas_clone_paragraphs/clone_paragraphs';
    $form['#theme'] = 'copy_skill_form';

    return $form;
  }

  /**
   * AJAX callback handler that displays any errors or a success message.
   */
  public function submitSkillFormAjax(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    // If there are any form errors, re-display the form.
    if ($form_state->hasAnyErrors()) {
      $response->addCommand(new ReplaceCommand('#copy_skill_modal_form', $form));
    }
    else {
      // Clear the form errors.
      $form_state->clearErrors();
      $selected_data = [];
      $selected_skill = $form_state->getValues()['skill'];
      $selected_data['cat_no'] = $form_state->getValues()['cat_id'];
      $selected_data['skill_no'] = $form_state->getValues()['skill_id'];
      if (isset($selected_skill)) {
        $skill_info = Paragraph::load($selected_skill);
        $selected_data['skill_name'] = $skill_info->field_skill->value;
        $selected_data['skill_levels'] = $skill_info->field_number_of_levels->value;
        $selected_data['skill_include_na'] = $skill_info->field_include_na->value;
        $selected_data['skill_target_prof'] = $skill_info->field_target_proficiency->value;
        $skill_level_info = $skill_info->field_skill_level_information->getValue();
        $items = [];
        foreach ($skill_level_info as $element) {
          $p = Paragraph::load($element['target_id']);
          if (!empty($p->field_level_header->getValue()[0]['value'])) {
            $items[] = [$p->field_level_header->getValue()[0]['value'], strip_tags($p->field_level_description->getValue()[0]['value'])];
            // $element['target_id'] \Drupal::logger('Skill Level info')->info('<pre>' . print_r($selected_data, TRUE) . '</pre>');.
          }
        }
        $selected_data['skill_info'] = $items;
      }
      $response->addCommand(new InvokeCommand(NULL, 'cloneData', [$selected_data]));
      $response->addCommand(new CloseModalDialogCommand());
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValues()['role'] == '' || $form_state->getValues()['role'] == '_none') {
      $form_state->setErrorByName('role', t('Role field is required'));
    }
    if ($form_state->getValues()['category'] == '_none') {
      $form_state->setErrorByName('category', t('Category field is required'));
    }
    if ($form_state->getValues()['skill'] == '_none') {
      $form_state->setErrorByName('skill', t('Skill field is required'));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   *
   */
  public static function getCategoriesByRoles($form, FormStateInterface $form_state) {

    $form_state->setRebuild(TRUE);
    $category = ['_none' => '- Select -'];
    $role_id = $form_state->getValues()['role'];
    if ($role_id != 0 && $role_id != NULL) {
      $node = Node::load($role_id);
      $paragraph = $node->field_category->getValue();
      foreach ($paragraph as $element) {
        $p = Paragraph::load($element['target_id']);
        $category_nm = $p->field_new_category->value;
        $category[$element['target_id']] = $category_nm;
      }
      if ($category) {
        $form['category']['#options'] = $category;
      }
      else {
        $form['category']['#options'] = ['NA' => 'NA'];
      }

      return $form['category'];
    }
    else {
      $form['category']['#options'] = ['NA' => 'NA'];
      return $form['category'];
    }
  }

  /**
   *
   */
  public static function getSkillsByCategory($form, FormStateInterface $form_state) {

    $form_state->setRebuild(TRUE);
    $skill = ['_none' => '- Select -'];
    $category_id = $form_state->getValues()['category'];
    if ($category_id != 0 && $category_id != NULL) {
      $category_para = Paragraph::load($category_id);
      $paragraph = $category_para->field_skills->getValue();
      foreach ($paragraph as $element) {
        $p = Paragraph::load($element['target_id']);
        $skill_nm = $p->field_skill->value;
        $skill[$element['target_id']] = $skill_nm;
      }
      if ($skill) {
        $form['skill']['#options'] = $skill;
      }
      else {
        $form['skill']['#options'] = ['NA' => 'NA'];
      }
      return $form['skill'];
    }
    else {
      $form['skill']['#options'] = ['NA' => 'NA'];
      return $form['skill'];
    }
  }

}
