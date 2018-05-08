<?php
/**
 * @file
 * 为idc_room实体提供服务操作
 *
 */

namespace Drupal\idc;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Database\Connection;

class CabinetServerService {
  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   *修改机柜上机位的使用情况
   */
  public function updateCabinetSeatInfo(EntityInterface $entity, $op) {
    $cabinet = $entity->getObject('cabinet_id');
    $old_unused_seat = $cabinet->getSimpleValue('unused_seat');
    $old_used_seat = $cabinet->getSimpleValue('used_seat');
    $size = $entity->getSimpleValue('seat_size');
    if($op == 'add') {
      $cabinet->set('unused_seat', $old_unused_seat - $size);
      $cabinet->set('used_seat', $old_used_seat + $size);
    } else if ($op == 'delete') {
      $cabinet->set('unused_seat', $old_unused_seat + $size);
      $cabinet->set('used_seat', $old_used_seat - $size);
    } else if ($op == 'move') {
      $cabinet->set('unused_seat', $old_unused_seat - $size);
      $cabinet->set('used_seat', $old_used_seat + $size);

      $old_cabinet_id = $entity->move_cabinet_before;
      $old_cabinet = entity_load('room_cabinet', $old_cabinet_id);
      $before_unused = $old_cabinet->getSimpleValue('unused_seat');
      $before_used = $old_cabinet->getSimpleValue('used_seat');
      $old_cabinet->set('unused_seat', $before_unused + $size);
      $old_cabinet->set('used_seat', $before_used - $size);
      $old_cabinet->save();
    }
    $cabinet->save();
  }

  /**
   * 修改上柜状态，on：已上柜，off：未上柜
   */
  public function updateEquipmentStatus(EntityInterface $entity, $op) {
    $ipm = $entity->getObject('ipm_id');
    $server = $entity->getObject('server_id');
    if($op=='add') {
      $ipm->set('status_equipment', 'on');
      $server->set('status_equipment', 'on');
    } else if($op == 'delete') {
      $ipm->set('status_equipment', 'off');
      $ipm->set('port', null);
      $server->set('status_equipment', 'off');
    }
    $ipm->save();
    $server->save();
  }

  /**
   * 统计查询
   */
  public function getServerByCondition($condition) {
    $query = $this->database->select('idc_cabinet_server_field_data','t')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender');

    if(isset($condition['server_type']) || isset($condition['server_code'])) {
      $query->innerJoin('server_field_data','s', 't.server_id = s.sid');
    }
    if(isset($condition['manage_ip']) || isset($condition['status'])) {
      $query->innerJoin('management_ip_field_data','m', 't.ipm_id = m.id');
    }
    if(isset($condition['cabinet'])) {
      $query->innerJoin('idc_cabinet_field_data','c', 't.cabinet_id = c.cid');
    }
    $query->fields('t', array('sid'));
    $query->condition('t.ipm_id', 0, '>');
    if(isset($condition['server_type'])) {
      $query->condition('s.type', $condition['server_type']);
    }
    if(isset($condition['server_code'])) {
      $query->condition('s.server_code', $condition['server_code'] . '%', 'LIKE');
    }
    if(isset($condition['manage_ip'])) {
      $query->condition('m.ip', $condition['manage_ip'] . '%', 'LIKE');
    }
    if(isset($condition['status'])) {
      $query->condition('m.status', $condition['status']);
    }
    if(isset($condition['cabinet'])) {
      $query->condition('c.code', $condition['cabinet']);
    }
    $query->limit(PER_PAGE_COUNT);
    return $query->execute()->fetchCol();
  }
}

