<?php

/**
 * @file
 * Contains Drupal\atlas_multistep_login\Form\AtlasMultiStepForm.
 */

namespace Drupal\atlas_multistep_login\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\Session\Session;

class AtlasMultiStepForm extends ConfigFormBase {

	protected $step = 1;

	/**
	 *
	  {
	  @inheritdoc
	  }
	 */
	protected function getEditableConfigNames() {
		
	}

	/**
	 *
	  {
	  @inheritdoc
	  }
	 */
	public function getFormID() {
		return 'atlas_multi_step_form';
	}

	/**
	 *
	  {
	  @inheritdoc
	  }
	 */
	public function buildForm(array $form, FormStateInterface $form_state) {
		global $base_url;
		$form = parent::buildForm($form, $form_state);
		if ($this->step == 1) {
			$form['name'] = [
				'#type' => 'textfield',
				'#title' => $this->t('Email or Username'),
				'#required' => TRUE,
			];
		}
		if ($this->step == 2) {
			$value = $form_state->getValue([]);
			if ($value['user_exist'] == 'exist') {
				$form = \Drupal::formBuilder()->getForm('Drupal\user\Form\UserLoginForm');
				//$form['name']['#access'] = FALSE;
        $form['name']['#value'] = $value['name'];
				$form['my_captcha_element'] = array(
					'#type' => 'captcha',
					'#captcha_type' => 'image_captcha/Image',
					'#weight' => '98'
				);
        $form['persistent_login']['#description'] = t('<a href="@password">Reset Password</a>', array('@password' => $base_url . '/user/password'));
			}
		}
		if ($this->step < 2) {
			$button_label = $this->t('Next');
		}
		else {
			$button_label = $this->t('Login');
		}
		$form['actions']['submit']['#value'] = $button_label;
		$form['#theme'] = 'multistep_login_form';
		return $form;
	}

	/**
	 *
	  {
	  @inheritdoc
	  }
	 */
	public function validateForm(array &$form, FormStateInterface $form_state) {
		if ($this->step == 1) {
			$user_value = $form_state->getValue([]);
			$query = \Drupal::database()->select('users_field_data', 'u');
			$query->fields('u', ['name']);
			$query->fields('u', ['mail']);
			$or = $query->orConditionGroup()
					->condition('u.name', $user_value['name'])->condition('u.mail', $user_value['name']);
			$results = $query->condition($or)->execute()->fetchAll();
			if (!$results) {
				$user_exist = 'notexist';
				$form_state->setErrorByName('name', t('Email or Username does not exist.'));
			}
			else {
				$user_exist = 'exist';
			}
			$set_result = $form_state->setValue('user_exist', $user_exist);
			return $set_result;
		}
		if ($this->step == 2) {
			$input = &$form_state->getUserInput();
			$name = $input['name'];
			$password = $input['pass'];
			if (strpos($name, "@") == true) {
				$query = \Drupal::database()->select('users_field_data', 'u');
				$query->fields('u', ['mail']);
				$query->condition('u.mail', $name, '=');
				$results = $query->execute()->fetchAssoc();
				if (!$results || !isset($results['mail'])) {
					$form_state->setErrorByName('name', t('The email address provided could not be found'));
				}
				else {
					$name = $results['mail'];
					$users = \Drupal::entityTypeManager()->getStorage('user')
						->loadByProperties(['mail' => $name]);
					$user = reset($users);
					$username = $user->getUsername();
					if ($user) {
						$uid = $user->id();
						$uid = \Drupal::service('user.auth')->authenticate($username, $password);
					}
				}
			}
			else {
				$query = \Drupal::database()->select('users_field_data', 'u');
				$query->fields('u', ['name']);
				$query->condition('u.name', $name, '=');
				$results = $query->execute()->fetchAssoc();
				if (!$results || !isset($results['name'])) {
					$form_state->setErrorByName('name', t('The username provided could not be found'));
				}
				else {
					$name = $results['name'];
					$uid = \Drupal::service('user.auth')->authenticate($name, $password);
				}
			}
			$user = \Drupal\user\Entity\User::load($uid);
			if (!isset($user)) {
				$form_state->setErrorByName('pass', t('Wrong password. Try again or click Reset password to reset it.'));
			}
		}
	}

	/**
	 *
	  {
	  @inheritdoc
	  }
	 */
	public function submitForm(array &$form, FormStateInterface $form_state) {
		if ($this->step < 2) {
			$form_state->setRebuild();
			$this->step++;
		}
		$button_clicked = $form_state->getTriggeringElement()['#value'];
		if ($this->step == 2 && $button_clicked == 'Login') {
			$input = &$form_state->getUserInput();
			$name = $input['name'];
			$password = $input['pass'];
			if (strpos($name, "@") == true) {
				$users = \Drupal::entityTypeManager()->getStorage('user')
					->loadByProperties(['mail' => $name]);
				$user = reset($users);
				$name = $user->getUsername();
			}

			$uid = \Drupal::service('user.auth')->authenticate($name, $password);
			$user = \Drupal\user\Entity\User::load($uid);
			if (isset($user)) {
				$form_state->setRedirect('<front>');
				user_login_finalize($user);
			}
			else {
				$form_state->setErrorByName('pass', t('Wrong password. Try again or click Reset password to reset it.'));
			}
		}
	}
}
