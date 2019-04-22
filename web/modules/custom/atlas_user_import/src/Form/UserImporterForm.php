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

    $validators = ['file_validate_extensions' => ['csv']];

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
    
    
    
}
