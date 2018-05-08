<?php
namespace Drupal\hc_alipay\HostLog;

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
    if ($action == 'payment') {
      $message = strtr('%user支付了订单。订单编码为：%code', array(
        '%user' => \Drupal::currentUser()->getUsername(),
        '%code' => $entity->getSimpleValue('code')
      ));
    }
    return $message;
  }
}
