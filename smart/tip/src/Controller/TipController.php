<?php

namespace Drupal\tip\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Utility\Tags;
use Drupal\Component\Utility\Unicode;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for sop routes.
 */
class TipController extends ControllerBase {

  /**
   * 自动返回可匹配的用户账号列表.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function handleUserAutocomplete(Request $request) {
    $matches = [];
    if ($input = $request->query->get('q')) {
      $typed_string = Tags::explode($input);
      $typed_string = Unicode::strtolower(array_pop($typed_string));
      $matches = \Drupal::service('tip.userservice')->getMatchClients($typed_string);
    }
    return new JsonResponse($matches);
  }

  /**
   * 获取当前用户的消息列表.
   *
   * @param int $pagesize
   *
   * @return string[]|array[]
   */
  public function ajaxGetPersonalMsgList($pageSize = 20) {
    $queryFactory = \Drupal::getContainer()->get('entity.query');
    $entity_query = $queryFactory->get('tip_msg');
    $entity_query->sort('created');
    $entity_query->condition('isreaded', 0);
    $entity_query->condition('uid', \Drupal::currentUser()->id());
    $ids = $entity_query->execute();
    $entities = entity_load_multiple('tip_msg', $ids);

    $build['msg_list'] = [
      '#theme' => 'msg_list',
      '#entities_msg' => $entities,
    ];
    return $build;
  }

  /**
   *
   */
  public function ajaxGetTipList($pageSize = 20) {
    $queryFactory = \Drupal::getContainer()->get('entity.query');
    $entity_query = $queryFactory->get('tip_msg');
    $entity_query->sort('created');
    $entity_query->condition('isreaded', 0);
    $ids = $entity_query->execute();
    $entities = entity_load_multiple('tip_msg', $ids);
    // @todo 这里需要进一步处理，获取子类型的所有实体信息。
    // @todo 暂时只获取msg的所有实体
    $build['msg_list'] = [
      '#theme' => 'msg_list',
      '#entities_msg' => $entities,
    ];
    return $build;
  }

}
