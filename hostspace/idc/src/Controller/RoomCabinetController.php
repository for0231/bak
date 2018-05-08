<?php

/**
 * @file
 * Contains \Drupal\idc\Controller\RoomCabinetController.
 */

namespace Drupal\idc\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;

class RoomCabinetController extends ControllerBase {

  /**
   * Display cabinet list
   */
  public function viewCabinet(EntityInterface $room) {
    $build = array();
    $build['room'] = array(
      '#type' => 'label',
      '#title' => t('Room：@room', array(
        '@room' => $room->label()
      )),
      '#title_display' => 'option'
    );
    $build['cabinet'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('container-inline'),
      ),
      '#attached' => array(
        'library' => array('idc/drupal.cabinet-item')
      )
    );
    $cabinets = entity_load_multiple_by_properties('room_cabinet', array('rid' => $room->id()));
    foreach($cabinets as $key=>$cabinet) {
      $build['cabinet'][$key] = array(
        '#theme' => 'cabinet_item',
        '#cabinet' => $cabinet
      );
    }
    return $build;
  }


  public function viewSeat(EntityInterface $room_cabinet) {
    $build['list'] = array(
      '#theme' => 'seat_list',
      '#cabinet' => $room_cabinet
    );
    return $build;
  }

  /**
   * 显示机柜服务器详细
   */
  public function CabinetServerDetail($cabinet_server) {
    $entity = entity_load('cabinet_server', $cabinet_server);
    if(empty($entity)) {
       return array('#markup' => '机柜服务器不存在');
    }
    $build['info'] = array(
      '#theme' => 'cabinet_server_detail',
      '#cabinet_server' => $entity,
      '#attached' => array(
        'library' => array('idc/drupal.cabinet-server-detail')
      )
    );
    return $build;
  }
}
