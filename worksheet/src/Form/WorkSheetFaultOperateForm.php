<?php

/**
 * @file
 * Contains \Drupal\worksheet\Form\WorkSheetFaultOperateForm.
 */

namespace Drupal\worksheet\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Datetime\DrupalDateTime;

class WorkSheetFaultOperateForm extends WorkSheetOperateBaseForm {
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
        'worksheet_operation' => array('phenomenon','handle_info','reason', 'problem_types', 'problem_difficulty','fault_time', 'report_time', 'report_user', 'buss_recover_time', 'fault_recover_time')
      )
    );
    $entity = $this->entity;
    $status = $entity->get('status')->value;
    $uid = $entity->get('uid')->target_id;
    if($status == 15 && $uid == \Drupal::currentUser()->id()) { //建单人和处理人都是自己则可以编辑
      $status_field[15]['worksheet_operation'][] = 'client';
      $status_field[15]['worksheet_operation'][] = 'tid';
      $status_field[15]['worksheet_operation'][] = 'ip';
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
    $form['problem_types']['widget']['#ajax'] = array(
      'callback' => array(get_class($this), 'operateItem'),
      'wrapper' => 'child_item_wrapper',
      'method' => 'html'
    );
    $form['problem_types']['child_item'] = array(
      '#type' => 'container',
      '#id' => 'child_item_wrapper'
    );
    $submit_value = $form_state->getValue('problem_types');
    $problem_types = empty($submit_value) ? $entity->get('problem_types')->value : $submit_value[0]['value'];
    if(empty($problem_types)) {
      $form['problem_types']['child_item']['problem_types_child'] = array();
    } else {
      $service = \Drupal::service('worksheet.option');
      $options = $service->getOptions('problem_type', $problem_types);
      if(empty($options)) {
        $options['_none'] = '- 无 -';
      }
      $form['problem_types']['child_item']['problem_types_child'] = array(
        '#type' => 'select',
        '#options' => $options,
        '#default_value' => $entity->get('problem_types_child')->value
      );
    }
    unset($form['problem_difficulty']['widget']['#options']['_none']);
    if($pd = $entity->get('problem_difficulty')->value) {
      $form['problem_difficulty']['widget']['#default_value'] = $pd;
    } else {
      $form['problem_difficulty']['widget']['#default_value'] = 20;
    }
    $form['#theme'] = 'fault_operate_form';
    return $form;
  }

  /**
   * 问题类型回调
   */
  public static function operateItem(array $form, FormStateInterface $form_state) {
    return $form['problem_types']['child_item']['problem_types_child'];
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
    $problem_types = $form_state->getValue('problem_types');
    if(empty($problem_types)) {
      $form_state->setErrorByName('problem_types', '请选择问题类型');
    }
    $problem_difficulty = $form_state->getValue('problem_difficulty');
    if(empty($problem_difficulty)) {
      $form_state->setErrorByName('problem_difficulty', '请选择问题难度');
    }
    $phenomenon = $form_state->getValue('phenomenon')[0]['value'];
    if(empty(trim($phenomenon))) {
      $form_state->setErrorByName('phenomenon', '聊天记录不能为空');
    }
    $handle_info = $form_state->getValue('handle_info')[0]['value'];
    if(empty(trim($handle_info))) {
      $form_state->setErrorByName('handle_info', '故障现象不能为空');
    }
    $reason = $form_state->getValue('reason')[0]['value'];
    if(empty(trim($reason))) {
      $form_state->setErrorByName('reason', '故障原因、处理方法不能为空');
    }
    $type = $form_state->getValue('tid')[0]['value'];
    if($type == '200' || $type == '210' || $type == '220') {
      if(empty($form_state->getValue('fault_time')[0]['value'])) {
        $form_state->setErrorByName('fault_time', '故障时间不能为空');
      }
      if(empty($form_state->getValue('buss_recover_time')[0]['value'])) {
        $form_state->setErrorByName('buss_recover_time', '业务恢复时间不能为空');
      }
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
