<?php

namespace Drupal\atlas_verbatim_comments\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 *
 */
class VerbatimComments extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'verbatile_comments_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $options = NULL) {
    // Get Current user UID.
    $current_user = \Drupal::currentUser();
    $uid = $current_user->id();

    $form['#prefix'] = '<div class="ver-comments-wrapper">';
    $form['#suffix'] = '</div>';

    $form['title'] = [
      '#markup' => '<div class="ver-comments-header"><span class="page-title">Verbatim Comments</span>',
    ];
    $form['name'] = [
      '#type' => 'textfield',
      '#autocomplete_route_name' => 'atlas_verbatim_comments.comment_autocomplete',
      '#prefix' => '<span class="search-filter">',
      '#suffix' => '</span></div>',
      '#maxlength' => 512,
      '#ajax' => [
        'callback' => '::getFilteredvalues',
        'wrapper' => 'ver-comments-content',
        'method' => 'replace',
        'event' => 'autocompleteclose',
        'progress' => [
          'type' => 'throbber',
        ],
      ],
    ];
    $header = [
      ['data' => t('Rater Group')],
      ['data' => t('Comment')],
      ['data' => t('Assessment ID')],
    ];

    $db = \Drupal::database();
    $query = $db->select('assessment_invite', 'ai');
    $query->innerJoin('assessment_invite_details', 'aid', 'aid.invite_id = ai.invite_id');
    $query->innerJoin('paragraphs_item_field_data', 'pfd', 'pfd.parent_id = ai.role_id');
    $query->innerJoin('paragraph__field_question', 'pfq', 'pfq.entity_id = pfd.revision_id');
    $query->fields('pfq', ['entity_id', 'field_question_value']);
    $query->condition('ai.uid', $uid);
    $query->condition('aid.completed', 1);
    $query->condition('pfd.type', 'verbatim_comments');
    $query->condition('pfd.parent_type', 'node');
    $query->distinct('pfq.entity_id');
    if (isset($form_state->getValues()['name'])) {
      $search_name = $form_state->getValues()['name'];
      $query->condition('pfq.field_question_value', '%' . db_like($search_name) . '%', 'LIKE');
    }
    // Limit the rows to 20 for each page.
    /* $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')
    ->limit(3);
    $result = $pager->execute(); */

    $verbatim_questions = $query->execute()->fetchAll();

    $form['comment-wrapper'] = [
      '#type' => 'fieldset',
      '#prefix' => '<div class="ver-comments-content" id="ver-comments-content"><div class="question-wrapper">',
      '#suffix' => '</div></div>',
    ];
    foreach ($verbatim_questions as $question) {
      $rows = [];
      $comment_id = $question->entity_id;
      $form['comment-wrapper']['question_' . $question->entity_id] = [
        '#markup' => t('Q. ' . $question->field_question_value),
        '#prefix' => '<div class="ver-comments-question">',
        '#suffix' => '</div>',
      ];
      $comments = getVerbatimComments($uid, $comment_id);
      // Populate the rows.
      foreach ($comments as $id => $row) {
        $rows[] = [
          'data' => [
            $row['relationship'],
            $row['comment'],
            $row['assessment_id'],
          ],
        ];
      }
      // Generate the table.
      $form['comment-wrapper']['config_table_' . $question->entity_id] = [
        '#theme' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#empty' => t('No data found'),
      ];
    }

    if (!$verbatim_questions) {
      $form['comment-wrapper']['no-results'] = [
        '#markup' => '<span>No matches found</span>',
      ];
    }
    else {
      unset($form['comment-wrapper']['no-results']);
    }

    $form['comment-wrapper']['suffix'] = [
      '#markup' => '</div></div>',
    ];

    $form['#theme'] = 'verbatile_comments_form';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   *
   */
  public static function getFilteredvalues($form, FormStateInterface $form_state) {
    return $form['comment-wrapper'];
  }

}
