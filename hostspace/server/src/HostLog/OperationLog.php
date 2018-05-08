<?php
namespace Drupal\server\HostLog;

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
      case 'server_type':
        $message = strtr('%action服务器分类【%name】。', array(
          '%action' => $op,
          '%name' => $entity->label()
        ));
        break;
      case 'server':
        $cup_arr = array();
        $vals = $entity->get('cpu')->getValue();
        foreach($vals as $val) {
          if(!empty($val)) {
            $obj = entity_load('part_cpu', $val['target_id']);
            $arr = array();
            $arr[] = $obj->get('brand')->value;
            $arr[] = $obj->get('model')->value;
            $arr[] = $obj->get('standard')->value;
            $cup_arr[] = implode('、', $arr);
          }
        }
        $mb_arr = array();
        $mainboard = $entity->get('mainboard')->entity;
        $mb_arr[] = $mainboard->get('brand')->value;
        $mb_arr[] = $mainboard->get('model')->value;
        $mb_arr[] = $mainboard->get('standard')->value;
        $memory_arr = array();
        $vals = $entity->get('memory')->getValue();
        foreach($vals as $val) {
          if(!empty($val)) {
            $obj = entity_load('part_memory', $val['target_id']);
            $arr = array();
            $arr[] = $obj->get('brand')->value;
            $arr[] = $obj->get('model')->value;
            $arr[] = $obj->get('standard')->value;
            $memory_arr[] = implode('、', $arr);
          }
        }
        $hd_arr = array();
        $vals = $entity->get('harddisk')->getValue();
        foreach($vals as $val) {
          if(!empty($val)) {
            $obj = entity_load('part_harddisc', $val['target_id']);
            $arr = array();
            $arr[] = $obj->get('brand')->value;
            $arr[] = $obj->get('model')->value;
            $arr[] = $obj->get('standard')->value;
            $hd_arr[] = implode('、', $arr);
          }
        }
        $chassis_arr = array();
        $chassis = $entity->get('chassis')->entity;
        $chassis_arr[] = $chassis->get('brand')->value;
        $chassis_arr[] = $chassis->get('model')->value;
        $chassis_arr[] = $chassis->get('standard')->value;
        $message = strtr('%action添加了一台服务器【%name】(CPU:%cup 主板:%mb 内存:%momory 硬盘:%hk 机箱:%chassis)', array(
          '%action' => $op,
          '%name' => $entity->label(),
          '%cup' => implode(',', $cup_arr),
          '%mb' => implode('、', $mb_arr),
          '%momory' => implode(',', $memory_arr),
          '%hk' => implode(',', $hd_arr),
          '%chassis' => implode('、', $chassis_arr)
        ));
        break;
    }
    return $message;
  }
}
