<?php

/**
 * @file
 * Contains file
 * \Drupal\worksheet\WorkSheetLogisticsListBuilde.
 */

namespace Drupal\worksheet;

use Drupal\Core\Url;
/**
 *
 */
class WorkSheetLogisticsListBuilde {

  protected $dept = 'All';

  
  protected function load() {
    $storage = \Drupal::entityManager()->getStorage('work_sheet_logistics');
    $entity_query = $storage->getBaseQuery();
    if($this->dept == 'operation') {
      $value = array(1,10,15,16,20,25);
      $entity_query->condition('status', $value, 'IN');
    } else if ($this->dept == 'business') {
      $value = array(1,5,30,31,35,40);
      $entity_query->condition('status', $value, 'IN');
    } else {
      $status = getStatus();
      unset($status[45]);
      $value = array_keys($status);
      $entity_query->condition('status', $value, 'IN');
    }
    $result = $entity_query->execute()->fetchCol();
    if($result) {
      return $storage->loadMultiple($result);
    }
    return array();
  }

  
  /**
   * 排序数据
   */
  protected function orderData($datas) {
    if($this->dept == 'operation') {
      return $this->operationOrder($datas);
    } else if ($this->dept == 'business') {
      return $this->businessOrder($datas);
    } else {
      return $datas;
    }
    return $datas;
  }

  /**
   * 技术部排序
   */
  protected function operationOrder($datas) {
    $weight = array(1 =>0, 25 => 1, 10 => 2, 15=>4, 16=>5, 20=>6, 5=>7, 30=>8, 35=>9, 31=>10, 40=>11);
    $order_status = array();
    $order_created = array();
    foreach($datas as $key => $data) {
      $status = $data->get('status')->value;
      $created = $data->get('created')->value;
      $order_status[$key] = isset($weight[$status]) ? $weight[$status] : 99;
      $order_created[$key] = $created;
    }
    array_multisort($order_status,$order_created, $datas);
    return $datas;
  }
  /**
   * 业务部排序
   */
  protected function businessOrder($datas) {
    $weight = array(1=>0, 5=>1, 30=>2, 35=>4, 31=>5, 40=>6, 25=>7, 10=>8, 15=>9, 16=>10, 20=>11);
    $order_status = array();
    $order_created = array();
    foreach($datas as $key => $data) {
      $status = $data->get('status')->value;
      $created = $data->get('created')->value;
      $order_status[$key] = isset($weight[$status]) ? $weight[$status] : 99;
      $order_created[$key] = $created;
    }
    array_multisort($order_status, $order_created, $datas);
    return $datas;
  }

  protected function buildHeader() {
    $header['code'] = '工单编码';
    $header['status'] = '工单状态';
    $header['level'] = '所属分类';
    $header['order_code'] = '物流单号';
    $header['logistics_company'] = '物流公司';
    $header['send_time'] = '发件时间';
    $header['estimate_time'] = '预计到达时间';
    $header['creater'] = '创建人';
    $header['handler'] = '处理人';
    $header['last'] = '交接人';
    $header['create_time'] = '建单时间';
    $header['time'] = '剩时(分)';
    $header['op'] = '操作';
    return $header;
  }

  protected function buildRow($item) {
    $row['id'] = $item->get('code')->value;
    $row['status'] = getStatus()[$item->get('status')->value];
    $type = \Drupal::service('worksheet.type')->getTypeById($item->get('tid')->value);
    $row['type'] = $type->class_name . '-' . $type->operation_name;
    $row['order_code'] = $item->get('order_code')->value;
    $row['logistics_company'] = $item->get('logistics_company')->value;
    $row['send_time'] = date('Y-m-d H:i:s', $item->get('send_time')->value);
    if($estimate_time = $item->get('estimate_time')->value) {
      $row['estimate_time'] = date('Y-m-d H:i:s', $estimate_time);
    } else {
      $row['estimate_time'] = '';
    }
    $row['creater'] = $item->get('uid')->entity->label();;
    $row['handler'] = $item->handleUser();
    $row['last'] = $item->lastUser();
    $row['created'] = date('Y-m-d H:i:s', $item->get('created')->value);
    if($total = $item->get('completed')->value) {
      if($begin_time = $item->get('begin_time')->value) {
        $end_time = time();
        if($e = $item->get('end_time')->value) {
          $end_time = $e;
        }
        $y = $end_time - $begin_time;
        $s = ($total * 60) - $y;
        if($s >= 0) {
          $row['time'] = worksheet_time2string($s);
        } else {
          $row['time'] = '(超时)' . worksheet_time2string(abs($s));
        }
      } else {
        $row['time'] = '';
      }
    } else {
      $row['time'] = '不记时';
    }
    $row['op']['data'] = array(
      '#type' => 'operations',
      '#links' => $this->getOperations($item)
    );
    return $row;
  }
  /**
   * 构建操作
   */
  private function getOperations($item) {
    $op = array();
    $option['query']['source'] = \Drupal::url('admin.worksheet.logistics.list');
    $account = \Drupal::currentUser();
    if($account->hasPermission('administer operation work sheet')) {
      $op['edit'] = array(
        'title' => '操作',
        'url' => new Url('admin.worksheet.sop.logistics.operation', array('work_sheet_logistics' => $item->get('wid')->value), $option)
      );
    }
    $op['info'] = array(
      'title' => '详情',
      'url' => new Url('admin.worksheet.sop.detail', array(
        'entity_type' => 'work_sheet_logistics',
        'wid' => $item->id()
      ), $option)
    );
    $op['delete'] = array(
      'title' => '删除',
      'url' => new Url('admin.worksheet.sop.delete', array(
        'entity_type' => 'work_sheet_logistics',
        'wid' => $item->id()
      ), $option)
    );
    $status = $item->get('status')->value;
    if($status == 1 || $status == 10) {
      $op['voice'] = array(
        'title' => '再次提醒',
        'url' => new Url('admin.worksheet.sop.remind', array(
          'entity_type' => 'work_sheet_logistics',
          'wid' => $item->id()
        ), $option)
      );
    }
    return $op;
  }
  
  /**
   * 列表
   */
  public function build() {
    $account = \Drupal::currentUser();
    $roles = $account->getRoles();
    if(in_array('worksheet_operation',$roles)) {
      $this->dept = 'operation';
    } else if (in_array('worksheet_business', $roles)) {
      $this->dept = 'business';
    }
    $datas = $this->load();
    $items = $this->orderData($datas);
    $build['left'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('left')
      )
    );
    $build['left']['list'] = array(
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#rows' => array(),
      '#empty' => '无数据'
    );
    foreach($items as $item) {
      if($row = $this->buildRow($item)) {
        $build['left']['list']['#rows'][$item->id()] = $row;
      }
    }
    return drupal_render($build);
  }
}
