<?php

/**
 * @file
 * Contains file
 * \Drupal\worksheet\WorkSheetListBuilde.
 */

namespace Drupal\worksheet;

use Drupal\Core\Url;
use Drupal\Component\Utility\SafeMarkup;
/**
 *
 */
class WorkSheetDetailBuilde {

  protected $entity_type;
  protected $wid;
  protected $typeService;
  protected $formBuilder;

  public function __construct($entity_type, $wid){
    $this->entity_type = $entity_type;
    $this->wid = $wid;
    $this->typeService = \Drupal::service('worksheet.type');
    $this->formBuilder = \Drupal::getContainer()->get('form_builder');
  }

  protected function buildHeader() {
    $header['wid'] = '工单编号';
    $header['status'] = '工单状态';
    $header['op_type'] = '操作类型';
    $header['type'] = '工单类型';
    $header['ip'] = 'IP';
    $header['client'] = '公司名称';
    $header['person'] = '责任人';
    $header['op_status'] = '操作状态';
    $header['op_user'] = '操作人';
    $header['op_date'] = '操作时间';
    $header['op'] = '处理';
    return $header;
  }

  protected function buildRow($item) {
    $row = array();
    $wdata = unserialize($item->get('work_sheet')->value);
    $wid = $wdata->get('code')->value;
    $type = $this->typeService->getTypeById($wdata->get('tid')->value);
    //构造详情
    $detail = array();
    $detail['code'] = $wid;
    $detail['person'] = $item->liableUser();
    $detail['status'] = getStatus()[$wdata->get('status')->value];
    $detail['type'] = empty($type) ? '' : $type->class_name;
    $ip = '';
    if($this->entity_type == 'work_sheet_fault' || $this->entity_type == 'work_sheet_cycle') {
      $ip = $wdata->get('ip')->value;
    } else if ($this->entity_type == 'work_sheet_logistics'){
      $ip = '快递物流';
    }else if ($this->entity_type == 'work_sheet_major_fault'){
      $ip = '重大故障';
    } else {
      $ip = $wdata->get('manage_ip')->value;
    }
    $detail['ip'] = $ip;
    $detail['client'] = $wdata->get('client')->value;;
    $detail['created'] = date('Y-m-d H:i:s', $wdata->get('created')->value);
    $detail['abnormal'] = $item->get('reason')->value;
    $abnormal_res = '';
    $noShow = array('wid', 'status', 'created', 'uuid', 'tid', 'completed', 'begin_time', 'end_time', 'abnormal_exist', 'com_time', 'problem_types_child','unique_cycle_key');
    $fields = $wdata->getFields();
    foreach($fields as $k => $field){
      if(in_array($k, $noShow)) {
        continue;
      }
      $values = $field->getValue();
      if(!isset($values[0])) {
        continue;
      }
      $field_label = $field->getFieldDefinition()->getLabel();
      $field_value = '';
      if(isset($values[0]['target_id'])){
        if($k == 'uid') {
          $field_value = $wdata->createUser();
        } else if($k == 'handle_uid') {
          $field_value = $wdata->handleUser();
        } else if ($k == 'last_uid') {
          $field_value = $wdata->lastUser();
        } else {
          $field_value = $wdata->get($k)->entity->label();
        }
      } else {
        $options = array('problem_types', 'problem_difficulty', 'ip_class', 'system', 'op_dept', 'job_content');
        $options2 = array('room', 'affect_direction', 'affect_range', 'affect_level','fault_location');
        if(in_array($k, $options)) {
          $service = \Drupal::service('worksheet.option');
          $option = $service->getOptionByid($values[0]['value']);
          $field_value = $option->optin_name;
          if($k == 'problem_types') {
            $child = $wdata->get('problem_types_child')->value;
            if($child && $child != '_none') {
              $option = $service->getOptionByid($child);
              $field_value .= '('. $option->optin_name .')';
            }
          }
        }
        else if(in_array($k, array('fault_time', 'report_time', 'buss_recover_time', 'fault_recover_time','sy_report_time'))) {
          if(!empty($values[0]['value'])) {
            $field_value = date('Y-m-d H:i:s', $values[0]['value']);
          } else {
            $field_value = '';
          }
        }
        else if(in_array($k,$options2)) {
          if(!empty($values[0]['value'])){
            $service = \Drupal::service('worksheet.option');
            $option = $service->getOptionByid($values[0]['value']);
            $field_value = $option->optin_name;
          }else{
            $field_value = '';
          }
        }
        else {
          if($k == 'add_card') {
            $field_value = $values[0]['value'] ? '是' : '否';
            if($wdata->get('tid')->value == 140) {
              $field_label = '删除管理卡';
            }
          } else if ($k == 'add_arp') {
            $field_value = $values[0]['value'] ? '是' : '否';
            if($wdata->get('tid')->value == 140) {
              $field_label = '绑定ARP';
            }
          } else if ($k == 'handle_date') {
            $field_value = $values[0]['value'] == 0 ? '当日处理': '下一个工作日处理';
          }else if ($k == 'if_question') {
            $field_value = $values[0]['value'] == 0 ? '正常': '异常';
          } else if ($k == 'if_right') {
            $field_value = $values[0]['value'] == 0 ? '正常': '异常';
          } else if ($k == 'if_deal') {
            $field_value = $values[0]['value'] == 0 ? '正常': '异常';
          } else if ($k == 'if_quality') {
            $field_value = $values[0]['value'] == 0 ? '不是': '是';
          }
          else {
            $field_value = $values[0]['value'];
          }
        }
      }
      $abnormal_res .= '【'. $field_label .'】' . $field_value . "\r\n";
    }
    $detail['abnormal_res'] = $abnormal_res;
    //行数据
    $row['wid'] = SafeMarkup::format($wid. '<span class="row-detail" style="display:none">'. json_encode($detail) .'</span>', array());
    $row['status'] = getStatus()[$wdata->get('status')->value];
    if(empty($type)) {
      $row['op_type'] = '';
      $row['type'] = '';
    } else {
      $row['op_type'] = $type->operation_name;
      $row['type'] = $type->class_name; 
    }
    $row['ip'] = $ip;
    $row['client'] = $wdata->get('client')->value;
    $row['person'] = $item->liableUser();
    $row['op_status'] = $item->get('operation')->value;
    $row['op_user'] = $item->get('uid')->entity->label();
    $row['op_date'] = date('Y-m-d H:i:s', $item->get('time')->value);
    $op = $item->get('operation_id')->value;
    if($item->get('is_abnormal')->value == 1){
      $row['op']['data'] = array(
        '#type' => 'operations',
        '#links' => array(
          'cancl' => array(
            'title' => '删除异常',
            'url' => new Url('admin.worksheet.abnormal.cancel', array('work_sheet_handle' => $item->id()))
          )
        )
      );
    } else {
      $row['op'] = '';
    }
    return $row;
  }
  /**
   * 列表
   */
  public function build() {
    $storage = \Drupal::entityManager()->getStorage('work_sheet_handle');
    $entity_query = $storage->getBaseQuery();
    $entity_query->condition('wid', $this->wid);
    $entity_query->condition('entity_name', $this->entity_type);
    $result = $entity_query->execute()->fetchCol();
    $data = array();
    if($result) {
      krsort($result);
      $data = $storage->loadMultiple($result);
    }
    $build['list'] = array(
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#rows' => array(),
      '#empty' => t('No data.'),
      '#attributes' => array('id'=>'tanchuang'),
      '#attached' => array(
        'library' => array('worksheet/drupal.work_sheet_history')
      )
    );
    foreach($data as $item) {
      if($row = $this->buildRow($item)) {
        $build['list']['#rows'][$item->id()] = $row;
      }
    }
    $build['code'] = array(
      '#type' => 'textfield',
      '#title' => '工单编号',
      '#default_value' => '',
      '#attributes' => array('id'=>'wid_code', 'disabled'=> 'disabled'),
    );
    $build['status'] = array(
      '#type' => 'textfield',
      '#title' => '工单状态',
      '#default_value' => '',
      '#attributes' => array('id'=>'status', 'disabled'=> 'disabled')
    );
    $build['type'] = array(
      '#type' => 'textfield',
      '#title' => '工单类型',
      '#default_value' => '',
      '#attributes' => array('id'=>'type', 'disabled'=> 'disabled')
    );
    $build['ip'] = array(
      '#type' => 'textfield',
      '#title' => '管理IP',
      '#default_value' => '',
      '#attributes' => array('id'=>'ip', 'disabled'=> 'disabled')
    );
    $build['client'] = array(
      '#type' => 'textfield',
      '#title' => '公司名称',
      '#attributes' => array('id'=>'client', 'disabled'=> 'disabled')
    );
    $build['created'] = array(
      '#type' => 'textfield',
      '#title' => '建单时间',
      '#attributes' => array('id'=>'created', 'disabled'=> 'disabled')
    );
    $build['abnormal'] = array(
      '#type' => 'textfield',
      '#title' => '异常原因',
      '#attributes' => array('id'=>'abnormal', 'disabled'=> 'disabled')
    );
    $build['person'] = array(
      '#type' => 'textfield',
      '#title' => '责任人',
      '#default_value' => '',
      '#attributes' => array('id'=>'person', 'disabled'=> 'disabled')
    );
    $build['abnormal_res'] = array(
      '#type' => 'textarea',
      '#title' => '异常工单结果',
      '#value' => '',
      '#attributes' => array('id'=>'abnormal_res', 'disabled'=> 'disabled')
    );
    if ($this->entity_type == 'work_sheet_fault' || $this->entity_type == 'work_sheet_major_fault') {
      $build['comment'] = $this->formBuilder->getForm('Drupal\worksheet\Form\CommentForm',$this->wid,$this->entity_type);
    } else {
      $build['comment'] = array('#markup' =>'');
    }
    $build['button'] = array(
      '#type' => 'link',
      '#title' => '返回',
      '#url' => new Url('admin.worksheet.history',array('page'=> empty($_GET['page'])?0:$_GET['page'])),
      '#attributes' => array(
        'class' => array('button')
      )
    );
          
    $build['#theme'] = 'worksheet_detail';
    return $build;
  }
}
