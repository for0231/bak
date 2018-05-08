<?php

namespace Drupal\order;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;

class OrderService {

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
   * 插入改价申请记录
   *
   *  @param $field_arr array
   *    字段数组
   *
   */
  public function saveApplyRecord($field_arr) {
    return $this->database->insert('order_change_price')
      ->fields($field_arr)
      ->execute();
  }
  /**
   * update order status and payment price.
   */
  public function updateOrder($order) {
    $paid_price = $order->getSimpleValue('order_price') - $order->getSimplevalue('discount_price');
    if (in_array($order->getSimpleValue('status'), array(0, 1, 2))){
      $order->set('payment_date', REQUEST_TIME)
        ->set('payment_mode', 2)
        ->set('paid_price', $paid_price)
        ->set('status', 3)
        ->save();
      $dis = ServerDistribution::createInstance();
      $dis->orderDistributionServer($order);
    }
    return 1; //订单状态保存成功并分
  }
  /**
   * 查询所有的改价数据
   */
  public function getPriceChangeData($condition = array(),$header=array()) {
    $query = $this->database->select('order_change_price','record')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender')
      ->fields('record');
    if(!empty($condition)) {
      foreach($condition as $key => $v) {
        if($key == 'order_code') {
           $query->condition($key, $v.'%', 'like');
        }else {
           $query->condition($key, $v);
        }
      }
    }
    $query->limit(PER_PAGE_COUNT)
      ->orderByHeader($header)
      ->orderBy('id', 'DESC');
    return $query->execute()->fetchAll();
  }

  /**
   * 查询所有单条改价数据
   *
   * @param $id
   *  编号
   */
  public function getPriceChangeById($id) {
    $query = $this->database->select('order_change_price','record')
      ->fields('record')
      ->condition('id', $id);
    return $query->execute()->fetchObject();
  }

  /**
   * 对改价申请进行审核
   *
   * @param $id
   *  编号
   *
   * @param $field_arr array
   *    字段数组
   */
  public function auditPriceChangeApplation($id, $field_arr, EntityInterface $order) {
    //移除数组中为空值的项
    $field_arr = array_filter($field_arr, create_function( '$v', 'return   !empty($v);'));
    $transaction = $this->database->startTransaction();
    try{
      $this->database->update('order_change_price')
        ->fields($field_arr)
        ->condition('id ', $id)
        ->execute();
      // order实体保存变更
      $order->save();

    } catch (\Exception $e) {
      $transaction->rollback();
    }
  }

  /**
   * 列表查询页用到。获取用户
   */
  public function getAuditUser($type) {
    $query = $this->database->select('order_change_price','o');
    if($type == 'ask') {
      $query->innerJoin('user_employee_data', 'c', 'o.ask_uid=c.uid');
    } elseif($type == 'audit') {
      $query->innerJoin('user_employee_data', 'c', 'o.audit_uid=c.uid');
    }
    $query->fields('o',array('ask_uid', 'audit_uid'));
    $query->fields('c',array('employee_name'));
    if($type == 'ask') {
      $query->condition('o.ask_uid','0','<>');
      //$query->groupBy('o.ask_uid');
    } elseif($type == 'audit') {
      $query->condition('o.audit_uid','0','<>');
      //$query->groupBy('o.audit_uid');
    }
     return $query->execute()->fetchAll();
  }

  /**
   * 保存试用申请
   *
   * @param $field_arr
   *  插入的数据
   */
  public function saveTrialRecord(array $field_arr) {
    return $this->database->insert('order_server_trial')
      ->fields($field_arr)
      ->execute();
  }

  /**
   * 查义所有的试用申请订单
   */
  public function getTrialData($condition = array(), $header=array()) {
    $query = $this->database->select('order_server_trial','record')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender')
      ->fields('record');
    if(!empty($condition)) {
      foreach($condition as $key => $v) {
        if($key == 'order_code') {
           $query->condition($key, $v.'%', 'like');
        }else {
           $query->condition($key, $v);
        }
      }
    }
    $query->limit(PER_PAGE_COUNT)
      ->orderByHeader($header);

    return $query->execute()->fetchAll();
  }

  /**
   * 通过id获取试用信息
   */
  public function getTrialById($id) {
    $query = $this->database->select('order_server_trial','record')
      ->fields('record')
      ->condition('id', $id);
    return $query->execute()->fetchObject();
  }

  /**
   * 拒绝试用
   */
  public function TrialRefuse($trial, EntityInterface $order) {
    $transaction = $this->database->startTransaction();
    try {
      $query = $this->database->update('order_server_trial')
        ->fields(array(
          'audit_uid' => $trial->audit_uid,
          'audit_date' => $trial->audit_date,
          'audit_description' => $trial->audit_description,
          'status' => $trial->status
        ))
        ->condition('id', $trial->id)
        ->execute();
      $order->save();
    }
    catch (\Exception $e) {
      $transaction->rollback();
    }
  }

