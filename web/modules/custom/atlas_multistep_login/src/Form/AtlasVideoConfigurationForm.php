<?php

namespace Drupal\atlas_multistep_login\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures forms module settings.
 */
class AtlasVideoConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'atlas_video_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'atlas_multistep_login.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('atlas_multistep_login.settings');
    $form['youtube_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Youtube URL'),
      '#default_value' => $config->get('youtube_url'),
      '#description' => 'For example. http://www.youtube.com/watch?v=0O2aH4XLbto',
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('atlas_multistep_login.settings')
      ->set('youtube_url', $values['youtube_url'])
      ->save();
    parent::submitForm($form, $form_state);
  }

}
