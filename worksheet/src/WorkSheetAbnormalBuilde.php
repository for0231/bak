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
class WorkSheetAbnormalBuilde {
  protected $formBuilder;
  protected $typeService;

  public function __construct() {
    $this->formBuilder = \Drupal::getContainer()->get('form_builder');
    $this->typeService = \Drupal::service('worksheet.type');
  }

  private function filter() {
    $condition = array();
    if(isset($_SESSION['worksheet_abnormal'])) {
      if(!empty($_SESSION['worksheet_abnormal']['keyword'])) {
        $condition['keyword'] = $_SESSION['worksheet_abnormal']['keyword'];
      }
      if(!empty($_SESSION['worksheet_abnormal']['begin'])) {
        $condition['begin'] = strtotime($_SESSION['worksheet_abnormal']['begin']);
      }
      if(!empty($_SESSION['worksheet_abnormal']['end'])) {
        $condition['end'] = strtotime($_SESSION['worksheet_abnormal']['end']) + 24*3600;
      }
      $condition['hander'] = $_SESSION['worksheet_abnormal']['hander'];
      $condition['type'] = $_SESSION['worksheet_abnormal']['type'];
      $condition['creater'] = $_SESSION['worksheet_abnormal']['creater'];
    }
    
    return $condition;
  }
  
  /**
   * 查询数据
   */
  protected function load() {
    $condition = $this->filter();

    $ids = \Drupal::service('worksheet.dbservice')->loadAbnormal($condition);
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
    $row['id'] = $item->get('code')->value;
    $type = $this->typeService->getTypeById($item->get('tid')->value);
    $row['type'] = $type->class_name;
    $row['op_type'] = $type->operation_name;
    $row['ip'] = $item->get('ip')->value;
    $row['client'] = $item->get('client')->value;
    $row['uid'] = $item->createUser();
    $row['hander'] = $item->handleUser();
    $row['last'] = $item->lastUser();
    $begin_time = $item->get('begin_time')->value;
    $end_time = $item->get('com_time')->value;
    $useTiem = $end_time - $begin_time;
    $row['time'] = worksheet_time2string($useTiem);
    $row['op']['data'] = array(
      '#type' => 'operations',
      '#links' => array(
        'info' => array(
          'title' => '详情',
          'url' => new Url('admin.worksheet.sop.detail', array(
            'entity_type' => $item->get('entity_name')->value,
            'wid' => $item->get('wid')->value
          ), array('query'=> array(
            'source' => \Drupal::url('admin.worksheet.abnormal')
          )))
        )
      )
    );
    return $row;
  }

