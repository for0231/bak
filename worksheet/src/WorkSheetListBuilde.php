<?php

/**
 * @file
 * Contains file
 * \Drupal\worksheet\WorkSheetListBuilde.
 */

namespace Drupal\worksheet;

use Drupal\Core\Url;
/**
 *
 */
class WorkSheetListBuilde {
  protected $mode = 4;
  protected $order = 3;
  protected $types = array();
  protected $dept = 'All';

  public function setMode($mode) {
    $this->mode = $mode;
  }
  public function setOrder($order) {
    $this->order = $order;
  }
  public function setTypes($types) {
    $this->types = $types;
  }
  
  protected function load() {
    $storage = \Drupal::entityManager()->getStorage('work_sheet_base');
    $entity_query = $storage->getBaseQuery();
    if($this->dept == 'operation') {
      $this->operationCondition($entity_query);
    } else if ($this->dept == 'business') {
      $this->businessCondition($entity_query);
    } else {
      $status = getStatus();
      unset($status[45]);
      $value = array_keys($status);
      $entity_query->condition('status', $value, 'IN');
    }
    if(!empty($this->types)) {
      $entity_query->condition('tid', $this->types, 'IN');
    }
    $result = $entity_query->execute()->fetchCol();
    if($result) {
      return $storage->loadMultiple($result);
    }
    return array();
  }

  /**
   * 业务列表条件
   */
  protected function businessCondition($entity_query) {
    $mode = $this->mode;
    if($mode== 1) {
      $status = getStatus();
      unset($status[45]);
      $value = array_keys($status);
      $entity_query->condition('status', $value, 'IN');
    } else if($mode == 2) {
      $value = array(1,5,30,31,35,40);
      $entity_query->condition('status', $value, 'IN');
    } else if ($mode == 3) {
      $status = getStatus();
      unset($status[45]);
      $value = array_keys($status);
      $entity_query->condition('status', $value, 'IN');
      $uid = \Drupal::currentUser()->id();
      $entity_query->condition('uid', \Drupal::currentUser()->id());
    } else {
      $value = array(1,5,30,31,35,40);
      $entity_query->condition('status', $value, 'IN');
      $uid = \Drupal::currentUser()->id();
      $entity_query->condition('uid', \Drupal::currentUser()->id());
    }
  }
  /**
   * 技术列表部条件
   */
  protected function operationCondition($entity_query) {
    $mode = $this->mode;
    if($mode== 1) {
      $status = getStatus();
      unset($status[45]);
      $value = array_keys($status);
      $entity_query->condition('status', $value, 'IN');
    } else if($mode == 2) {
      $value = array(1,10,15,16,20,25);
      $entity_query->condition('status', $value, 'IN');
    } else if ($mode == 3) {
      $value = array(1,10,15,16,20,25);
      $entity_query->condition('status', $value, 'IN');
      $uid = \Drupal::currentUser()->id();
      $entity_query->condition('handle_uid', $uid);
    } else {
      $value = array(1,10,15,16,20,25);
      $entity_query->condition('status', $value, 'IN');
      $uid = \Drupal::currentUser()->id();
      $entity_query->condition('handle_uid', array(0, $uid), 'IN');  
    }
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
    $order = $this->order;
    $weight = array(1 =>0, 25 => 1, 10 => 2, 20=>3, 15=>4, 16=>5, 5=>7, 30=>8, 35=>9, 31=>10, 40=>11); //状态对应的顺序
    if($order == 1) {
      $order_status = array();
      $order_created = array();
      foreach($datas as $key => $data) {
        $status = $data->get('status')->value;
        $created = $data->get('created')->value;
        $order_status[$key] = isset($weight[$status]) ? $weight[$status] : 99;
        $order_created[$key] = $created;
      }
      array_multisort($order_status, $order_created, $datas);
    } else if ($order == 2) {
      $order_user = array();
      $order_created = array();
      foreach($datas as $key => $data) {
        $handle_uid = $data->get('handle_uid')->target_id;
        if(empty($handle_uid)) {
          $order_user[$key] = 1;
        } else if ($handle_uid == \Drupal::currentUser()->id()) {
          $order_user[$key] = 2;
        } else {
          $order_user[$key] = 3;
        }
        $order_created[$key] = $data->get('created')->value;
      }
      array_multisort($order_user, $order_created, $datas);
    } else if ($order == 4) {
      $order_status = array();
      $order_tid = array();
      $order_created = array();
      foreach($datas as $key => $data) {
        $status = $data->get('status')->value;
        $tid = $data->get('tid')->value;
        $created = $data->get('created')->value;
        $order_status[$key] = isset($weight[$status]) ? $weight[$status] : 99;
        $order_tid[$key] = $tid;
        $order_created[$key] = $created;
      }
      array_multisort($order_status, $order_tid, $order_created, $datas);
    } else {
      $order_status = array();
      $order_user = array();
      $order_created = array();
      foreach($datas as $key => $data) {
        $status = $data->get('status')->value;
        $uid = $data->get('handle_uid')->target_id;
        $created = $data->get('created')->value;
        $order_status[$key] = isset($weight[$status]) ? $weight[$status] : 99;
        $order_user[$key] = $uid;
        $order_created[$key] = $created;
      }
      array_multisort($order_status, $order_user, $order_created, $datas);
    }
    return $datas;
  }
  /**
   * 业务部排序
   */
  protected function businessOrder($datas) {
    $weight = array(1=>0, 5=>1, 30=>2, 35=>4, 31=>5, 40=>6, 25=>7, 10=>8, 15=>9, 16=>10, 20=>11);
    $order = $this->order;
    if($order == 1) {
      $order_status = array();
      $order_created = array();
      foreach($datas as $key => $data) {
        $status = $data->get('status')->value;
        $created = $data->get('created')->value;
        $order_status[$key] = isset($weight[$status]) ? $weight[$status] : 99;
        $order_created[$key] = $created;
      }
      array_multisort($order_status, $order_created, $datas);
    } else if ($order == 2) {
      $order_user = array();
      $order_created = array();
      foreach($datas as $key => $data) {
        $status = $data->get('status')->value;
        $uid = $data->get('uid')->target_id;
        $created = $data->get('created')->value;
        $order_user[$key] = $uid;
        $order_created[$key] = $created;
      }
      array_multisort($order_user, $order_created, $datas);
    } else if ($order == 4) {
      $order_status = array();
      $order_tid = array();
      $order_created = array();
      foreach($datas as $key => $data) {
        $status = $data->get('status')->value;
        $tid = $data->get('tid')->value;
        $created = $data->get('created')->value;
        $order_status[$key] = isset($weight[$status]) ? $weight[$status] : 99;
        $order_tid[$key] = $tid;
        $order_created[$key] = $created;
      }
      array_multisort($order_status, $order_tid, $order_created, $datas);
    } else {
      $order_status = array();
      $order_user = array();
      $order_created = array();
      foreach($datas as $key => $data) {
        $status = $data->get('status')->value;
        $uid = $data->get('uid')->target_id;
        $created = $data->get('created')->value;
        $order_status[$key] = isset($weight[$status]) ? $weight[$status] : 99;
        $order_user[$key] = $uid;
        $order_created[$key] = $created;
      }
      array_multisort($order_status, $order_user, $order_created, $datas);
    }
    return $datas;
  }

