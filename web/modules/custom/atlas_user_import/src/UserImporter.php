<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Drupal\atlas_user_import;

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
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountInterface;

class UserImporter {
  public static function createUser($file_id, &$context){
    $message = 'Creating Users...';
    $results = array();

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
              $user->addRole('res');
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
        $profile_array = [
            'type' => 'general_profile',
            'uid' => $user->id(),
            'field_first_name' => $firstname,
            'field_last_name' => $lastname,
            'field_employee_id' => $empid,
            'field_phone' => $phone,
            'field_preferred_name' => $preffered_name,
            'field_work_location' => $work_location,
            ];
        
            if (isset($job_tid) && $job_tid != '') {
              $profile_array['field_job_title'] = $job_tid;
            }
            if (isset($role_id) && $role_id != '') {
              $profile_array['field_360_role'] = $role_id;
            }
            if (isset($managerid) && $managerid != '') {
              $profile_array['field_manager'] = $managerid;
            }
            if (isset($dept_tid) && $dept_tid != '') {
              $profile_array['field_department'] = $dept_tid;
            }
            if (isset($timezone) && $timezone != '') {
              $profile_array['field_time_zone'] = $timezone;
            }
            if (isset($birthdate) && $birthdate != '') {
              $profile_array['field_birthdate'] = $birthdate;
            }
            if (isset($hiredate) && $hiredate != '') {
              $profile_array['field_hire_date'] = $hiredate;
            }
            if (isset($role_start_date) && $role_start_date != '') {
              $profile_array['field_role_start_date'] = $role_start_date;
            }
            

        $profile = Profile::create($profile_array);

        $profile->setDefault(TRUE);
        $profile->save();
        $results[] = $user->id();
        drupal_set_message("User with uid " . $user->id() . " saved!\n");
      }else if (!empty($ids) && !empty($email)){
         $existingUserID = array_keys($ids)[0];

         // load user object
        $existingUser = user_load($existingUserID);
        // update some user property
        $activeProfile = \Drupal::getContainer()
          ->get('entity_type.manager')
          ->getStorage('profile')
          ->loadByUser(User::load($existingUserID), 'general_profile');
      
        foreach($row as $key => $val) {
          if($key == 3) {
            $existing_roles = $existingUser ->getRoles();
            // Remove previosuly present roles
            foreach ($existing_roles as $existing_role) {
                $existingUser->removeRole($existing_role);
            }
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
              $existingUser->addRole($role_id);
            }
          }
          if($key == 4) {
            if ($val == 'Yes'){
              $existingUser->field_is_manager = 1;
              $existingUser->addRole('res');
            } else {
              $existingUser->field_is_manager = 0;
              
            }
          }
          if($key == 5) {
           $activeProfile->field_first_name->value = $val;
          }
          if($key == 6) {
            $activeProfile->field_last_name->value = $val;
          }
          if($key == 8) {
            $activeProfile->field_employee_id->value = $val;
          }
          // job title
          if($key == 9) {
            $term = \Drupal::entityTypeManager()
               ->getStorage('taxonomy_term')
                ->loadByProperties(['name' => $val]);
            if ($term) {
              $term_obj = reset($term);
              $job_tid = $term_obj->id();
            }
            $activeProfile->field_job_title->target_id = $job_tid;
          }
          // 360 Roles 
          if($key == 10) {
            $nodes = \Drupal::entityTypeManager()
                ->getStorage('node')
                ->loadByProperties(['title' => $val]);
              foreach ( $nodes as $node ) {
                $role_id = $node->id();
              }
              $activeProfile->field_360_role->target_id = $role_id;
          }
          // Manager
          if($key == 11) {
            $users = \Drupal::entityTypeManager()->getStorage('user')
                ->loadByProperties(['name' => $val]);
            $manager = reset($users);
            if ($manager) {
              $managerid = $manager->id();
            }
            $activeProfile->field_manager->target_id = $managerid;
          }

          if($key == 7) {
            $preffered_name = $val;
            $activeProfile->field_preferred_name->value = $val;

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
            $activeProfile->field_department->target_id = $dept_tid;
          }
          if($key == 13) {
            $activeProfile->field_work_location->value = $val;
          }
          if($key == 14) {
            $activeProfile->field_phone->value = $val;
          }

//          if($key == 15) {
//            $birthdate_temp = str_replace('/', '-', $val);
//            $birthdate = format_date(strtotime($birthdate_temp), 'custom', 'Y-m-d');
//          }
//          if($key == 16) {
//            $hiredate_temp = str_replace('/', '-', $val);
//            $hiredate = format_date(strtotime($hiredate_temp), 'custom', 'Y-m-d');
//          }
//          if($key == 17) {
//            $role_start_date_temp = str_replace('/', '-', $val);
//            $role_start_date = format_date(strtotime($role_start_date_temp), 'custom', 'Y-m-d');
//          }
         
        }
         $existingUser->save();
         $activeProfile->save();
         $results[] = $existingUserID;
         drupal_set_message("User with uid ".$existingUserID." is updasted");
        
      }
    }
  
    
    $context['message'] = $message;
    $context['results'] = $results;
  }
  function createUserFinishedCallback($success, $results, $operations) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      $message = \Drupal::translation()->formatPlural(
        count($results),
        'One post processed.', '@count users created/updated.'
      );
    }
    else {
      $message = t('Finished with an error.');
    }
    drupal_set_message($message);
  }
}