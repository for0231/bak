<?php

namespace Drupal\order;

use Drupal\Core\Database\Connection;

class OrderProductService {

  /*
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * 增加订单产品
   */
  public function add_multiple(array $products) {
    foreach($products as $product){
      $order_product_id = $this->database->insert('order_product')
        ->fields(array(
          'action' => $product->action,
          'product_id' => $product->product_id,
          'product_name' => $product->product_name,
          'product_type' => $product->product_type,
          'product_num' => $product->product_num,
          'product_limit' => $product->product_limit,
          'base_price' => $product->base_price,
          'custom_price' => $product->custom_price,
          'description' => $product->description,
          'order_id' => $product->order_id,
          'rid' => $product->rid,
        ))
        ->execute();
      $details = $product->details;
      foreach($details as $detail) {
        $detail->order_product_id = $order_product_id;
        $this->add_product_detail($detail);
      }
    }
  }
  /**
   * 增加产品所选业务信息
   */
  public function add_product_detail($detail) {
    $this->database->insert('order_product_detail')
      ->fields(array(
        'business_id' => $detail->business_id,
        'business_name' => $detail->business_name,
        'business_content' => $detail->business_content,
        'business_content_name' => $detail->business_content_name,
        'business_default' => $detail->business_default,
        'business_price' => $detail->business_price,
        'combine_mode' => $detail->combine_mode,
        'order_product_id' => $detail->order_product_id
      ))
      ->execute();
  }

  /**
   * 通过订单产品id，获取订单产品
   */
  public function getProductById($order_product_id) {
    $query = $this->database->select('order_product','op')
      ->fields('op') 
      ->condition('opid', $order_product_id);
    return $query->execute()->fetchObject();
  }

  /**
   * 根据订单编号查询到订单所包含的产品
   *
   * @param $oid int
   *   订单编号
   *
   * @return 该订单下的产品集合
   */
  public function getProductByOrderId($oid) {
    $query = $this->database->select('order_product','op')
      ->fields('op')
      ->condition('order_id', $oid);
    return $query->execute()->fetchAll();
  }

  /**
   * 根据订单编号查询到订单所包含的产品
   *
   * @param $order_product_id int
   *   所属订单产品Id
   *
   * @return 产品下的业务集合
   */
  public function getOrderBusiness($order_product_id) {
    $query = $this->database->select('order_product_detail','opd')
      ->fields('opd')
      ->condition('order_product_id', $order_product_id);
    return $query->execute()->fetchAll();
  }

  /**
   * 查询所有提交过订单的客户
   */
  public function getClient() {
    $query = $this->database->select('idc_order_field_data','o');
    $query->innerJoin('user_client_data', 'c', 'o.uid=c.uid');
    $query->fields('o',array('uid'));
    $query->fields('c',array('client_name'));
    $query->condition('o.uid','0','<>')
      ->orderBy('o.uid');
     return $query->execute()->fetchAll();
  }

  /**
   * 
   */
  public function getClientService() {
    $query = $this->database->select('idc_order_field_data','o');
    $query->innerJoin('user_employee_data', 'c', 'o.client_service=c.uid');
    $query->fields('o',array('client_service'));
    $query->fields('c',array('employee_name'));
    $query->condition('o.client_service','0','<>')
      ->orderBy('o.client_service');
     return $query->execute()->fetchAll();
  }
}

