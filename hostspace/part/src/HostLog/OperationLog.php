<?php
namespace Drupal\part\HostLog;

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
    $op = '配件';
    if($action == 'insert') {
      $op = '添加';
    } else if ($action == 'update') {
      $op = '编辑';
    } else if ($action == 'delete') {
      $op = '删除';
    }
    $type = $entity->getEntityTypeId();
    switch($type) {
      case 'part_cpu':
        $message = strtr('%actionCPU，品牌：%brand 规格：%standard，型号：%model', array(
          '%action' => $op,
          '%brand' => $entity->get('brand')->value,
          '%standard' => $entity->get('standard')->value,
          '%model' => $entity->get('model')->value,
        ));
        break;
      case 'part_mainboard':
        $message = strtr('%action主板，品牌：%brand 规格：%standard，型号：%model', array(
          '%action' => $op,
          '%brand' => $entity->get('brand')->value,
          '%standard' => $entity->get('standard')->value,
          '%model' => $entity->get('model')->value,
        ));
        break;
      case 'part_harddisc':
        $message = strtr('%action硬盘，品牌：%brand 规格：%standard，型号：%model', array(
          '%action' => $op,
          '%brand' => $entity->get('brand')->value,
          '%standard' => $entity->get('standard')->value,
          '%model' => $entity->get('model')->value,
        ));
        break;
      case 'part_memory':
        $message = strtr('%action内存，品牌：%brand 规格：%standard，型号：%model', array(
          '%action' => $op,
          '%brand' => $entity->get('brand')->value,
          '%standard' => $entity->get('standard')->value,
          '%model' => $entity->get('model')->value,
        ));
        break;
      case 'part_chassis':
        $message = strtr('%action机箱，品牌：%brand 规格：%standard，型号：%model', array(
          '%action' => $op,
          '%brand' => $entity->get('brand')->value,
          '%standard' => $entity->get('standard')->value,
          '%model' => $entity->get('model')->value,
        ));
        break;
      case 'part_raid':
        $message = strtr('%actionRaid，品牌：%brand 规格：%standard，型号：%model', array(
          '%action' => $op,
          '%brand' => $entity->get('brand')->value,
          '%standard' => $entity->get('standard')->value,
          '%model' => $entity->get('model')->value,
        ));
        break;
      case 'part_network':
        $message = strtr('%action网卡，品牌：%brand 规格：%standard，型号：%model', array(
          '%action' => $op,
          '%brand' => $entity->get('brand')->value,
          '%standard' => $entity->get('standard')->value,
          '%model' => $entity->get('model')->value,
        ));
        break;
      case 'part_optical':
        $message = strtr('%action光模块，品牌：%brand 规格：%standard，型号：%model', array(
          '%action' => $op,
          '%brand' => $entity->get('brand')->value,
          '%standard' => $entity->get('standard')->value,
          '%model' => $entity->get('model')->value,
        ));
        break;
      case 'part_switch':
        $message = strtr('%action交换机，品牌：%brand 规格：%standard，型号：%model', array(
          '%action' => $op,
          '%brand' => $entity->get('brand')->value,
          '%standard' => $entity->get('standard')->value,
          '%model' => $entity->get('model')->value,
        ));
        break;
    }
    return $message;
  }
} 
