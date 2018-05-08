<?php

/**
 * @file
 * Contains \Drupal\worksheet\Form\WorkSheetFrameAddForm.
 */

namespace Drupal\worksheet\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class WorkSheetFrameAddForm extends ContentEntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $default_code = date('YmdHis').rand(100000,999999);
    $form['code']['widget'][0]['value']['#default_value'] = $default_code;
    $form['business_ip']['widget']['0']['value']['#states'] = array(
      'disabled' => array(
        ':input[name="tid"]' => array('value' => '120')
      )
    );
    $form['ip_class']['widget']['#states'] = array(
      'disabled' => array(
        ':input[name="tid"]' => array('value' => '120'),
      )
    );
    $form['product_name']['widget']['0']['value']['#states'] = array(
      'disabled' => array(
        ':input[name="tid"]' => array('value' => '120')
      )
    );
    $form['broadband']['widget']['0']['value']['#states'] = array(
      'disabled' => array(
        ':input[name="tid"]' => array('value' => '120')
      )
    );
    $form['system']['widget']['#states'] = array(
      'disabled' => array(
        ':input[name="tid"]' => array('value' => '140')
      )
    );
    unset($form['handle_info']);
    unset($form['problem_difficulty']);
    unset($form['add_card']);
    unset($form['add_arp']);
    $form['#theme'] = 'frame_add_form';
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
    if(empty($form_state->getValue('tid'))) {
      return;
    }
    $tid = $form_state->getValue('tid')[0]['value'];
    if($tid == 100 || $tid == 110) {
      $business_ip = $form_state->getValue('business_ip')[0]['value'];
      if(empty($business_ip)) {
        $form_state->setErrorByName('business_ip', '业务Ip不能为空');
      }
      $ip_class = $form_state->getValue('ip_class');
      if(empty($ip_class)) {
        $form_state->setErrorByName('ip_class', 'IP类型不能为空');
      }
    }
  }
  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    if($entity->get('tid')->value == 120) { //服务器重装
      $entity->set('broadband', '');
    }
    $uid = \Drupal::currentUser()->id();
    $entity->handle_record_info = array(
      'uid' => $uid,
      'time' => REQUEST_TIME,
      'operation_id' => 1,
      'is_abnormal' => 0
    );
    $entity->statistic_record = array(
      array('uid' => $uid,'event' => 1)
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
