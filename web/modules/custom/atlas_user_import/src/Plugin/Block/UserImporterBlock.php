<?php

namespace Drupal\atlas_user_import\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'UserImporterBlock' block.
 *
 * @Block(
 *   id = "UserImporterBlock",
 *   admin_label = @Translation("User Importer block"),
 *   category = @Translation("User Importer block")
 * )
 */
class UserImporterBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $form = \Drupal::formBuilder()->getForm('Drupal\atlas_user_import\Form\UserImporterForm');
    unset($form['columns']);
    return $form;
  }

}
