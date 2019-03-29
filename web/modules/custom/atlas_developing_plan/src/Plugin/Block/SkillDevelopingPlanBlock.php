<?php

namespace Drupal\atlas_developing_plan\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'SkillDevelopingPlanBlock' block.
 *
 * @Block(
 *   id = "SkillDevelopingPlanBlock",
 *   admin_label = @Translation("Skill Developing block"),
 *   category = @Translation("Custom example block")
 * )
 */
class SkillDevelopingPlanBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $form = \Drupal::formBuilder()->getForm('Drupal\atlas_developing_plan\Form\SkillDevelopingPlan');

    return $form;
  }

}