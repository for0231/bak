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
class WorkSheetHistoryBuilde {
  protected $formBuilder;
  protected $typeService;

  public function __construct() {
    $this->formBuilder = \Drupal::getContainer()->get('form_builder');
    $this->typeService = \Drupal::service('worksheet.type');
  }
  
  private function filter() {
    $condition = array();
    if(isset($_SESSION['worksheet_history'])) {
      if(!empty($_SESSION['worksheet_history']['keyword'])) {
        $condition['keyword'] = $_SESSION['worksheet_history']['keyword'];
      }
      if(!empty($_SESSION['worksheet_history']['begin'])) {
        $condition['begin'] = strtotime($_SESSION['worksheet_history']['begin']);
      }
      if(!empty($_SESSION['worksheet_history']['end'])) {
        $condition['end'] = strtotime($_SESSION['worksheet_history']['end']) + 24*3600;
      }
      $condition['hander'] = $_SESSION['worksheet_history']['hander'];
      $condition['type'] = $_SESSION['worksheet_history']['type'];
      $condition['creater'] = $_SESSION['worksheet_history']['creater'];
      if(!empty($_SESSION['worksheet_history']['comment'])){
        $condition['comment'] = $_SESSION['worksheet_history']['comment'];
      }
    }
    return $condition;
  }

  /**
   * 查询数据
   */
  public function load() {
    $condition = $this->filter();
    $ids = \Drupal::service('worksheet.dbservice')->loadHistory($condition);
    return entity_load_multiple('work_sheet_base', $ids);
  }

  protected function buildHeader() {
    $header['id']  = '工单编码';
    $header['type'] = '类型';
    $header['op_type'] = '操作类型';
    $header['ip'] = 'IP';
    $header['client'] = '公司名称';
    $header['uid'] = '创建人';
    $header['hander'] = '处理人';
    $header['last'] = '交接人';
    $header['time'] = '耗时(分)';
    $header['operate'] = '操作';
    return $header;
  }

  protected function buildRow($item) {
    //得到当前的页数
    $page = empty($_GET['page'])?0:$_GET['page'];
    $row['id']['data'] = array('#markup' => $item->get('code')->value);
    $type = $this->typeService->getTypeById($item->get('tid')->value);
    $row['type']['data'] = array('#markup' =>$type->class_name);
    $row['op_type']['data'] = array('#markup' =>$type->operation_name);
    $row['ip']['data'] =array('#markup' => $item->get('ip')->value);
    $row['client']['data'] = array('#markup' =>$item->get('client')->value);
    $row['uid']['data'] = array('#markup' =>$item->createUser());
    $row['hander']['data'] = array('#markup' =>$item->handleUser());
    $row['last']['data'] = array('#markup' =>$item->lastUser());
    $begin_time = $item->get('begin_time')->value;
    $end_time = $item->get('com_time')->value;
    $useTiem = $end_time - $begin_time;
    $row['time']['data'] = array('#markup' =>worksheet_time2string($useTiem));
    $row['op']['data'] = array(
      '#type' => 'operations',
      '#links' => array(
        'info' => array(
          'title' => '详情',
          'url' => new Url('admin.worksheet.sop.detail', array(
            'entity_type' => $item->get('entity_name')->value,
            'wid' => $item->get('wid')->value
          ), array('query'=> array(
            'source' => \Drupal::url('admin.worksheet.history'),
            'page' => $page,
          )))
        ),
      )
    );
    return $row;
  }

