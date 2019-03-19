<?php

/**
 * @file
 * Contains \Drupal\atlas_multistep_login\Plugin\Block\MultiStepLogin.
 */

namespace Drupal\atlas_multistep_login\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormInterface;

/**
 * Provides a 'atlas_multistep_login' block.
 *
 * @Block(
 *   id = "atlas_multistep_login_block",
 *   admin_label = @Translation("Multi Step Login block"),
 *   category = @Translation("Custom")
 * )
 */
class MultiStepLogin extends BlockBase {

	/**
	 * {@inheritdoc}
	 */
	public function build() {

		$form = \Drupal::formBuilder()->getForm('Drupal\atlas_multistep_login\Form\AtlasMultiStepForm');

		return $form;
	}

}
