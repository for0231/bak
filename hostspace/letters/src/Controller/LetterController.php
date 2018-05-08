<?php

/**
 * @file
 * Contains \Drupal\letters\Controller\LetterController.
 */

namespace Drupal\letters\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for member routes.
 */
class LetterController extends ControllerBase {
  /**
   * 设置信件为已读
   *
   * @param $letter_id
   *   信件编号
   *
   */
  public function setLetterRead(Request $request) {
    $id = $request->request->get('letter_id');
    \Drupal::service('letters.letterservice')->setLetterHasReaded($id);
    $count =  count(\Drupal::service('letters.letterservice')->getNotReadCountByUid(\Drupal::currentUser()->id()));
    return $count;
  }

}