  /**
   * 列表
   */
  public function build() {
    $data =  $this->load();
    $build['filter'] = $this->formBuilder->getForm('Drupal\worksheet\Form\WorkSheetAbnormalFilterForm');
    $build['list'] = array(
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#rows' => array(),
      '#empty' => t('No data.')
    );
 
    foreach($data as $item) {  
      if($row = $this->buildRow($item)) {
        $build['list']['#rows'][$item->id()] = $row;
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
   * 同WorkSheetHistoryBuilde[r]
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
    $datas = \Drupal::service('worksheet.dbservice')->abnormalExportData($conditions);
    if(empty($datas)) {
      fclose($fp);
      exit;
    }
    $db_service = \Drupal::service('worksheet.dbservice');
    $type = $conditions['type'];
    $head_base = array('工单编号', '类型', '操作状态', '责任人', '异常原因','公司名称', '联系人','建单时间','创建人', '处理人','交接人', '耗时(分)', '操作耗时(分)');
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
        $handels = $db_service->loadHandle(array('wid' => $item->wid, 'entity_name'=> 'work_sheet_frame', 'is_abnormal' => 1));
        foreach($handels as $handel) {
          $row = $this->exportframeRowData($item, $handel);
          fputcsv($fp, $row);
        }
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
        $handels = $db_service->loadHandle(array('wid' => $item->wid, 'entity_name'=> 'work_sheet_ip', 'is_abnormal' => 1));
        foreach($handels as $handel) {
          $row = $this->exportIpRowData($item, $handel);
          fputcsv($fp, $row);
        }
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
        $handels = $db_service->loadHandle(array('wid' => $item->wid, 'entity_name'=> 'work_sheet_switch', 'is_abnormal' => 1));
        foreach($handels as $handel) {
          $row = $this->exportSwitchRowData($item, $handel);
          fputcsv($fp, $row);
        }
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
        $handels = $db_service->loadHandle(array('wid' => $item->wid, 'entity_name'=> 'work_sheet_room', 'is_abnormal' => 1));
        foreach($handels as $handel) {
          $row = $this->exportRoomRowData($item, $handel);
          fputcsv($fp, $row);
        }
      }
    }
    else if ($type == 'work_sheet_fault') {
      $head = array_merge($head_base, array('故障IP', '聊天记录','故障现象','故障原因、处理方法','问题类型','问题难度', '故障时间','上报时间', '上报给', '业务恢复时间', '故障恢复时间'));
      fputcsv($fp, $head);
      $i = 0;
      foreach ($datas as $item){
        if($i > 500) {
          $i = 0;
          ob_flush();
          flush();
        }
        $i++;
        $handels = $db_service->loadHandle(array('wid' => $item->wid, 'entity_name'=> 'work_sheet_fault', 'is_abnormal' => 1));
        foreach($handels as $handel) {
          $row = $this->exportFaultRowData($item, $handel);
        }
        fputcsv($fp, $row);
      }
    }
    else if ($type == 'work_sheet_major_fault') {
      $head = array('工单编号', '操作状态', '责任人', '异常原因','故障原因','故障时间','值班人员上报时间', '系统上报时间', '业务恢复时间', '故障恢复时间','恢复业务方法','故障处理方法');
      fputcsv($fp, $head);
      $i = 0;
      foreach ($datas as $item){
        if($i > 500) {
          $i = 0;
          ob_flush();
          flush();
        }
        $i++;
        $handels = $db_service->loadHandle(array('wid' => $item->wid, 'entity_name'=> 'work_sheet_major_fault', 'is_abnormal' => 1));
        foreach($handels as $handel) {
          $row = $this->exportMajorFaultRowData($item, $handel);
        }
        fputcsv($fp, $row);
      }
    }
    fclose($fp);
  }
  /**
   * 上下架导出行数据
   */
  private function exportframeRowData($item, $handel) {
    $option_service = \Drupal::service('worksheet.option');
    $tid_object = $this->typeService->getTypeById($item->tid);
    return array(
      "\t" . $item->code,
      $tid_object->class_name . '-' . $tid_object->operation_name,
      $handel->operation,
      empty($handel->person_liable) ? '' : entity_load('user', $handel->person_liable)->label(),
      $handel->reason,
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
  private function exportIpRowData($item, $handel) {
    $tid_object = $this->typeService->getTypeById($item->tid);

    return array(
      "\t" . $item->code,
      $tid_object->class_name . '-' . $tid_object->operation_name,
      $handel->operation,
      empty($handel->person_liable) ? '' : entity_load('user', $handel->person_liable)->label(),
      $handel->reason,
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
  private function exportSwitchRowData($item, $handel) {
    $tid_object = $this->typeService->getTypeById($item->tid);

    return array(
      "\t" . $item->code,
      $tid_object->class_name . '-' . $tid_object->operation_name,
      $handel->operation,
      empty($handel->person_liable) ? '' : entity_load('user', $handel->person_liable)->label(),
      $handel->reason,
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
  private function exportRoomRowData($item, $handel) {
    $tid_object = $this->typeService->getTypeById($item->tid);

    return array(
      "\t" . $item->code,
      $tid_object->class_name . '-' . $tid_object->operation_name,
      $handel->operation,
      empty($handel->person_liable) ? '' : entity_load('user', $handel->person_liable)->label(),
      $handel->reason,
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
  private function exportFaultRowData($item, $handel) {
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
      $handel->operation,
      empty($handel->person_liable) ? '' : entity_load('user', $handel->person_liable)->label(),
      $handel->reason,
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
      empty($item->fault_recover_time) ? '' : date('Y-m-d H:i:s', $item->fault_recover_time)
    );
  }
  /**
   *重大故障工单
   */
  private function exportMajorFaultRowData($item, $handel) {
    return array(
      "\t" . $item->code,
      $handel->operation,
      empty($handel->person_liable) ? '' : entity_load('user', $handel->person_liable)->label(),
      $handel->reason,
      $item->reason,
      empty($item->fault_time) ? '' : date('Y-m-d H:i:s', $item->fault_time),
      empty($item->report_time) ? '' : date('Y-m-d H:i:s', $item->report_time),
      empty($item->sy_report_time) ? '' : date('Y-m-d H:i:s', $item->sy_report_time),
      empty($item->buss_recover_time) ? '' : date('Y-m-d H:i:s', $item->buss_recover_time),
      empty($item->fault_recover_time) ? '' : date('Y-m-d H:i:s', $item->fault_recover_time),
      $item->recover_method,
      $item->deal_method
    );
  }
}
