<?php

/**
 * @file
 * Contains \Drupal\worksheet\Form\WorkSheetMajorFaultOperateForm.
 */

namespace Drupal\worksheet\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Datetime\DrupalDateTime;

class WorkSheetMajorFaultOperateForm extends WorkSheetOperateBaseForm {
  //状态对应的字段
  protected function statusField() {
    $status_field = array(
      1 => array(
        'worksheet_business' => array('client','contacts','tid','ip','phenomenon')
      ),
      5 => array(
        'worksheet_business' => array('client','contacts','tid','ip','phenomenon')
      ),
      15 => array(
        'worksheet_operation' => array('room','affect_direction','affect_range', 'affect_level', 'fault_location', 'recover_method', 'reason','deal_method','note','report_time','sy_report_time','alarm_action','buss_recover_time','fault_recover_time','fault_time')
      )
    );
    $entity = $this->entity;
    $status = $entity->get('status')->value;
    $uid = $entity->get('uid')->target_id;
    if($status == 15 && $uid == \Drupal::currentUser()->id()) { //建单人和处理人都是自己则可以编辑
      $status_field[15]['worksheet_operation'][] = 'client';
      $status_field[15]['worksheet_operation'][] = 'tid';
      $status_field[15]['worksheet_operation'][] = 'contacts';
    }
    return $status_field;
  }
  //状态对应的按扭
  protected function statusBution() {
    $options = parent::statusBution();
    $options[15]['worksheet_operation'] = array('submit','abnormal_audit','transfer', 'tobusiness', 'opwait', 'complete');
    $options[30]['worksheet_business'] = array('complete', 'abnormal_quality', 'buswait');
    return $options;
  }
  /**
   * 设置交付信息
   */
  protected function deliverInfo() {
    return '';
  }
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $entity = $this->entity;
    $form['#theme'] = 'majorfault_operate_form';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $entity = $this->entity;
    $status = $entity->get('status')->value;
    if($status == 15) {
      $actions['complete']['#submit'] = array('::submitForm', '::submitComplete'); 
    }
    return $actions;
  }
  /**
   * 交业务验证
   */
  public function validateToBusiness(array &$form, FormStateInterface $form_state) {
    $room = $form_state->getValue('room');
    if(empty($room)) {
      $form_state->setErrorByName('room', '请选择机房');
    }
    $affect_direction = $form_state->getValue('affect_direction');
    if(empty($affect_direction)) {
      $form_state->setErrorByName('affect_direction', '请选择影响方向');
    }
    $affect_level = $form_state->getValue('affect_level');
    if(empty($affect_level)) {
      $form_state->setErrorByName('affect_level', '请选择影响程度');
    }
    $affect_range = $form_state->getValue('affect_range');
    if(empty($affect_range)) {
      $form_state->setErrorByName('affect_range', '请选择影响范围');
    }
    $fault_location = $form_state->getValue('fault_location');
    if(empty($fault_location)) {
      $form_state->setErrorByName('fault_location', '请选择故障定位');
    }
    if(empty($form_state->getValue('buss_recover_time')[0]['value'])) {
      $form_state->setErrorByName('buss_recover_time', '业务恢复时间不能为空');
    }
    if(empty($form_state->getValue('reason')[0]['value'])) {
      $form_state->setErrorByName('reason', '故障原因不能为空');
    }
    if(empty($form_state->getValue('recover_method')[0]['value'])) {
      $form_state->setErrorByName('recover_method', '恢复业务方法不能为空');
    }
    if(empty($form_state->getValue('deal_method')[0]['value'])) {
      $form_state->setErrorByName('deal_method', '业务处理方法不能为空');
    }
  }
  /**
   *完成
   */
  public function submitComplete(array $form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();
    $entity = $this->entity;
    $status = $entity->get('status')->value;
    if($input['op']=='完成' && ($status==15 || $status ==30)) {
      $uid = \Drupal::currentUser()->id();
      $entity->set('handle_uid', $uid);
      $entity->set('status', 45);
      $entity->set('com_time', REQUEST_TIME);
      $entity->handle_record_info = array(
        'uid' => $uid,
        'time' => REQUEST_TIME,
        'operation_id' => 12,
        'is_abnormal' => 0
      );
      if($status==15) {
        $entity->set('end_time', REQUEST_TIME);
        $entity->statistic_record = array(
          array('uid' => $uid, 'event' => 3, 'user_dept' => 'worksheet_operation')
        );
      }
      //计算业务影响时间和业务影响时间2,单位设置为秒
      //业务恢复时间
      $buss_recover_time =strtotime($form_state->getValue('buss_recover_time')[0]['value']);
      $fault_time = strtotime($form_state->getValue('fault_time')[0]['value']);
      //转化为时间戳
      $affect_time1 = ($buss_recover_time-$fault_time)/60;
      $affect_range = $form_state->getValue('affect_range')[0]['value'];
      if($affect_range == 59){
        $affect_range= 0.25;
      }elseif($affect_range ==60){
        $affect_range= 0.5;
      }elseif($affect_range == 61){
        $affect_range= 0.75;
      }else{
        $affect_range= 1;
      }
      //计算值班人员发现耗时
      $report_time = strtotime($form_state->getValue('report_time')[0]['value']);
      $time_consuming = ($report_time-$fault_time)/60;
      //计算监控系统发现耗时
      $sy_report_time= strtotime($form_state->getValue('sy_report_time')[0]['value']);
      $sytime_consuming = ($sy_report_time-$fault_time)/60;
      $entity->set('sytime_consuming',$sytime_consuming);
      $entity->set('affect_time1',$affect_time1);
      $entity->set('affect_time2',$affect_time1*$affect_range);
      $entity->set('time_consuming',$time_consuming);
      $entity->save();
      drupal_set_message('完成操作成功');
    }
    $form_state->setRedirectUrl(new Url('admin.worksheet.sop'));
  }
  /**
   * 完成时验证
   */
  public function validateComplete(array &$form, FormStateInterface $form_state) {
    $this->validateToBusiness($form, $form_state);
  }
}
