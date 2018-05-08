<?php

namespace Drupal\worksheet;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;

class WorkSheetTypeService {
  
  protected $types = array();
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
   *  160~169:定义为机房事务
   *  200~299:定义为P类工单
   */
  public function DefaultTypes() {
    return array(
      100 => array('class_name' => 'I1', 'operation_name'=> '紧急服务器上架', 'complete_time'=> 90, 'workload'=> 1),
      110 => array('class_name' => 'I2', 'operation_name'=> '服务器上架', 'complete_time'=> 120, 'workload'=> 1),
      111 => array('class_name' => 'I2', 'operation_name'=> '服务器待上架', 'complete_time'=> 0, 'workload'=> 0),
      120 => array('class_name' => 'I3', 'operation_name'=> '服务器重装', 'complete_time'=> 720, 'workload'=> 0.67),
      130 => array('class_name' => 'I4', 'operation_name'=> '添加IP', 'complete_time'=> 30, 'workload'=> 0.1),
      131 => array('class_name' => 'I4', 'operation_name'=> '停用IP', 'complete_time'=> 30, 'workload'=> 0.1),
      132 => array('class_name' => 'I4', 'operation_name'=> '更换IP', 'complete_time'=> 30, 'workload'=> 0.1),
      133 => array('class_name' => 'I4', 'operation_name'=> '带宽变更', 'complete_time'=> 30, 'workload'=> 0.1),
      140 => array('class_name' => 'I5', 'operation_name'=> '服务器下架', 'complete_time'=> 720, 'workload'=> 0.33),
      150 => array('class_name' => 'I6', 'operation_name'=> 'UP端口', 'complete_time'=> 30, 'workload'=> 0),
      151 => array('class_name' => 'I6', 'operation_name'=> 'DOWN端口', 'complete_time'=> 30, 'workload'=> 0),
      160 => array('class_name' => 'I7', 'operation_name' => '机房事务', 'complete_time' => 0, 'workload'=> 0),
      161 => array('class_name' => 'I8', 'operation_name' => '机房事务', 'complete_time' => 0, 'workload'=> 0),
      200 => array('class_name' => 'P1', 'operation_name'=> '全网业务中断', 'complete_time'=> 0, 'workload'=> 0),
      210 => array('class_name' => 'P2', 'operation_name'=> '多台设备业务中断', 'complete_time'=> 0, 'workload'=> 0),
      220 => array('class_name' => 'P3', 'operation_name'=> '多台设备业务受影响', 'complete_time'=> 0, 'workload'=> 0),
      230 => array('class_name' => 'P4', 'operation_name'=> '单台服务器，业务中断', 'complete_time'=> 0, 'workload'=> 0),
      240 => array('class_name' => 'P5', 'operation_name'=> '单台服务器，业务受影响', 'complete_time'=> 0, 'workload'=> 0),
      250 => array('class_name' => 'P6', 'operation_name'=> '单台服务器，业务正常，其他问题', 'complete_time'=> 0, 'workload'=> 0),
      260 => array('class_name' => 'P7', 'operation_name'=> 'NOC任务', 'complete_time'=> 0, 'workload'=> 0),
      300 => array('class_name' => 'E类其他', 'operation_name'=> '非故障类售后事务', 'complete_time'=> 0, 'workload'=> 0),
      400 => array('class_name' => 'I9', 'operation_name'=> '周期性工单', 'complete_time'=> 0, 'workload'=> 0),
      410 => array('class_name' => 'P9', 'operation_name'=> '周期性工单', 'complete_time'=> 0, 'workload'=> 0),
      500 => array('class_name' => 'I10', 'operation_name'=> '物流工单', 'complete_time'=> 0, 'workload'=> 0)
    );
  }
  
  /**
   * 获取类型上下架类型
   */
  public function getFrameType() {
    return array(
      100 => 'I1-紧急服务器上架',
      110 => 'I2-服务器上架',
      111 => 'I2-服务器待上架',
      120 => 'I3-服务器重装',
      140 => 'I5-服务器下架'
    );
  }
  /**
   * IP or 带宽类型
   */
  public function getIpType() {
    return array(
      130 => 'I4',
      131 => 'I4',
      132 => 'I4',
      133 => 'I4'
    );
  }
  /**
   * 开关机类型
   */
  public function getSwitchType() {
    return array(
      150 => 'I6-UP端口',
      151 => 'I6-DOWN端口'
    );
  }
  /**
   * 机房事务类型
   */
  public function getFoomType() {
    return array(
      160 => 'I7',
      161 => 'I8'
    );
  }
  /**
   * 故障类型
   */
  public function getFaultType() {
    return array(
      230 => 'P4',
      240 => 'P5',
      250 => 'P6',
      260 => 'P7',
      300 => 'E类其他'
    );
  }
  /**
   * 重大故障类型
   */
  public function getMajorFaultType() {
    return array(
      200 => 'P1',
      210 => 'P2',
      220 => 'P3'
    );
  }
  /**
   * 周期型工单类型
   */
  public function getCycleType() {
    return array(
      400 => 'I9-周期性工单',
      410 => 'P9-周期性工单'
    );
  }
  /**
   * 物流工单类型
   */
  public function getLogisticsType() {
    return array(
      500 => 'I10-物流工单'
    );
  }

  /**
   * 所有分类
   */
  public function getAllOptions() {
    $options = array();
    $types = $this->DefaultTypes();
    foreach($types as $key => $type) {
      $name = $type['class_name'];
      if(array_key_exists($name, $options)) {
        $options[$name] .= ',' . $key;
      } else {
        $options[$name] = $key;
      }
    }
    return array_flip($options);
  }

  /**
   * 获取分类
   */
  public function getTypeById($tid) {
    if(array_key_exists($tid, $this->types)) {
      return $this->types[$tid];
    }
    $type = $this->database->select('work_sheet_type', 't')
      ->fields('t')
      ->condition('tid', $tid)
      ->execute()
      ->fetchObject();
    $this->types[$tid] = $type;
    return $type;
  }

  /**
   * 获取分类
   */
  public function getTypeDate() {
    $datas = $this->database->select('work_sheet_type', 't')
      ->fields('t')
      ->execute()
      ->fetchAll();
    foreach($datas as $data) {
      $this->types[$data->tid] = $data;
    }
    return $this->types;
  }
  
  /**
   * 获取完成时间
   */
  public function getCompleteTime($tid) {
    $type = $this->getTypeById($tid);
    return $type->complete_time;
  }
  /**
   * 修改指定分类
   */
  public function update($values, $tid) {
    $this->database->update('work_sheet_type')
      ->fields($values)
      ->condition('tid', $tid)
      ->execute();
  }
}