  /**
   *同意试用
   */
  public function TrialAgree($trial, EntityInterface $order) {
    $transaction = $this->database->startTransaction();
    try {
      $query = $this->database->update('order_server_trial')
        ->fields(array(
          'audit_uid' => $trial->audit_uid,
          'audit_date' => $trial->audit_date,
          'audit_description' => $trial->audit_description,
          'status' => $trial->status
        ))
        ->condition('id', $trial->id)
        ->execute();
      $order->save();

      //分配服务器
      $product_service = \Drupal::service('order.product');
      $order_product = $product_service->getProductById($trial->order_product_id);
      $hostclient = entity_create('hostclient', array(
        'client_uid' => $trial->client_id,
        'product_id' => $order_product->product_id,
        'description' => $order_product->description,
        'init_pwd' => '123456',
        'trial'=> 1,
        'status' => 0,
      ));
      $hostclient->save();

      $handle_info['handle_order_id'] = $order->id();
      $handle_info['handle_order_product_id'] = $trial->order_product_id;
      $handle_info['handle_action'] = 4;
      $handle_info['client_description'] = $order_product->description;
      $handle_info['hostclient_id'] = $hostclient->id();
      $hostclient_service = \Drupal::service('hostclient.serverservice');
      $hid = $hostclient_service->addHandleInfo($handle_info);
      \Drupal::service('sop.soptaskservice')->sop_task_iband_for_hostclient($hostclient, 'Normal_Trial_hostclient', $hid);
      //返回日志数据
      $handle_info_log = $hostclient_service->loadHandleInfo($hid);
      $hostclient->other_data = array('data_id' => $hid, 'data_name' => 'hostclient_handle_info', 'data' => (array)$handle_info_log);
      return $hostclient;
    }
    catch (\Exception $e) {
      $transaction->rollback();
    }
  }

  /**
   * 列表查询页用到。获取用户
   */
  public function getTrialAuditUser($type) {
    $query = $this->database->select('order_server_trial','o');
    if($type == 'ask') {
      $query->innerJoin('user_employee_data', 'c', 'o.ask_uid=c.uid');
    } elseif($type == 'audit') {
      $query->innerJoin('user_employee_data', 'c', 'o.audit_uid=c.uid');
    }
    $query->fields('o',array('ask_uid', 'audit_uid'));
    $query->fields('c',array('employee_name'));
    if($type == 'ask') {
      $query->condition('o.ask_uid','0','<>');
      //$query->groupBy('o.ask_uid');
    } elseif($type == 'audit') {
      $query->condition('o.audit_uid','0','<>');
      //$query->groupBy('o.audit_uid');
    }
    return $query->execute()->fetchAll();
  }

  /**
   * 检验订单是否有试用服务器
   */
  public function checkTrialHostclient($order_id) {
    $query = $this->database->select('hostclients_field_data', 'h');
    $query->innerJoin('hostclient_handle_info', 'hi', 'h.hid=hi.hostclient_id');
    $query->fields('h', array('hid'));
    $hid = $query->condition('h.trial', 1)
      ->condition('hi.handle_order_id', $order_id)
      ->execute()
      ->fetchField();
    if($hid) {
      return true;
    }
    return false;
  }

  /**
   * 获取试用服务器
   */
  public function loadTrialHostclient($order_id, $order_product_id) {
    $query = $this->database->select('hostclients_field_data', 'h');
    $query->innerJoin('hostclient_handle_info', 'hi', 'h.hid=hi.hostclient_id');
    $query->fields('h', array('hid'));
    $hid = $query->condition('h.trial', 1)
      ->condition('hi.handle_action', 4)
      ->condition('hi.handle_order_id', $order_id)
      ->condition('hi.handle_order_product_id', $order_product_id)
      ->execute()
      ->fetchField();
    if($hid) {
      return entity_load('hostclient', $hid);
    }
    return null;
  }

  /**
   *
   */
  public function userOrderList($condition) {
    $query = $this->database->select('idc_order_field_data', 'o')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender');

    $query->fields('o', array('oid'));
    foreach($condition as $name => $value) {
      if(is_array($value)) {
        if($value['op'] == 'like') {
          $query->condition($value['field'],  $value['value'].'%', 'like');
        } else {
          $query->condition($value['field'], $value['value'], $value['op']);
        }
      }else {
        $query->condition($name, $value);
      }
    }
    $query->orderBy('o.status', 'ASC');
    $query->limit(PER_PAGE_COUNT);
    return $query->execute()->fetchCol();
  }

  /**
   * 根据订单编码获取订单的编号
   */
  public function getOrderIdByOrderCode($order_code) {
    $query = $this->database->select('idc_order_field_data', 'o')
      ->fields('o', array('oid'))
      ->condition('o.code', $order_code);
    return $query->execute()->fetchField();
  }
}
