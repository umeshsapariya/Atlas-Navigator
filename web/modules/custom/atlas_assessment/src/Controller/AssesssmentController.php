<?php

namespace Drupal\atlas_assessment\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides route responses for the Example module.
 */
class AssesssmentController extends ControllerBase {

  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function thankYouPage() {
    $element = [
      '#markup' => 'THANK YOU FOR YOUR RESPONSE',
    ];
    return $element;
  }

}
