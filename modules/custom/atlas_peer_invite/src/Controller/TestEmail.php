<?php

namespace Drupal\atlas_peer_invite\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class TestEmail.
 *
 * @package Drupal\atlas_peer_invite\Controller
 */
class TestEmail extends ControllerBase {

  /**
   * Display.
   *
   * @return string
   */
  public function SendMail() {
    $mailManager = \Drupal::service('plugin.manager.mail');
    $module = "atlas_peer_invite";
    $key = "send_assessment_link";
    // $to = "nareshb.iksula@gmail.com";.
    $to = "hatkarabhijeet@gmail.com";
    $params['message'] = 'Hi Naresh, <br> <p>This is test mail with link tag <a href="www.google.com">google</a><p>';
    $params['sender_username'] = "testing";
    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $send = TRUE;
    $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
    if ($result['result'] !== TRUE) {
      drupal_set_message(t('There was a problem sending your message and it was not sent.'), 'error');
    }
    else {
      drupal_set_message(t('Your message has been sent.'));
    }
    $element = [
      '#markup' => 'test email',
    ];
    return $element;
  }

}
