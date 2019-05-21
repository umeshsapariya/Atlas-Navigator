<?php

namespace Drupal\atlas_common\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a '360 Results page' Block.
 *
 * @Block(
 *   id = "SwitchViewFormBlock",
 *   admin_label = @Translation("Switch View Form Block"),
 *   category = @Translation("Switch View Form Block"),
 * )
 */
class SwitchViewFormBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\atlas_common\Form\SwitchViewForm');
    return $form;
  }

}