  /**
   * 列表
   */
  public function build() {
    $data =  $this->load();
    $comm = array();
    $build['filter'] = $this->formBuilder->getForm('Drupal\worksheet\Form\WorkSheetHistoryFilterForm');
    $build['list'] = array(
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#rows' => array(),
      '#empty' => '无数据'
    );
    foreach($data as $item) {
      if($row = $this->buildRow($item)) {
        $comm = \Drupal::service('worksheet.dbservice')->getcomm($item->get('code')->value,$item->get('entity_name')->value);
        if(!empty($comm)){
          if($comm[0]->isno_comment){
            $row['#attributes']['style'] = 'color:blueviolet';
          }
        }
        $build['list'][$item->id()] = $row;
      }
    }
    $build['list_pager'] = array('#type' => 'pager');
    $build['#attached'] = array(
      'library' => array('worksheet/drupal.work_sheet_history')
    );
    
    return $build;
  }
  /**
   * 导出数据
   */
  public function export() {
    $conditions = $this->filter();
    if(empty($conditions['type'])) {
      echo '请选择工单类型';
      exit;
    }
    $filename = '工单数据'.time().'.csv';
    header('Content-Type: application/vnd.ms-excel;');
    header('Content-Disposition: attachment;filename="'.$filename.'"');
    header('Cache-Control: max-age=0');
    $fp = fopen('php://output', 'a');
    fwrite($fp, chr(0xEF).chr(0xBB).chr(0xBF)); // 添加 BOM
    $datas = \Drupal::service('worksheet.dbservice')->historyExportData($conditions);
    if(empty($datas)) {
      fclose($fp);
      exit;
    }
    $type = $conditions['type'];
    $head_base = array('工单编号', '类型', '公司名称', '联系人','建单时间','创建人', '处理人','交接人', '耗时(分)', '操作耗时(分)');
    if($type == 'work_sheet_frame') {
      $head = array_merge($head_base, array('管理IP', '业务IP', '配置', 'IP类型', '系统', '带宽', '需求', '处理过程、结果', '问题难度', '已增加管理卡', '已绑定ARP'));
      fputcsv($fp, $head);
      $i = 0;
      foreach ($datas as $item){
        if($i > 500) {
          $i = 0;
          ob_flush();
          flush();
        }
        $i++;
        $row = $this->exportframeRowData($item);
        fputcsv($fp, $row);
      }
    }
    else if($type == 'work_sheet_ip') {
      $head = array_merge($head_base, array('管理IP', '属性', '增加IP', '停用IP', '带宽', '需求', '处理过程、结果'));
      fputcsv($fp, $head);
      $i = 0;
      foreach ($datas as $item){
        if($i > 500) {
          $i = 0;
          ob_flush();
          flush();
        }
        $i++;
        $row = $this->exportIpRowData($item);
        fputcsv($fp, $row);
      }
    }
    else if ($type == 'work_sheet_switch') {
      $head = array_merge($head_base, array('管理IP', '处理过程、结果'));
      fputcsv($fp, $head);
      $i = 0;
      foreach ($datas as $item){
        if($i > 500) {
          $i = 0;
          ob_flush();
          flush();
        }
        $i++;
        $row = $this->exportSwitchRowData($item);
        fputcsv($fp, $row);
      }
    }
    else if ($type == 'work_sheet_room') {
      $head = array_merge($head_base, array('管理IP', '配置','机柜','位置(U位)','需求','处理过程、结果','下一步操作'));
      fputcsv($fp, $head);
      $i = 0;
      foreach ($datas as $item){
        if($i > 500) {
          $i = 0;
          ob_flush();
          flush();
        }
        $i++;
        $row = $this->exportRoomRowData($item);
        fputcsv($fp, $row);
      }
    }
    else if ($type == 'work_sheet_fault') {
      $head = array_merge($head_base, array('故障IP', '聊天记录','故障现象','故障原因、处理方法','问题类型','问题难度', '故障时间','上报时间', '上报给', '业务恢复时间', '故障恢复时间','工单是否有问题','定位分类是否正确','是否正确处理','是否优质工单','评论说明'));
      fputcsv($fp, $head);
      $i = 0;
      foreach ($datas as $item){
        if($i > 500) {
          $i = 0;
          ob_flush();
          flush();
        }
        $i++;
        $row = $this->exportFaultRowData($item);
        fputcsv($fp, $row);
      }
    }
    else if ($type == 'work_sheet_major_fault') {
      $head = array('工单编号','机房', '影响方向','影响范围','影响程度','故障定位','故障原因', '值班人员发现耗时','监控系统发现耗时','业务影响时间2', '业务故障开始时间', '值班人员','值班人员上报时间','监测系统上报时间','告警方式','业务恢复时间','故障处理完成时间','恢复业务方法','故障处理方法','备注','工单是否有问题','定位分类是否正确','是否正确处理','是否优质工单','评论说明');
      fputcsv($fp, $head);
      $i = 0;
      foreach ($datas as $item){
        if($i > 500) {
          $i = 0;
          ob_flush();
          flush();
        }
        $i++;
        $row = $this->exportMajorfaultRowData($item);
        fputcsv($fp, $row);
      }
    }
    fclose($fp);
  }
  /**
   * 上下架导出行数据
   */
  private function exportframeRowData($item) {
    $option_service = \Drupal::service('worksheet.option');
    $tid_object = $this->typeService->getTypeById($item->tid);
    return array(
      "\t" . $item->code,
      $tid_object->class_name . '-' . $tid_object->operation_name,
      $item->client,
      "\t" . $item->contacts,
      date('Y-m-d H:i:s', $item->created),
      entity_load('user', $item->uid)->label(),
      empty($item->handle_uid) ? '' : entity_load('user', $item->handle_uid)->label(),
      empty($item->last_uid) ? '' : entity_load('user', $item->last_uid)->label(),
      ceil(($item->com_time - $item->begin_time) / 60),
      ceil(($item->end_time - $item->begin_time) / 60),
      $item->manage_ip,
      $item->business_ip,
      $item->product_name,
      empty($item->ip_class) ? '' : $option_service->getOptionByid($item->ip_class)->optin_name,
      empty($item->system) ? '' : $option_service->getOptionByid($item->system)->optin_name,
      $item->broadband,
      $item->requirement,
      $item->handle_info,
      empty($item->problem_difficulty) ? '' : $option_service->getOptionByid($item->problem_difficulty)->optin_name,
      $item->add_card ? '是' : '否',
      $item->add_arp ? '是': '否'
    );
  }
  /**
   * IP or 带宽
   */
  private function exportIpRowData($item) {
    $tid_object = $this->typeService->getTypeById($item->tid);
    return array(
      "\t" . $item->code,
      $tid_object->class_name . '-' . $tid_object->operation_name,
      $item->client,
      "\t" . $item->contacts,
      date('Y-m-d H:i:s', $item->created),
      entity_load('user', $item->uid)->label(),
      empty($item->handle_uid) ? '' : entity_load('user', $item->handle_uid)->label(),
      empty($item->last_uid) ? '' : entity_load('user', $item->last_uid)->label(),
      ceil(($item->com_time - $item->begin_time) / 60),
      ceil(($item->end_time - $item->begin_time) / 60),
      $item->manage_ip,
      $item->property,
      $item->add_ip,
      $item->rm_ip,
      $item->broadband,
      $item->requirement,
      $item->handle_info
    );
  }
  /**
   * 开关机
   */
  private function exportSwitchRowData($item) {
    $tid_object = $this->typeService->getTypeById($item->tid);
    return array(
      "\t" . $item->code,
      $tid_object->class_name . '-' . $tid_object->operation_name,
      $item->client,
      "\t" . $item->contacts,
      date('Y-m-d H:i:s', $item->created),
      entity_load('user', $item->uid)->label(),
      empty($item->handle_uid) ? '' : entity_load('user', $item->handle_uid)->label(),
      empty($item->last_uid) ? '' : entity_load('user', $item->last_uid)->label(),
      ceil(($item->com_time - $item->begin_time) / 60),
      ceil(($item->end_time - $item->begin_time) / 60),
      $item->manage_ip,
      $item->handle_info
    );
  }
  /**
   * 机房事务
   */
  private function exportRoomRowData($item) {
    $tid_object = $this->typeService->getTypeById($item->tid);
    return array(
      "\t" . $item->code,
      $tid_object->class_name . '-' . $tid_object->operation_name,
      $item->client,
      "\t" . $item->contacts,
      date('Y-m-d H:i:s', $item->created),
      entity_load('user', $item->uid)->label(),
      empty($item->handle_uid) ? '' : entity_load('user', $item->handle_uid)->label(),
      empty($item->last_uid) ? '' : entity_load('user', $item->last_uid)->label(),
      ceil(($item->com_time - $item->begin_time) / 60),
      ceil(($item->end_time - $item->begin_time) / 60),
      $item->manage_ip,
      $item->product_name,
      $item->cabinet,
      $item->port,
      $item->requirement,
      $item->handle_info,
      $item->next_step
    );
  }
  /**
   * 故障
   */
  private function exportFaultRowData($item) {
    $tid_object = $this->typeService->getTypeById($item->tid);
    $option_service = \Drupal::service('worksheet.option');
    $problem_types = '';
    if(!empty($item->problem_types)) {
      $problem_types = $option_service->getOptionByid($item->problem_types)->optin_name;
      if(!empty($item->problem_types_child)) {
        $child = $option_service->getOptionByid($item->problem_types_child)->optin_name;
        $problem_types .= '('. $child .')';
      }
    }
    return array(
      "\t" . $item->code,
      $tid_object->class_name . '-' . $tid_object->operation_name,
      $item->client,
      "\t" . $item->contacts,
      date('Y-m-d H:i:s', $item->created),
      entity_load('user', $item->uid)->label(),
      empty($item->handle_uid) ? '' : entity_load('user', $item->handle_uid)->label(),
      empty($item->last_uid) ? '' : entity_load('user', $item->last_uid)->label(),
      ceil(($item->com_time - $item->begin_time) / 60),
      ceil(($item->end_time - $item->begin_time) / 60),
      $item->ip,
      $item->phenomenon,
      $item->handle_info,
      $item->reason,
      $problem_types,
      empty($item->problem_difficulty) ? '' : $option_service->getOptionByid($item->problem_difficulty)->optin_name,
      empty($item->fault_time) ? '' : date('Y-m-d H:i:s', $item->fault_time),
      empty($item->report_time) ? '' : date('Y-m-d H:i:s', $item->report_time),
      empty($item->report_user) ? '' : entity_load('user', $item->report_user)->label(),
      empty($item->buss_recover_time) ? '' : date('Y-m-d H:i:s', $item->buss_recover_time),
      empty($item->fault_recover_time) ? '' : date('Y-m-d H:i:s', $item->fault_recover_time),
      empty($item->if_question)?'正常':'不正常',
      empty($item->if_right)?'正常':'不正常',
      empty($item->if_deal)?'正常':'不正常',
      empty($item->if_quality)?'否':'是',
      $item->comment_note
    );
  }
  /**
   * 重大故障
   */
  private function exportMajorfaultRowData($item) {
    $tid_object = $this->typeService->getTypeById($item->tid);
    $option_service = \Drupal::service('worksheet.option');
    if(!empty($item->room)) {
      $room = $option_service->getOptionByid($item->room)->optin_name;
    }
    if(!empty($item->affect_direction)) {
      $affect_direction = $option_service->getOptionByid($item->affect_direction)->optin_name;
    }
    if(!empty($item->affect_range)) {
      $affect_range = $option_service->getOptionByid($item->affect_range)->optin_name;
    }
    if(!empty($item->affect_level)) {
      $affect_level = $option_service->getOptionByid($item->affect_level)->optin_name;
    }
    if(!empty($item->fault_location)) {
      $fault_location = $option_service->getOptionByid($item->fault_location)->optin_name;
    }
    /*
工单编号','机房', '影响方向','影响范围','影响程度','故障定位','故障原因', '值班人员发现耗时','监控系统发现耗时','业务影响时间2', '业务故障开始时间', '值班人员','值班人员上报时间','监测系统上报时间','告警方式','业务恢复时间','故障处理完成时间','恢复业务方法','故障处理方法','备注
    */
    return array(
      "\t" . $item->code,
      $room,
      $affect_direction,
      $affect_range,
      $affect_level,
      $fault_location,
      $item->reason,
      empty($item->time_consuming) ? '' :$item->time_consuming,
      empty($item->sytime_consuming) ? '' :$item->sytime_consuming,
      $item->affect_time2,
      empty($item->fault_time) ? '' : date('Y-m-d H:i:s', $item->fault_time),
      empty($item->last_uid) ? '' : entity_load('user', $item->last_uid)->label(),
      empty($item->report_time) ? '' : date('Y-m-d H:i:s', $item->report_time),
      empty($item->sy_report_time) ? '' : date('Y-m-d H:i:s', $item->sy_report_time),
      $item->alarm_action,
      empty($item->buss_recover_time) ? '' : date('Y-m-d H:i:s', $item->buss_recover_time),
      empty($item->fault_recover_time) ? '' : date('Y-m-d H:i:s', $item->fault_recover_time),
      $item->recover_method,
      $item->deal_method,
      $item->note,
      empty($item->if_question)?'正常':'不正常',
      empty($item->if_right)?'正常':'不正常',
      empty($item->if_deal)?'正常':'不正常',
      empty($item->if_quality)?'否':'是',
      $item->comment_note
    );
  }
  
  
}
