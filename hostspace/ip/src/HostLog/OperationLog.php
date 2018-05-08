<?php
namespace Drupal\ip\HostLog;

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
    $op = 'IP';
    if($action == 'insert') {
      $op = '添加';
    } else if ($action == 'update') {
      $op = '编辑';
    } else if ($action == 'delete') {
      $op = '删除';
    }
    // 实体类型
    $type = $entity->getEntityTypeId();   
    switch($type) {
      case 'ipb':
        if($action == 'insert') {
          $message = strtr('业务IP入库。IP：%ip, 状态：%status, 类型：%ip_type, 专用用户：%puser, 机房：%room', array(
            '%action' => $op,
            '%ip' => $entity->label(),
            '%status' => ipbStatus()[$entity->get('status')->value],
            '%ip_type' => $entity->get('type')->entity->label() ,
            '%puser' => $entity->get('puid')->entity ? $entity->get('puid')->entity->getUsername(): '无',
            '%room' => entity_load('room', $entity->get('rid')->value)->label()
          ));
        } else if($action == 'apply') {
          $other_data = $entity->other_data['data'];
          $defense = taxonomy_term_load($other_data['defense'])->label();
          $type = taxonomy_term_load($other_data['type'])->label();
          $room = entity_load('room', $other_data['rid'])->label(); 
          $message = strtr('申请业务IP入库。IP:%ip, 防御: %defense, 类型:%type, 机房:%room', array(
            '%ip' => $other_data['segment'] . '.'. $other_data['begin'] . '-' . $other_data['end'],
            '%defense' => $defense,
            '%type' => $type,
            '%room' => $room
          ));
        } else if ($action == 'audit') {
          $other_data = $entity->other_data['data'];
          $message = strtr('拒绝业务IP入库申请。IP:%ip', array(
            '%ip' => $other_data['segment'] . '.'. $other_data['begin'] . '-' . $other_data['end']
          ));
        } else {
          $message = strtr('%action业务IP。IP：%ip, 类型：%ip_type, 状态：%status, 机房：%room', array(
            '%action' => $op,
            '%ip' => $entity->label(),
            '%ip_type' => $entity->get('type')->entity->label(),
            '%status' => ipbStatus()[$entity->get('status')->value],
            '%room' => entity_load('room', $entity->get('rid')->value)->label()
          ));
        }
      break;
      case 'ipm':
        if($action == 'insert') {          
          $message = strtr('%action管理IP。IP：%ip， 状态：%status, 类型：%server_type ', array(
            '%action' => $op,
            '%ip' => $entity->label(),
            '%status' => ipmStatus()[$entity->get('status')->value],
            '%server_type' => ip_server_type()[$entity->get('server_type')->value],
          ));
        } else {
          $message = strtr('%action了管理IP：%ip 的信息。', array(
            '%action' => $op,
            '%ip' => $entity->label(),
          ));
        }
      break;     
      case 'ips':
        $message = strtr('%action了交换机IP。IP：%ip', array(
          '%action' => $op,
          '%ip' => $entity->label()
        ));
      break;
    }
 
    return $message;
  }

  /**
   * 字段差异比较
   */
  protected function diff($name, $current, $before, $type) {
    $current_arr = $current->other_data['data'];
    $before_arr = $before->other_data['data'];
    if($name == 'status') {
      return  'IP状态：【' .ipbStatus()[$before->get('status')->value] .'】 变更为 【' . ipbStatus()[$current->get('status')->value] . '】';
    }
    if($name == 'type') {
      return  'IP类型：【' .$before->get('type')->entity->label() .'】 变更为 【' . $current->get('type')->entity->label() . '】';
    }
    if($name == 'server_type') {
      return  '服务器类型：【' .ip_server_type()[$before->get('server_type')->value] .'】 变更为 【' . ip_server_type()[$current->get('server_type')->value] . '】';
    }    
    return null;
  }

  /**
   * 获取label
   */
  protected function getLabel($name) {
    if($name == 'corporate_name') {
       return '公司/个人名称';
    }
    if($name == 'department') {
       return '部门';
    } 
    if($name == 'position') {
       return '职位';
    } 
 
    return parent::getLabel($name);
  }
} 
