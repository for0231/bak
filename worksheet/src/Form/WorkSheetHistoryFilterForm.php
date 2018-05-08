<?php

/**
 * @file
 * Contains \Drupal\worksheet\Form\WorkSheetStatisticFilterForm.
 */

namespace Drupal\worksheet\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * 增加IP封停表单类
 */
class WorkSheetHistoryFilterForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'history_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['filters'] = array(
      '#type' => 'details',
      '#title' => '查询条件',
      '#open' => !empty($_SESSION['worksheet_history']['is_open']),
    );
    $form['filters']['keyword'] = array(
      '#type' => 'textfield',
      '#title' => '关键字',
      '#default_value' => empty($_SESSION['worksheet_history']['keyword']) ? '' : $_SESSION['worksheet_history']['keyword'],
    );
    $form['filters']['type'] = array(
      '#type' => 'select',
      '#title' => '工单类型',
      '#options' => array('all' => '-All-') + getEntityType(),
      '#default_value' => empty($_SESSION['worksheet_history']['type']) ? 'all' :  $_SESSION['worksheet_history']['type'],
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
    $form['filters']['comment'] = array(
      '#type' => 'select',
      '#title' => '是否评论',
      '#options' => array(''=>'-None-','2' => '未评论','1' => '已评论',),
      '#default_value' => empty($_SESSION['worksheet_history']['comment']) ? '0' : $_SESSION['worksheet_history']['comment']
    );
    $form['filters']['creater'] = array(
      '#type' => 'select',
      '#title' => '建单人',
      '#options' => array('all' => '-All-') + $creater + $hander,
      '#default_value' => empty($_SESSION['worksheet_history']['creater']) ? 'all' : $_SESSION['worksheet_history']['creater']
    );
    $form['filters']['hander'] = array(
      '#type' => 'select',
      '#title' => '处理人',
      '#options' => array('all' => '-All-') + $hander,
      '#default_value' => empty($_SESSION['worksheet_history']['hander']) ? 'all' : $_SESSION['worksheet_history']['hander']
    );
    $form['filters']['created_begin'] = array(
      '#type' => 'textfield',
      '#title' => '建单时间',
      '#default_value' => empty($_SESSION['worksheet_history']['begin']) ? date('Y-m') . '-01' : $_SESSION['worksheet_history']['begin'] ,
    );
    $form['filters']['created_end'] = array(
      '#type' => 'textfield',
      '#default_value' => empty($_SESSION['worksheet_history']['end']) ? date('Y-m-d') : $_SESSION['worksheet_history']['end']
    );
    $form['filters']['submit'] = array(
      '#type' => 'submit',
      '#value' => '查询'
    );
    if(isset($_SESSION['worksheet_history']['is_open'])){
      $form['filters']['reset'] = array(
        '#type' => 'submit',
        '#value' => '清空',
        '#submit' => array('::resetForm'),
      );
      if($_SESSION['worksheet_history']['type'] != 'all') {
        $form['filters']['export'] = array(
          '#type' => 'link',
          '#title' => '导出',
          '#url' => new Url('admin.worksheet.history.export'),
          '#attributes' => array(
            'class' => array('button')
          )
        );
      }
    }
    return $form;
  }

  public function resetForm(array &$form, FormStateInterface $form_state){
    unset($_SESSION['worksheet_history']);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {   
    $_SESSION['worksheet_history']['keyword'] = $form_state->getValue('keyword');
    $_SESSION['worksheet_history']['type'] = $form_state->getValue('type');
    $_SESSION['worksheet_history']['creater'] = $form_state->getValue('creater');
    $_SESSION['worksheet_history']['hander'] = $form_state->getValue('hander');
    $_SESSION['worksheet_history']['begin'] = $form_state->getValue('created_begin');
    $_SESSION['worksheet_history']['end'] = $form_state->getValue('created_end');
    $_SESSION['worksheet_history']['comment'] = $form_state->getValue('comment');
    $_SESSION['worksheet_history']['is_open'] = true;
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
    $head_base = array('工单编号', '类型', '公司名称', '联系人','建单时间','创建人', '处理人','交接人', '耗时(分)');
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
        $row = $this->exportFaultRowData($item);
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
  
}
