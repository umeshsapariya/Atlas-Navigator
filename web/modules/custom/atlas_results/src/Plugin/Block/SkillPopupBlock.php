<?php

namespace Drupal\atlas_results\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Render\Markup;

/**
 * Provides a 'Modal' Block.
 *
 * @Block(
 *   id = "skill_popup",
 *   admin_label = @Translation("Modal block"),
 * )
 */
class SkillPopupBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build($skill_id = NULL) {
    global $base_url;

    $link_url = Url::fromRoute('atlas_result.skill_popup', ['skill_id' => $skill_id]);
    $link_url->setOptions([
      'attributes' => [
        'class' => ['use-ajax', 'button', 'button--small'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode(['width' => 400]),
      ],
    ]);
    $handler        = \Drupal::service('theme_handler');
    $path           = $handler->getTheme('yorkshire')->getPath();
    $theme_path     = $base_url . '/' . $path;
    $img            = '<img src="' . $theme_path . '/images/rubric_icon.png" />';
    $rendered_image = render($img);
    $image_markup   = Markup::create($rendered_image);

    return [
      '#type' => 'markup',
      '#markup' => Link::fromTextAndUrl($image_markup, $link_url)->toString(),
      '#attached' => ['library' => ['core/drupal.dialog.ajax']],
    ];
  }

}
