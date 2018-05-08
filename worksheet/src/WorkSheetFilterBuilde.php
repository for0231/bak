<?php

/**
 * @file
 * Contains file
 * \Drupal\worksheet\WorkSheetFilterBuilde.
 */

namespace Drupal\worksheet;

use Drupal\Core\Url;
/**
 *
 */
class WorkSheetFilterBuilde {
  
  protected function load() {
    
    return array();
  }
  
  

  protected function buildHeader() {
    $header['id']  = '工单编码';
    $header['status'] = '订单状态';
    $header['type'] = '类型';
    $header['op_type'] = '操作类型';
    $header['ip'] = 'IP';
    $header['client'] = '公司名称';
    $header['uid'] = '创建人';
    $header['hander'] = '处理人';
    $header['last'] = '交接人';
    $header['created'] = '建单时间';
    $header['time'] = '耗时(分)';
    $header['op'] = '操作';
    return $header;
  }

  protected function buildRow($item) {
    $row['id'] = $item->get('code')->value;
    $row['status'] = getStatus()[$item->get('status')->value];
    $type = \Drupal::service('worksheet.type')->getTypeById($item->get('tid')->value);
    $row['type'] = $type->class_name;
    $row['op_type'] = $type->operation_name;
    $row['ip'] = $item->get('ip')->value;
    $row['client'] = $item->get('client')->value;
    $row['uid'] = $item->createUser();
    $row['hander'] = $item->handleUser();
    $row['last'] = $item->lastUser();
    $row['created'] = date('Y-m-d H:i:s', $item->get('created')->value);
    if($total = $item->get('completed')->value) {
      if($begin_time = $item->get('begin_time')->value) {
        $end_time = time();
        if($e = $item->get('end_time')->value) {
          $end_time = $e;
        }
        $s = $end_time - $begin_time;
        $row['time'] = worksheet_time2string($s);
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
    return array(
      'info' => array(
        'title' => '详情',
        'url' => new Url('admin.worksheet.sop.detail', array(
          'entity_type' => $item->get('entity_name')->value,
          'wid' => $item->get('wid')->value
        ))
      )
    );
  }
  
  /**
   * 列表
   */
  public function build() {
    $build['filters'] = array(
      '#type' => 'details',
      '#title' => '查询条件',
      '#open' => empty($_GET),
    );
    $build['filters']['keyword'] = array(
      '#type' => 'textfield',
      '#title' => '关键字',
      '#id' => 'filter_keyword',
      '#value' => isset($_GET['keyword']) ? $_GET['keyword'] : ''
    );
    $build['filters']['type'] = array(
      '#type' => 'select',
      '#title' => '工单类型',
      '#options' => array('all' => '-All-') + getEntityType(),
      '#id' => 'filter_type',
      '#value' => isset($_GET['type']) ? $_GET['type'] : 'all'
    );
    $users = entity_load_multiple('user');
    $creater = array();
    $hander = array();
    foreach($users as $user) {
      if($user->id() > 1) {
        if(in_array('worksheet_operation', $user->getRoles())) {
          $hander[$user->id()] = $user->label();
        } else {
          $creater[$user->id()] = $user->label();
        }
      }
    }
    $build['filters']['creater'] = array(
      '#type' => 'select',
      '#title' => '建单人',
      '#options' => array('all' => '-All-') + $creater + $hander,
      '#id' =>'filter_creater',
      '#value' => isset($_GET['creater']) ? $_GET['creater'] : 'all'
    );
    $build['filters']['hander'] = array(
      '#type' => 'select',
      '#title' => '处理人',
      '#options' => array('all' => '-All-') + $hander,
      '#id' => 'filter_hander',
      '#value' => isset($_GET['hander']) ? $_GET['hander'] : 'all'
    );
    $build['filters']['created_begin'] = array(
      '#type' => 'textfield',
      '#title' => '开始时间',
      '#id' => 'filter_begin',
      '#value' => isset($_GET['begin']) ? $_GET['begin'] : ''
    );
    $build['filters']['created_end'] = array(
      '#type' => 'textfield',
      '#title' => '结束时间',
      '#id' => 'filter_end',
      '#value' => isset($_GET['end']) ? $_GET['end'] : ''
    );
    $build['filters']['submit'] = array(
      '#type' => 'submit',
      '#value' => '查询',
      '#id' => 'filter_submit'
    );
    $items = array();
    if(!empty($_GET)) {
      $conditions = $_GET;
      if(!empty($_GET['begin'])) {
        $conditions['begin'] = strtotime($_GET['begin']) ;
      }
      if(!empty($_GET['end'])) {
        $conditions['end'] = strtotime($_GET['end']) ;
      }
      $ids = \Drupal::service('worksheet.dbservice')->loadFilter($conditions);
      $items = entity_load_multiple('work_sheet_base', $ids);
    }
    $build['list'] = array(
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#rows' => array(),
      '#empty' => '无数据'
    );
    foreach($items as $item) {
      if($row = $this->buildRow($item)) {
        $build['list']['#rows'][] = $row;
      }
    }
    $build['list_pager'] = array('#type' => 'pager');
    $build['#attached'] = array(
      'library' => array('worksheet/drupal.work-sheet-filter')
    );
    return $build;
  }
}
