<?php

namespace Drupal\atlas_developing_plan\Plugin\Block;

use Drupal\node\Entity\Node;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Component\Serialization\Json;

/**
 * Provides a 'Modal' Block.
 *
 * @Block(
 *   id = "activity_popup",
 *   admin_label = @Translation("Modal block"),
 * )
 */
class ActivityPopupBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build($activity_id = NULL) {
    global $base_url;

    $link_url = Url::fromRoute('development-plan.activity_popup', ['activity_id' => $activity_id]);
    $link_url->setOptions([
      'attributes' => [
        'class' => ['use-ajax', 'button', 'button--small'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode(['width' => 400]),
      ],
    ]);
    $handler = \Drupal::service('theme_handler');
    $activity_node = Node::load($activity_id);
    $activity_title = $activity_node->title->value;
    // ksm($activity_node->title->value);.
    return [
      '#type' => 'markup',
      '#markup' => Link::fromTextAndUrl($activity_title, $link_url)->toString(),
      '#attached' => ['library' => ['core/drupal.dialog.ajax']],
    ];
  }

}
