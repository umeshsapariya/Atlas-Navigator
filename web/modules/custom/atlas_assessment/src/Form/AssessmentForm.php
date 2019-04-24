<?php

namespace Drupal\atlas_assessment\Form;

use Drupal\Core\Form\FormBase;
use Drupal\node\Entity\Node;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\user\Entity\User;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;


/**
 * Contribute form.
 */
class AssessmentForm extends FormBase {

  protected $step = 1;
  protected $temp = TRUE;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'assessment_multi_step_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $hash = NULL) {

    // To load form from given step.
    if (isset($step)) {
      $this->step = $step;
    }
    // To get invite id.
    $connection = \Drupal::database();
    $query = $connection->select('assessment_invite_details', 'aid');
    $query->addField('aid', 'invite_id');
    $query->addField('aid', 'id');
    $query->addField('aid', 'raters_uid');
    $query->addField('aid', 'raters_name');
    $query->condition('aid.hash', $hash);
    $query->condition('aid.completed', 0);
    $query->range(0, 1);
    $invite = $query->execute()->fetchAssoc();

    $invite_id = $invite['invite_id'];
    $id = $invite['id'];
    $raters_uid = $invite['raters_uid'];
    // Get Rater's name.
    if ($raters_uid == 0) {
      $raters_name = $invite['raters_name'];
    }
    else {
      // Pass your uid.
      $account = User::load($raters_uid);
      $raters_name = $account->getUsername();
    }
    $raters_name = preg_replace('/\s+/', '_', $raters_name);

