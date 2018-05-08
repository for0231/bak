<?php
/**
 * @file
 * 为idc_room实体提供服务操作
 *
 */

namespace Drupal\idc;

use Drupal\Core\Entity\EntityInterface;

class CabinetSwitchService {

  /**
   *修改机柜上机位的使用情况
   */
  public function updateCabinetSeatInfo(EntityInterface $entity, $op) {
    $cabinet = $entity->getObject('cabinet_id');
    $old_unused_seat = $cabinet->getSimpleValue('unused_seat');
    $old_used_seat = $cabinet->getSimpleValue('used_seat');
    $old_switch_seat = $cabinet->getSimpleValue('switch_seat');
    $size = $entity->getSimpleValue('seat_size');
    if($op == 'add') {
      $cabinet->set('unused_seat', $old_unused_seat - $size);
      $cabinet->set('used_seat', $old_used_seat + $size);
      $cabinet->set('switch_seat', $old_switch_seat + $size);
    } else if ($op == 'delete') {
      $cabinet->set('unused_seat', $old_unused_seat + $size);
      $cabinet->set('used_seat', $old_used_seat - $size);
      $cabinet->set('switch_seat', $old_switch_seat - $size);
    }
    $cabinet->save();
  }

  /**
   * 修改上柜状态，on：已上柜，off：未上柜
   */
  public function updateEquipmentStatus(EntityInterface $entity, $op) {
    $ips = $entity->getObject('ips_id');
    if($op=='add') {
      $ips->set('status_equipment', 'on');
    } else if($op == 'delete') {
      $ips->set('status_equipment', 'off');
    }
    $ips->save();
  }
}
