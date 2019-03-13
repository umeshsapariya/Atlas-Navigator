<?php

namespace Drupal\atlas_homepage\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Developing Plan Homepage' Block.
 *
 * @Block(
 *   id = "DevelopingPlanBlock",
 *   admin_label = @Translation("Developing plan Homepage"),
 *   category = @Translation("Developing Plan Homepage"),
 * )
 */
class DevelopingPlanBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#type' => 'markup',
      '#markup' => '',
    ];
  }

}
