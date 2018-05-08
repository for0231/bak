<?php
namespace Drupal\published_information\HostLog;

use Drupal\hostlog\OperationLogBase;

/**
 * 操作日志
 */
class OperationLog extends OperationLogBase {
  /**
   * 构建日志消息
   * @param
   *  - $entity 当前操作实体
   *  - $action 当前操作（如insert, update, delete等）
   */
  protected function message($entity, $action) {
    $message = '';
    $type = '';
    if($entity->get('type')->target_id == 'news') {
      $type = '闻新';
    } else if ($entity->get('type')->target_id == 'articles') {
      $type = '文章';
    }
    if(empty($type)) {
      return $message;
    }
    if($action == 'insert') {
      $message = strtr('%user发布了%type，标题【%title】', array(
        '%user' => \Drupal::currentUser()->getUsername(),
        '%type' => $type,
        '%title' => $entity->getTitle()
      ));
    } else if ($action == 'update') {
      $message = strtr('%user编辑了%type，标题【%title】', array(
        '%user' => \Drupal::currentUser()->getUsername(),
        '%type' => $type,
        '%title' => $entity->getTitle()
      ));
    } else if ($action == 'delete') {
      $message = strtr('%user删除了%type，标题【%title】', array(
        '%user' => \Drupal::currentUser()->getUsername(),
        '%type' => $type,
        '%title' => $entity->getTitle()
      ));
    }
    return $message;
  }
}
