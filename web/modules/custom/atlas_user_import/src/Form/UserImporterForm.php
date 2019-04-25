<?php

namespace Drupal\atlas_user_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\user\Entity\User;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\UserInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityInterface;
use Drupal\profile\Entity\Profile;
use Drupal\profile\Entity\ProfileType;
use Drupal\field\FieldConfigInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Url;

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
    $url = $base_url.'/'.$module_path.'/user_import_template.csv';
    
    $markup = new FormattableMarkup('You can Import users using template. <a href="@url">Download Template</a>', [
          '@url' => $url, // If $url is an Drupal\Core\Url object.
        ]);
    // To get allowed 360 Roles name.
    $query = \Drupal::entityQuery('node')
        ->condition('status', 1) //published or not
        ->condition('type', 'assessment_form'); //content type
    $nids = $query->execute();
    $allowed_roles = '<ul>';
    foreach ($nids as $nid) {
      $node = \Drupal\node\Entity\Node::load($nid); 
      $title = $node->title->value;
      $allowed_roles .= '<li>'.$title.'</li>';
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
      $allowed_managers .= '<li>'.$user->getUsername().'</li>';
    }
    $allowed_managers .= '</ul>';

    $form['template'] = [
        '#markup' => $markup];
    $form['columns'] = [
        '#markup' => "<div> CSV file column order must be same as in template. It contain following columns</div>"
        . "<ul><li>Email (Required) : Must be valid email address</li><li>Password : Must be same as email Address </li><li>Username (Required) : Must be Unique</li>"
        . "<li>Role : A user role name if multiple roles are there then must be separated by comma (,). Allowed values are : <ul><li>Super Admin</li><li>Restricted Admin</li><li>Non Admin</li></ul></li>"
        . "<li>Is Manager (Required): Allowed values are :<ul><li>YES</li><li>NO</li>"
        . "</ul></li><li>First Name (Required)</li><li>Last Name (Required)</li><li>Preferred Name (Required)</li><li>Employee ID (Required)</li>"
        . "<li>Job Title (Required) : Allowed values are :".$this->getAllowedterms('job_title')."</li>"
        . "<li>360 Role (Optional) : Allowed values are :".$allowed_roles."</li>"
        . "<li>Manager (Optional) : Allowed values are :".$allowed_managers."</li>"
        . "<li>Department (Optional) : Allowed values are :".$this->getAllowedterms('department')."</li>"
        . "<li>Work Location (Optional) </li>"
        . "<li>Phone (Optional) </li>"
        . "<li>Birthdate (Optional) </li><li>Hire Date (Optional) </li></ul>"
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
    // Display result.
    foreach ($form_state->getValues() as $key => $value) {
    //  drupal_set_message($key . ': ' . $value);
    }
    $file_id = $form_state->getValues()['upload_user'][0];
    // Set batch process
        $batch = array(
      'title' => t('Creating / Updating Users...'),
      'operations' => array(
        array(
          '\Drupal\atlas_user_import\UserImporter::createUser',
          array($file_id)
        ),
      ),
      'finished' => '\Drupal\atlas_user_import\UserImporter::createUserFinishedCallback',
    );
    batch_set($batch);
  }
    
  public function getAllowedterms($vid) {
    $terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
    $allowed_term = '<ul>';
    foreach ($terms as $term) {
    
       $allowed_term .= '<li>'.$term->name.'</li>';
    }
      $allowed_term .= '</ul>';
      return $allowed_term;
  }  
    
}
