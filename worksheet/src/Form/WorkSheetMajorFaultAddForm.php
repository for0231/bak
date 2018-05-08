<?php

/**
 * @file
 * Contains \Drupal\worksheet\Form\WorkSheetMajorFaultAddForm.
 */

namespace Drupal\worksheet\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class WorkSheetMajorFaultAddForm extends ContentEntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $default_code = date('YmdHis').rand(100000,999999);
    $form['code']['widget'][0]['value']['#default_value'] = $default_code;
    unset($form['room']);//机房
    unset($form['affect_direction']);//影响方向
    unset($form['affect_range']);//影响范围
    unset($form['affect_level']);//影响程度
    unset($form['fault_location']);//故障定位
    unset($form['time_consuming']);
    unset($form['report_time']);
    unset($form['sy_report_time']);
    unset($form['alarm_action']);
    unset($form['affect_time1']);
    unset($form['affect_time2']);
    unset($form['reason']);
    unset($form['recover_method']);
    unset($form['deal_method']);
    unset($form['buss_recover_time']);
    unset($form['fault_recover_time']);
    unset($form['sytime_consuming']);
    unset($form['note']);
    unset($form['comment_note']);
    return $form;
  }
  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = '创建工单';
    return $actions;
  }
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    parent::validateForm($form, $form_state);
    $code = $form_state->getValue('code')[0]['value'];
    $entitys = entity_load_multiple_by_properties('work_sheet_base', array('code' => $code));
    if($entitys) {
      $form_state->setErrorByName('code', '工单编号重复');
    }
  }
  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $uid = \Drupal::currentUser()->id();
    $entity->handle_record_info = array(
      'uid' => $uid,
      'time' => REQUEST_TIME,
      'operation_id' => 1,
      'is_abnormal' => 0
    );
    $entity->statistic_record = array(
      array('uid' => $uid, 'event' => 1)
    );
    if($entity->save()) {
      $roles = \Drupal::currentUser()->getRoles();
      if(!in_array('worksheet_operation',$roles)) {
        $uids = \Drupal::service('worksheet.dbservice')->getRoleUser('worksheet_operation');
        $voice = \Drupal::service("voice.voice_server");
        $voice->openVioce('当前系统有一个新工单', $uids);
      }
      drupal_set_message('工单创建成功');
    }
    $form_state->setRedirectUrl(new Url('admin.worksheet.sop'));
  }
}