    // To get uid and Role id.
    $connection = \Drupal::database();
    $query = $connection->select('assessment_invite', 'ai');
    $query->addField('ai', 'uid');
    $query->addField('ai', 'role_id');
    $query->condition('ai.invite_id', $invite_id);
    $query->range(0, 1);
    $invite_data = $query->execute()->fetchAssoc();
    // Store access_uid, role_id and hash value to be store in database.
    $form_state->setFormState([
      'assess_uid' => $raters_uid,
      'role_id' => $invite_data['role_id'],
      'hash' => $hash,
      'id' => $id,
      'raters_name' => $raters_name,
    ]);
    if (!empty($invite_data['role_id'])) {
      // Set role_id to nid.
      $nid = $invite_data['role_id'];
      $role_skills = $this->get_role_skills_data($nid);
      if (!empty($role_skills)) {
        // Use temp veriable to check it must executed at first time form load.
        if ($this->temp) {
          // To get step_id from assessment_data if form is saved as draft.
          $connection = \Drupal::database();
          $query = $connection->select('assessment_data', 'ad');
          $query->addField('ad', 'step_id');
          $query->condition('ad.invite_id', $id);
          $query->range(0, 1);
          $step_id = $query->execute()->fetchField();
          // To load from from give step.
          if ($step_id) {
            $this->step = $step_id;
            // Set temp variable to false.
            $this->temp = FALSE;
          }
        }
        $categories = [];
        $comments = [];

        $count = 1;
        $skill_count = 0;
        $comment_count = 0;
        $comments = $role_skills[$nid]['comment'];
        $comment_count = count($comments);
        // Create Assessment Form.
        if (!empty($role_skills[$nid]['category'])) {
          $categories = $role_skills[$nid]['category'];
          foreach ($categories as $cat => $category) {
            $category_name = $category['name'];
            $skills = $category['skill'];
            $skill_count = $skill_count + count($skills);
            foreach ($skills as $ski => $skill) {
              $temp[] = $ski;
              $skill_name = $skill['name'];
              // Generate heading using category and skill name.
              $heading_title = '<div class="outer_wrapper"><div class="heading_titles"><div class="category">' . $category_name . '</div><div class="skill">' . $skill_name . '</div></div>';

              $markup = '';
              $markup .= '<div class="rating_main_wrapper"> <div class="rating_heading">rating description</div><div class="scroll_div_content content_wrapper">';
              $sp = Paragraph::load($ski);
              // Check for include options.
              $include_na = $sp->field_include_na->getValue();
              $levels = $skill['level'];
              $level_count = count($levels);
              // Create options for skill.
              $level_options = range(0, $level_count);
              // Generate markup for levels.
              foreach ($levels as $level) {
                $markup .= '<div class="rating_outer_box"><div class="rating_label">' . $level['label'] . '</div><div class="rating_desc">' . $level['description']['value'] . '</div></div>';
              }
              $markup .= '</div></div><div class="no_of_levels">';
              // Unset option 0 as option 0 is for NA.
              unset($level_options[0]);
              if ($this->step == $count) {
                // Get default value for level from DB.
                $query = db_select('assessment_skill_data', 'asd');
                $query->join('assessment_data', 'ad', 'ad.assessment_id = asd.assessment_id');
                $query->condition('asd.category_id', $cat, '=');
                $query->condition('asd.skill_id', $ski, '=');
                $query->condition('ad.invite_id', $id, '=');
                $score = $query->fields('asd', ['score'])->execute()->fetchField();

                if ($include_na[0]['value'] == 1) {
                  $level_options[-1] = 'n/a';
                }
                $form['heading_title'] = [
                  '#markup' => $heading_title,
                ];

                $default_value = empty($form_state->get("level-" . $this->step)) ? $score : $form_state->get("level-" . $this->step);
                $form_state->setFormState([
                  "level-" . $this->step => $default_value,
                ]);
                $form['level-' . $this->step] = [
                  '#type' => 'radios',
                  '#options' => $level_options,
                  '#prefix' => $markup,
                  '#suffix' => '</div></div>',
                  '#default_value' => $default_value,
                  '#cache' => [
                    'max-age' => 0,
                  ],
                ];
                $form['category-' . $this->step] = [
                  '#type' => 'hidden',
                  '#value' => $cat,
                ];

                $form['skill-' . $this->step] = [
                  '#type' => 'hidden',
                  '#value' => $ski,
                ];
              }
              // To increase steps.
              $count++;
            }
          }
        }

        // At last add verbatim comments.
        if (!empty($role_skills[$nid]['comment'])) {
          $comments = $role_skills[$nid]['comment'];
          if ($this->step == $count) {
            $num = 1;
            $comment_markup = '<div class="comments_wraper scroll_div_content">';
            $form['comment_markup_start'] = [
              '#markup' => $comment_markup,
            ];
            $comment_count = count($comments);

            foreach ($comments as $com => $comment) {
              // Get default value for level from DB.
              $query = db_select('assessment_verbatim_comments', 'avc');
              $query->join('assessment_data', 'ad', 'ad.assessment_id = avc.assessment_id');
              $query->condition('avc.verbatim_id', $com, '=');
              $query->condition('ad.invite_id', $id, '=');
              $comment_value = $query->fields('avc', ['answer'])->execute()->fetchField();
              if (!empty($comment)) {

                $form['comment-' . $num] = [
                  '#type' => 'textarea',
                  '#title' => $comment,
                  '#cache' => [
                    'max-age' => 0,
                  ],
                ];
                $default_value = !empty($form_state->get("comment-" . $num)) ? $form_state->get("comment-" . $num) : $comment_value;
                $form['comment-' . $num]['#default_value'] = $default_value;
                $form['comment-id-' . $num] = [
                  '#type' => 'hidden',
                  '#value' => $com,
                  '#cache' => [
                    'max-age' => 0,
                  ],
                ];
                $num++;
              }
            }

            $form['comment_markup_end'] = [
              '#markup' => '</div>',
            ];
          }
        }

        if ($comment_count > 0) {
          $steps_count = $skill_count + 1;
        }
        else {
          $steps_count = $skill_count;
        }
        $form['current_step'] = [
          '#markup' => $this->step,
        ];

        $form['steps_count'] = [
          '#markup' => $steps_count,
        ];
        // Add prev button if step is greater than 1.
        if ($this->step > 1) {
          $form['actions']['back'] = [
            '#type' => 'submit',
            '#value' => $this->t('Prev'),
            '#submit' => ['::gopreviousSubmit'],
          ];
        }
        // Add Save as Draft button.
        $form['actions']['draft'] = [
          '#type' => 'submit',
          '#value' => 'Save as Draft',
          '#submit' => ['::saveAsDraftSubmit'],
        ];

        if ($this->step < $steps_count) {
          $button_label = $this->t('Next');
          $form['actions']['submit']['#limit_validation_errors'] = [];
        }
        else {
          $button_label = $this->t('Submit');
        }

        $form['actions']['submit'] = [
          '#type' => 'submit',
          '#value' => $button_label,
        ];
        // To set no of steps.
        $form_state->setFormState([
          'steps_count' => $steps_count,
        ]);
        $form_state->setFormState([
          'skill_count' => $skill_count,
        ]);
        $form_state->setFormState([
          'invite_id' => $invite_id,
        ]);
        $form_state->setFormState([
          'comment_count' => count($comments),
        ]);
        $form_state->setFormState([
          'temp' => $temp,
        ]);
      }
      $form['#theme'] = 'assessment_multi_step_form';
    }
    else {
      $form['inactive'] = [
        '#markup' => 'You have already completed Assessment for this role.',
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $id = $form_state->get("id");
    $triggering_element = $form_state->getTriggeringElement();
    $skill_count = $form_state->get('skill_count');
    $comment_count = $form_state->get('comment_count');
    // Save form data.
    $level_value = $form_state->getValue("level-" . $this->step);
    $category_value = $form_state->getValue("category-" . $this->step);
    $skill_value = $form_state->getValue("skill-" . $this->step);
    $form_state->setFormState([
      "level-" . $this->step => $level_value,
      "category-" . $this->step => $category_value,
      "skill-" . $this->step => $skill_value,
    ]);
    $j = 1;

    $steps_count = $form_state->get('steps_count');
    $comments = $form_state->getValues([]);
    if ($this->step == $steps_count) {
      while ($j <= $comment_count) {
        $form_state->setFormState([
          "comment-" . $j => $comments['comment-' . $j],
          "comment-id-" . $j => $comments['comment-id-' . $j],
        ]);
        $j++;
      }
    }
    // To get stored values from db.
    $connection = Database::getConnection();
    $query = $connection->select('assessment_data', 'ad');
    $query->Join('assessment_skill_data', 'asd', 'asd.assessment_id= ad.assessment_id');
    $query->fields('asd', ['skill_id']);

    $query->condition('ad.invite_id', $id);
    $assessment_data = $query->execute()->fetchAll();
    foreach ($assessment_data as $assessment) {
      $ass_data[] = $assessment->skill_id;
    }

    $values = $form_state->get([]);
    $temp = $form_state->get('temp');

    if (!empty($triggering_element['#is_button'])) {
      $button_value = $triggering_element['#value'];
      if ($button_value == 'Submit') {
        $t_count = 1;
        if ($ass_data != NULL) {
          foreach ($temp as $t) {
            if (!in_array($t, $ass_data)) {
              $form_state->setErrorByName('test', t('Please select value for @name.', ['@name' => 'Question no ' . $t_count]));
              $form_state->setRebuild(TRUE);
            }
            $t_count++;
          }
        }
        else {
          $values = $form_state->get([]);
          $i = 1;
          while ($i <= $skill_count) {
            $scores[$i] = [
              'level' => $values['level-' . $i],
              'skill' => $values['skill-' . $i],
            ];

            if ($scores[$i]['level'] == FALSE) {
              $form_state->setErrorByName('test', t('Please select value for @name.', ['@name' => 'Question no ' . $i]));
              $form_state->setRebuild(TRUE);
            }
            $i++;
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $skill_count = $form_state->get('skill_count');
    $invite_id = $form_state->get("invite_id");
    $comments = $form_state->getValues();
    $id = $form_state->get("id");
    $steps_count = $form_state->get('steps_count');

    if ($this->step < $steps_count) {
      $form_state->setRebuild(TRUE);
      $this->step++;
    }
    else {
      $values = $form_state->get([]);
      // ksm($values);
      $i = 1;
      $j = 1;
      while ($i <= $skill_count) {
        $scores[$i] = [
          'category' => $values['category-' . $i],
          'skill' => $values['skill-' . $i],
          'level' => $values['level-' . $i],
        ];
        $i++;
      }
      $comment_count = $form_state->get('comment_count');

      while ($j <= $comment_count) {
        $ver_comments[$j] = [
          'comment' => $comments['comment-' . $j],
          'comment-id' => $comments['comment-id-' . $j],
        ];
        $j++;
      }
      $connection = \Drupal::database();

      $assessment_data = $connection->merge('assessment_data')
        ->key(['invite_id' => $id])
        ->fields(
            [
              'assess_uid' => $form_state->get('assess_uid'),
              'role_id' => $form_state->get('role_id'),
              'step_id' => $this->step + 1,
            ]
          )->execute();

      // Get $assessment_id.
      if ($assessment_data) {
        $query = $connection->select('assessment_data', 'ad');
        $query->addField('ad', 'assessment_id');
        $query->condition('ad.invite_id', $id);
        $query->range(0, 1);
        $assessment_id = $query->execute()->fetchField();
      }

      if ($assessment_id) {
        // Insert Assessment skill details.
        foreach ($scores as $score) {
          if (!empty($score['category']) && !empty($score['level'])) {
            $assessment_skill_data_id = $connection->merge('assessment_skill_data')
              ->key(['assessment_id' => $assessment_id])
              ->key(['category_id' => $score['category']])
              ->key(['skill_id' => $score['skill']])
              ->fields(
                  [
                    'score  ' => $score['level'],
                  ]
                )->execute();
          }
        }
        foreach ($ver_comments as $ver_comment) {
          if (!empty($ver_comment['comment-id'])) {
            $assessment_verbatim_comments_id = $connection->merge('assessment_verbatim_comments')
              ->key(['assessment_id' => $assessment_id])
              ->key(['verbatim_id' => $ver_comment['comment-id']])
              ->fields(
                  [
                    'answer ' => $ver_comment['comment'],
                  ]
                )->execute();
          }
        }
        $updated = $connection->update('assessment_invite_details')->fields(
            [
              'completed' => 1,
            ]
          )
          ->condition('hash', $form_state->get('hash'), '=')
          ->execute();

        if ($updated) {
          // Generate Atlas Assessment ID.
          $uid = $form_state->get('assess_uid');
          $role_id = $form_state->get('role_id');
          $role = Node::load($role_id);
          $role_name = $role->getTitle();
          $role_name = preg_replace('/\s+/', '_', $role_name);
          // Pass your uid.
          $account = User::load($uid);
          // Close video popup.
          $current_user_id = \Drupal::currentUser()->id();
          if ($current_user_id == $uid) {
            $account->set('field_tutorial_watched', 1);
            $account->save();
          }
          $rater_name = $form_state->get('raters_name');
          $month = date('m');
          $year = date("Y");
          $connection->update('assessment_data')->fields(
              [
                'atlas_assessment_id' => $rater_name . '_' . $role_name . '_' . $month . '_' . $year . '_' . $assessment_id,
              ]
            )
            ->condition('invite_id', $id, '=')
            ->execute();
        }
        $form_state->setRedirect('assessment-form.thank_you_page');
      }
    }
  }

  /**
   * Custom function for previous step.
   */
  public function gopreviousSubmit(array &$form, FormStateInterface $form_state) {
    // Store comments values.
    $steps_count = $form_state->get('steps_count');
    if ($this->step <= $steps_count) {
      $form_state->setRebuild(TRUE);
      $this->step--;
    }
  }

  /**
   * Custom function for previous step.
   */
  public function saveAsDraftSubmit(array &$form, FormStateInterface $form_state) {
    $invite_id = $form_state->get("invite_id");
    $id = $form_state->get("id");
    $steps_count = $form_state->get('steps_count');
    $skill_count = $form_state->get('skill_count');
    $comment_count = $form_state->get('comment_count');
    $values = $form_state->get([]);
    $i = 1;
    $j = 1;
    while ($i <= $skill_count) {
      $scores[$i] = [
        'category' => $values['category-' . $i],
        'skill' => $values['skill-' . $i],
        'level' => $values['level-' . $i],
      ];
      $i++;
    }
    while ($j <= $comment_count) {
      $ver_comments[$j] = [
        'comment' => $values['comment-' . $j],
        'comment-id' => $values['comment-id-' . $j],
      ];
      $j++;
    }
    $connection = \Drupal::database();

    if ($this->step == $steps_count) {
      $step_id = $this->step;
    }
    else {
      $step_id = $this->step + 1;
    }
    $assessment_data = $connection->merge('assessment_data')
      ->key(['invite_id' => $id])
      ->fields(
          [
            'assess_uid' => $form_state->get('assess_uid'),
            'role_id' => $form_state->get('role_id'),
            'step_id' => $step_id,
          ]
        )->execute();
    // Get $assessment_id.
    if ($assessment_data) {
      $query = $connection->select('assessment_data', 'ad');
      $query->addField('ad', 'assessment_id');
      $query->condition('ad.invite_id', $id);
      $query->range(0, 1);
      $assessment_id = $query->execute()->fetchField();
    }

    if ($assessment_id) {
      // Insert Assessment skill details.
      foreach ($scores as $score) {
        if (!empty($score['category']) && !empty($score['level'])) {
          $assessment_skill_data_id = $connection->merge('assessment_skill_data')
            ->key(['assessment_id' => $assessment_id])
            ->key(['category_id' => $score['category']])
            ->key(['skill_id' => $score['skill']])
            ->fields(
                [
                  'score  ' => $score['level'],
                ]
              )->execute();
        }
      }
      if (isset($ver_comments) && $ver_comments != NULL) {
        foreach ($ver_comments as $ver_comment) {
          if (!empty($ver_comment['comment-id'])) {
            $assessment_verbatim_comments_id = $connection->merge('assessment_verbatim_comments')
              ->key(['assessment_id' => $assessment_id])
              ->key(['verbatim_id' => $ver_comment['comment-id']])
              ->fields(
                  [
                    'answer ' => $ver_comment['comment'],
                  ]
                )->execute();
          }
        }
      }
      drupal_set_message(t('Your form has been saved. You can come back and continue from here.'));

      // Redirect to home page 
      $url = Url::fromRoute('atlas_peer_invite.take_assessment_list');
      $form_state->setRedirectUrl($url);
      //$form_state->setRebuild(TRUE);
    }
  }

  /**
   * Custom function to get skill data in one role.
   */
  public function get_role_skills_data($nid) {
    $node = Node::load($nid);
    $node_type = $node->bundle();
    $role_skills = [];
    // Check for only assessment_form.
    if ($node->bundle() == 'assessment_form') {
      $role = $node->get('title')->getValue();
      $field_category = $node->get('field_category')->getValue();
      $field_verbatim_comments = $node->get('field_verbatim_comments')->getValue();

      foreach ($field_category as $category) {
        $category_id = $category['target_id'];
        $p = Paragraph::load($category['target_id']);
        $category_name = $p->field_new_category->getValue();
        $role_skills[$nid]['category'][$category_id]['name'] = $category_name[0]['value'];
        $skills_name = $p->field_skills->getValue();
        foreach ($skills_name as $skill) {
          $sp = Paragraph::load($skill['target_id']);
          $skills = $sp->field_skill->getValue();
          $field_number_of_levels = $sp->field_number_of_levels->getValue();
          $skill_level_informations = $sp->field_skill_level_information->getValue();
          $skill_id = $skill['target_id'];
          $role_skills[$nid]['category'][$category_id]['skill'][$skill_id]['name'] = $skills[0]['value'];
          foreach ($skill_level_informations as $skill_level) {
            $sl = Paragraph::load($skill_level['target_id']);
            $skill_level_id = $skill_level['target_id'];
            $field_level_header = $sl->field_level_header->getValue();
            $field_level_description = $sl->field_level_description->getValue();
            if (!empty($field_level_header[0]['value'])) {
              $role_skills[$nid]['category'][$category_id]['skill'][$skill_id]['level'][$skill_level_id]['label'] = $field_level_header[0]['value'];
              $role_skills[$nid]['category'][$category_id]['skill'][$skill_id]['level'][$skill_level_id]['description']['value'] = $field_level_description[0]['value'];
              $role_skills[$nid]['category'][$category_id]['skill'][$skill_id]['level'][$skill_level_id]['description']['format'] = $field_level_description[0]['format'];
            }
          }
        }
      }
      foreach ($field_verbatim_comments as $field_verbatim_comment) {
        $vc = Paragraph::load($field_verbatim_comment['target_id']);
        $comment_id = $field_verbatim_comment['target_id'];
        $comment = $vc->field_question->getValue();

        if (!empty($comment)) {
          $role_skills[$nid]['comment'][$comment_id] = $comment[0]['value'];
        }
      }
    }
    return $role_skills;
  }

}
