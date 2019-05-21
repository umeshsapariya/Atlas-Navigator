<?php

namespace Drupal\atlas_user_import\Form;

use Drupal\node\Entity\Node;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Class UserImporter.
 */
class UserImporterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_importer';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_url;
    $validators = ['file_validate_extensions' => ['csv']];
    $module_path = \Drupal::moduleHandler()->getModule('atlas_user_import')->getPath();
    $url = $base_url . '/' . $module_path . '/user_import_template.csv';

    $markup = new FormattableMarkup('You can Import users using template. <a href="@url">Download Template</a>', [
    // If $url is an Drupal\Core\Url object.
      '@url' => $url,
    ]);
    // To get allowed 360 Roles name.
    $query = \Drupal::entityQuery('node')
      ->condition('status', 1)
      ->condition('type', 'assessment_form');
    $nids = $query->execute();
    $allowed_roles = '<ul>';
    foreach ($nids as $nid) {
      $node = Node::load($nid);
      $title = $node->title->value;
      $allowed_roles .= '<li>' . $title . '</li>';
    }
    $allowed_roles .= '</ul>';

    // To get allowed Manager name.
    $ids = \Drupal::entityQuery('user')
      ->condition('status', 1)
      ->condition('roles', 'res')
      ->execute();
    $users = User::loadMultiple($ids);

    $allowed_managers = '<ul>';
    foreach ($users as $user) {
      $allowed_managers .= '<li>' . $user->getUsername() . '</li>';
    }
    $allowed_managers .= '</ul>';
    
    $form['template'] = [
      '#markup' => $markup,
    ];
    $form['upload_user'] = [
      '#type' => 'managed_file',
      '#name' => 'Upload User',
      '#title' => t('User Uploader CSV File'),
      '#size' => 20,
      '#upload_validators' => $validators,
      '#upload_location' => 'public://',
      '#required' => TRUE,
    ];
    $form['columns'] = [
      '#markup' => "<div> CSV file column order must be same as in template. It contain following columns</div>"
      . "<ul><li>Email (Required) : Must be valid email address</li><li>Password : Must be same as email Address </li><li>Username (Required) : Must be Unique</li>"
      . "<li>Role : A user role name if multiple roles are there then must be separated by comma (,). Allowed values are : <ul><li>Super Admin</li><li>Restricted Admin</li><li>Non Admin</li></ul></li>"
      . "<li>Is Manager (Required): Allowed values are :<ul><li>Yes</li><li>No</li>"
      . "</ul></li><li>First Name (Required)</li><li>Last Name (Required)</li><li>Preferred Name (Required)</li><li>Employee ID (Required)</li>"
      . "<li>Job Title (Required) : Allowed values are :" . $this->getAllowedterms('job_title') . "</li>"
      . "<li>360 Role (Optional) : Allowed values are :" . $allowed_roles . "</li>"
      . "<li>Manager (Optional) : Allowed values are :" . $allowed_managers . "</li>"
      . "<li>Department (Optional) : Allowed values are :" . $this->getAllowedterms('department') . "</li>"
      . "<li>Work Location (Optional) </li>"
      . "<li>Phone (Optional) </li>"
      . "<li>Birthdate (Optional): Format must be Y-m-d </li><li>Hire Date (Optional): Format must be Y-m-d </li></ul>",
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $file_id = $form_state->getValues()['upload_user'][0];
    // Set batch process.
    $batch = [
      'title' => t('Creating / Updating Users...'),
      'operations' => [
        [
          '\Drupal\atlas_user_import\UserImporter::createUser',
          [$file_id],
        ],
      ],
      'finished' => '\Drupal\atlas_user_import\UserImporter::createUserFinishedCallback',
    ];
    batch_set($batch);
  }

  /**
   * Custom funcion to get allowed terms.
   */
  public function getAllowedterms($vid) {
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
    $allowed_term = '<ul>';
    foreach ($terms as $term) {

      $allowed_term .= '<li>' . $term->name . '</li>';
    }
    $allowed_term .= '</ul>';
    return $allowed_term;
  }

}
