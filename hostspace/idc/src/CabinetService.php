<?php
/**
 * @file
 * 为idc_room实体提供服务操作
 *
 */

namespace Drupal\idc;

use Drupal\Core\Entity\EntityInterface;

class CabinetService {

  /**
   *修改机柜数量
   */
  public function updateRoomCabinetInfo(EntityInterface $entity, $op) {
    $room = $entity->getObject('rid');;
    $cabinet_number = $room->getSimpleValue('cabinet_number');
    if($op == 'add') {
        $room->set('cabinet_number', $cabinet_number + 1);
    } else if ($op == 'delete') {
       $room->set('cabinet_number', $cabinet_number - 1);
    }
    $image = $room->getObject('image');
    if(empty($image)) {
      $room->removeImage();
    }
    $room->save();
  }
}
