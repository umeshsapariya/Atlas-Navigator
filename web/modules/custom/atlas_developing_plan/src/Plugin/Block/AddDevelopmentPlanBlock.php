<?php

namespace Drupal\atlas_developing_plan\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Component\Serialization\Json;

/**
 * Provides a 'Modal' Block.
 *
 * @Block(
 *   id = "development_plan_popup",
 *   admin_label = @Translation("Modal block"),
 * )
 */
class AddDevelopmentPlanBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    global $base_url;

    $link_url = Url::fromRoute('add-development-plan-form');
    // ksm($link_url);
    $link_url->setOptions([
      'attributes' => [
        'class' => ['use-ajax', 'button', 'button--small', 'btn-primary'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode(['width' => 800]),
      ],
    ]);

    // ksm($activity_node->title->value);.
    return [
      '#type' => 'markup',
      '#markup' => Link::fromTextAndUrl('Add Activity', $link_url)->toString(),
      '#attached' => ['library' => ['core/drupal.dialog.ajax']],
    ];
  }

}
