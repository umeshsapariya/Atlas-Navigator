<?php

namespace Drupal\atlas_peer_invite\Controller;

use Drupal\node\Entity\Node;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Class DisplayTableController.
 *
 * @package Drupal\mydata\Controller
 */
class TakeAssessmentList extends ControllerBase {

  /**
   * Display.
   *
   * @return array
   *   Return Table element.
   */
  public function display() {
    $header_table = [
      'Role' => t('Role'),
      'operatoins' => t('Operations'),
    ];
    $query = \Drupal::database()->select('assessment_invite_details', 'aid');
    $query->fields('aid', ['hash']);
    $query->join('assessment_invite', 'ai', 'ai.invite_id = aid.invite_id');
    $query->fields('ai', ['role_id']);
    $query->condition('aid.raters_uid', \Drupal::currentUser()->id());
    $query->condition('aid.completed', 0);
    $results = $query->execute()->fetchAll();
    $rows = [];
    if ($results) {

      foreach ($results as $result) {
        $node = Node::load($result->role_id);
        if ($node->status->value) {
          $link = Link::fromTextAndUrl(t('Take Assessment'), Url::fromUri('internal:/assessment-form/' . $result->hash))->toString();

          $rows[] = [
            'Role' => $node->title->value,
            'operations' => $link,
          ];
        }
      }
    }
    $form['table'] = [
      '#type' => 'table',
      '#rows' => $rows,
      '#empty' => t('No Assessment Invitation found'),
      '#cache' => [
        'max-age' => 0,
      ],
    ];
    return $form;
  }

  /**
   * Display.
   *
   * @return array
   *   Return Begin assessment link markup.
   */
  public function begin_assessment($hash) {

    $link = Link::fromTextAndUrl(t('Begin Assessment'), Url::fromUri('internal:/assessment-form/' . $hash))->toString();
    return [
      '#type' => 'markup',
      '#markup' => '<div class = "begin-assessment-button">' . $link . '</div>',
    ];
  }

}
