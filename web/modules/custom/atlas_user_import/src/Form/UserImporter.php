<?php

namespace Drupal\atlas_user_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\user\Entity\User;
use Drupal\taxonomy\Entity\Term;
use Drupal\profile\Entity\Profile;
use Drupal\profile\Entity\ProfileType;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Component\Datetime\DateTimePlus;

use Drupal\Core\Datetime\Element\Datetime;
/**
 * Class UserImporter.
 */
class UserImporter extends FormBase {


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
    $file = File::load($file_id);

    // Get the uri from file object.
    $uri = $file->get('uri')->getString();

    $handle = fopen($uri, 'r');
    $headers = fgetcsv($handle);
    
    while ($row = fgetcsv($handle)) {
      $ids = \Drupal::entityQuery('user')
        ->condition('name', $row[2])
        ->range(0, 1)
        ->execute();
      $email = \Drupal::entityQuery('user')
        ->condition('mail', $row[0])
        ->range(0, 1)
        ->execute();
      if(empty($ids) && empty($email)){ 
        $firstname = '';
        $lastname = '';
        $empid = '';
        $phone = '';
        $preffered_name = '';
        $work_location = '';
        $job_tid = '';
        $role_id = '';
        $managerid = '';
        $dept_tid = '';
        $timezone = '';
        $birthdate = '';
        $hiredate = '';
        $user = User::create();
        foreach($row as $key => $val) {
          if($key == 0) {
            $user->setPassword($val);
            $user->enforceIsNew();
            $user->setEmail($val);
          }
          if($key == 2) {
            $user->setUsername($val);
          }
          if($key == 3) {
            $roles = explode(",",$val);
            $role_id = '';
            foreach ($roles as $role) {
                switch ($role) {
                case 'Super Admin':
                    $role_id = 'super_admin';
                    break;
                case 'Restricted Admin':
                    $role_id = 'res';
                    break;
                case 'Non Admin':
                    $role_id = 'non_admin';
            }
              $user->addRole($role_id);
            }
          }
          if($key == 4) {
            if ($val == 'Yes'){
              $user->set('field_is_manager', 1);
            } else {
              $user->set('field_is_manager', 0);
            }
          }
          if($key == 5) {
            $firstname = $val;
          }
          if($key == 6) {
            $lastname = $val;
          }
//          if($key == 7) {
//            $timezone = $val;
//          }
          if($key == 8) {
            $empid = $val;
          }
          // job title
          if($key == 9) {
            $term = \Drupal::entityTypeManager()
               ->getStorage('taxonomy_term')
                ->loadByProperties(['name' => $val]);
            if ($term) {
              $term_obj = reset($term);
              $job_tid =$term_obj->id();
            }
          }
          // 360 Roles 
          if($key == 10) {
            $nodes = \Drupal::entityTypeManager()
                ->getStorage('node')
                ->loadByProperties(['title' => $val]);
              foreach ( $nodes as $node ) {
                $role_id = $node->id();
              }
          }
          // Manager
          if($key == 11) {
            $users = \Drupal::entityTypeManager()->getStorage('user')
                ->loadByProperties(['name' => $val]);
            $manager = reset($users);
            if ($manager) {
              $managerid = $manager->id();
            }
          }

          if($key == 7) {
            $preffered_name = $val;
          }
          // department
          if($key == 12) {
            $term = \Drupal::entityTypeManager()
                ->getStorage('taxonomy_term')
                ->loadByProperties(['name' => $val]);
            if ($term) {
              $term_obj = reset($term);
              $dept_tid =$term_obj->id();
            }
          }
          if($key == 13) {
            $work_location = $val;
          }
          if($key == 14) {
            $phone = $val;
          }
          if($key == 15) {
            $birthdate_temp = str_replace('/', '-', $val);
            $birthdate = format_date(strtotime($birthdate_temp), 'custom', 'Y-m-d');
          }
          if($key == 16) {
            $hiredate_temp = str_replace('/', '-', $val);
            $hiredate = format_date(strtotime($hiredate_temp), 'custom', 'Y-m-d');
          }
          if($key == 17) {
            $role_start_date_temp = str_replace('/', '-', $val);
            $role_start_date = format_date(strtotime($role_start_date_temp), 'custom', 'Y-m-d');
          }
          
        }

        //Optional settings
        $language = 'en';
        $user->set("init", 'email');
        $user->activate();

          //Save user
        $user->save();

        $profile = Profile::create([
            'type' => 'general_profile',
            'uid' => $user->id(),
            'field_first_name' => $firstname,
            'field_last_name' => $lastname,
            'field_employee_id' => $empid,
            'field_phone' => $phone,
            'field_preferred_name' => $preffered_name,
            'field_work_location' => $work_location,
            'field_job_title' => $job_tid,
            'field_360_role' => $role_id,
            'field_manager' => $managerid,
            'field_department' => $dept_tid,
            'field_time_zone' => $timezone,
            'field_birthdate' => $birthdate,
            'field_hire_date' => $hiredate,
            'field_role_start_date' => $role_start_date,
            ]);

        $profile->setDefault(TRUE);
        $profile->save();
        drupal_set_message("User with uid " . $user->id() . " saved!\n");
      }else {
        drupal_set_message("Username ".$row[2]." already present");
      }
    }
  }
}
