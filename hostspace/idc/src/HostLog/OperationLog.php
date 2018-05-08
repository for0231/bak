<?php
namespace Drupal\idc\HostLog;

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
    $op = '操作';
    if($action == 'insert') {
      $op = '添加';
    } else if ($action == 'update') {
      $op = '编辑';
    } else if ($action == 'delete') {
      $op = '删除';
    }
    $type = $entity->getEntityTypeId();
    switch($type) {
      case 'room':
        $message = strtr('%action了机房【%name】。', array(
          '%action' => $op,
          '%name' => $entity->label()
        ));
        break;
      case 'room_cabinet':
        if($action == 'insert') {
          $message = strtr('在机房【%room】中增加机柜【%name】机位数【%seat】。', array(
            '%room' => $entity->getObject('rid')->label(),
            '%name' => $entity->label(),
            '%seat' => $entity->get('seat')->value
          ));
        } else {
          $message = strtr('%action机房【%room】里的机柜【%name】信息。', array(
            '%action' => $op,
            '%room' => $entity->getObject('rid')->label(),
            '%name' => $entity->label()
          ));
        }
        break;
      case 'cabinet_server':
        if($action == 'delete') {
          if($entity->getObjectId('ipm_id') == 0) {
            $message = strtr('从机柜【%cabinet】里移出服务器组【%group】。', array(
              '%group' => $entity->getSimpleValue('group_name'),
              '%cabinet' => $entity->getObject('cabinet_id')->label()
            ));
          } else {
            $message = strtr('从机柜【%cabinet】里移出服务器【%server】。', array(
              '%server' => $entity->getObject('server_id')->label(),
              '%cabinet' => $entity->getObject('cabinet_id')->label()
            ));
          }
        } else {
          if(isset($entity->move_seat_before)) {
            $cabinet_label = $entity->getObject('cabinet_id')->label();
            $seat_label = $entity->getSimpleValue('start_seat');
            $bseat_label = $entity->move_seat_before;
            $bcabinet_label = $entity->move_cabinet_label_before;
            $message = strtr('将服务器【%server】从机柜【%bcabinet】【#%bseat】移机到机柜【%cabinet】【#%seat】。', array(
              '%server' => $entity->getObject('server_id')->label(),
              '%bcabinet' => $bcabinet_label,
              '%bseat' => $bseat_label,
              '%cabinet' => $cabinet_label,
              '%seat' => $seat_label
            ));
          } else {
            $server = $entity->getObject('server_id');
            $message = strtr('%action服务器【%server】到机柜【%cabinet】【#%seat】里。服务器类型：%type', array(
              '%action' => $op,
              '%server' => $server->label(),
              '%cabinet' => $entity->getObject('cabinet_id')->label(),
              '%seat' => $entity->getSimpleValue('start_seat'),
              '%type' => $server->get('type')->entity->label()
            ));
          }
        }
        break;
      case 'cabinet_switch':
        if($action == 'delete') {
          $message = strtr('从机柜【%cabinet】里移出交换机【%switch】。', array(
            '%switch' => $entity->getObject('ips_id')->label(),
            '%cabinet' => $entity->getObject('cabinet_id')->label()
          ));
        } else {
          $message = strtr('%action交换机【%switch】到机柜【%cabinet】里。', array(
            '%action' => $op,
            '%switch' => $entity->getObject('ips_id')->label(),
            '%cabinet' => $entity->getObject('cabinet_id')->label()
          ));
        }
        break;
    }
    return $message;
  }

  /**
   * 字段差异比较
   */
  protected function diff($name, $current, $before, $type) {
    if($name == 'start_seat') {
      $current_label = $current->getSimpleValue('start_seat');
      $before_label = $before->getSimpleValue('start_seat');
      return '起始机位：【#'. $current_label .'】变更为【#'. $before_label .'】';
    }
    return null;
  }

}