  protected function buildHeader() {
    $header['id']  = '工单编码';
    $header['status'] = '工单状态';
    $header['type'] = '优先级';
    $header['op_type'] = '操作类型';
    $header['ip'] = 'IP';
    $header['client'] = '公司名称';
    $header['uid'] = '创建人';
    $header['hander'] = '处理人';
    $header['last'] = '交接人';
    $header['created'] = '建单时间';
    $header['time'] = '剩时(分)';
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
    $option['query']['source'] = \Drupal::url('admin.worksheet.sop');
    $account = \Drupal::currentUser();
    if($account->hasPermission('administer operation work sheet')) {
      $entity_name = $item->get('entity_name')->value;
      switch($entity_name) {
        case 'work_sheet_frame':
          $op['edit'] = array(
            'title' => '操作',
            'url' => new Url('admin.worksheet.sop.frame.operation', array('work_sheet_frame' => $item->get('wid')->value), $option)
          );
          break;
        case 'work_sheet_ip':
          $op['edit'] = array(
            'title' => '操作',
            'url' => new Url('admin.worksheet.sop.ip.operation', array('work_sheet_ip' => $item->get('wid')->value), $option)
          );
          break;
        case 'work_sheet_switch':
          $op['edit'] = array(
            'title' => '操作',
            'url' => new Url('admin.worksheet.sop.switch.operation', array('work_sheet_switch' => $item->get('wid')->value), $option)
          );
          break;
        case 'work_sheet_room':
          $op['edit'] = array(
            'title' => '操作',
            'url' => new Url('admin.worksheet.sop.room.operation', array('work_sheet_room' => $item->get('wid')->value), $option)
          );
          break;
        case 'work_sheet_fault':
          $op['edit'] = array(
            'title' => '操作',
            'url' => new Url('admin.worksheet.sop.fault.operation', array('work_sheet_fault' => $item->get('wid')->value), $option)
          );
          break;
        case 'work_sheet_cycle':
          $op['edit'] = array(
            'title' => '操作',
            'url' => new Url('admin.worksheet.sop.cycle.operation', array('work_sheet_cycle' => $item->get('wid')->value), $option)
          );
          break;
        case 'work_sheet_logistics':
          $op['edit'] = array(
            'title' => '操作',
            'url' => new Url('admin.worksheet.sop.logistics.operation', array('work_sheet_logistics' => $item->get('wid')->value), $option)
          );
          break;
        case 'work_sheet_major_fault':
          $op['edit'] = array(
            'title' => '操作',
            'url' => new Url('admin.worksheet.sop.majorfault.operation', array('work_sheet_major_fault' => $item->get('wid')->value), $option)
          );
          break;
        default:
          break;
      }
    }
    $op['info'] = array(
      'title' => '详情',
      'url' => new Url('admin.worksheet.sop.detail', array(
        'entity_type' => $item->get('entity_name')->value,
        'wid' => $item->get('wid')->value
      ), $option)
    );
    $op['delete'] = array(
      'title' => '删除',
      'url' => new Url('admin.worksheet.sop.delete', array(
        'entity_type' => $item->get('entity_name')->value,
        'wid' => $item->get('wid')->value
      ), $option)
    );
    $status = $item->get('status')->value;
    if($status == 1 || $status == 10) {
      $op['voice'] = array(
        'title' => '再次提醒',
        'url' => new Url('admin.worksheet.sop.remind', array(
          'entity_type' => $item->get('entity_name')->value,
          'wid' => $item->get('wid')->value,
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
    //每天9点修改处理时间
    $db_service = \Drupal::service('worksheet.dbservice');
    $db_service->updateRoomHandleDate();
    //工作量列表
    $config = \Drupal::config('worksheet.settings');
    $assigner = $config->get('task_assigner');
    if($account->hasPermission('access worksheet Display workload') || $assigner == $account->id()) {
      $build['right'] = array(
        '#type' => 'container',
        '#attributes' => array(
          'class' => array('right')
        )
      );
      $build['right']['list'] = array(
        '#type' => 'table',
        '#rows' => array(),
        '#attributes' => array(
          'class' => array('workload')
        ),
      );
      $workload = $db_service->loadWorkload();
      $roomWorkLoad = $db_service->roomWorkload();
      $build['right']['list']['#rows'][] = array(
        'I类工作量',
        array(
          'data' => array(
            '#type' => 'table',
            '#rows' => $this->workloadRow($workload['i'])
          )
        )
      );
      $build['right']['list']['#rows'][] = array(
        'P类工作量',
        array(
          'data' => array(
            '#type' => 'table',
            '#rows' => $this->workloadRow($workload['p'])
          )
        )
      );
      $build['right']['list']['#rows'][] = array(
        '机房事务',
        array(
          'data' => array(
            '#type' => 'table',
            '#rows' => array(
              array('当天处理(单位分钟)', isset($roomWorkLoad[0]) ? $roomWorkLoad[0] : 0), 
              array('下一个工作日处理(单位分钟)', isset($roomWorkLoad[1]) ? $roomWorkLoad[1] : 0)
            )
          )
        )
      );
    }
    $begin = strtotime('-7 days');
    $end = time();
    $statistic = \Drupal::service('worksheet.statistic');
    $data = $statistic->getCountQuestion($begin,$end);
    $rows = array();
    foreach($data as $item){
      $tmp = array(
        'problem_types' => empty($item->problem_types)?'无': $statistic->get_problem_name($item->problem_types),
        'problem_types_child' =>empty($item->problem_types_child)?'无':$statistic->get_problem_name($item->problem_types_child),
        'count' => $item->count,
      );
      $rows[] = $tmp;
    }
    $build['rightmost'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('rightmost')
      )
    );
    $build['rightmost']['list'] = array(
      '#type' => 'table',
      '#attributes' => array(
        'class' => array('workload')
      ),
      '#header' => array('问题父级名称','问题子级分类名称','问题个数'),
      '#rows' => $rows,
      '#empty' =>'无数据',
    );
    return drupal_render($build);
  }
  /**
   * 工作量行
   */
  private function workloadRow($item) {
    $storage = \Drupal::entityManager()->getStorage('user');
    $rows = array();
    foreach($item as $uid => $workload) {
      $user = $storage->load($uid);
      $rows['name'][] = $user->label();
      $rows['value'][] = $workload;
    }
    return $rows;
  }
}
