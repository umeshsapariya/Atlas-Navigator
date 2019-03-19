<?php

namespace Drupal\atlas_homepage\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a '360 Results page' Block.
 *
 * @Block(
 *   id = "Results360Block",
 *   admin_label = @Translation("360 Results Homepage"),
 *   category = @Translation("360 Results Homepage"),
 * )
 */
class Results360Block extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\atlas_homepage\Form\CateoryFilterForm');
    return $form;
  }

}
