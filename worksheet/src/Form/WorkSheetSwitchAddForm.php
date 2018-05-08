<?php

/**
 * @file
 * Contains \Drupal\worksheet\Form\WorkSheetSwitchAddForm.
 */

namespace Drupal\worksheet\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class WorkSheetSwitchAddForm extends ContentEntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $default_code = date('YmdHis').rand(100000,999999);
    $form['code']['widget'][0]['value']['#default_value'] = $default_code;
    $form['manage_ips'] = array(
      '#type' => 'textarea',
      '#title' => '管理IP',
      '#required' => true,
      '#description' => '一行只能输入一个IP',
      '#weight' => 11
    );
    unset($form['manage_ip']);  
    unset($form['handle_info']);
    $form['#theme'] = 'switch_add_form';
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
    $ips = $form_state->getValue('manage_ips');
    $ip_arrs = explode("\r\n", $ips);
    $uid = \Drupal::currentUser()->id();
    $code = $entity->get('code')->value;
    $roles = \Drupal::currentUser()->getRoles();
    $uids = \Drupal::service('worksheet.dbservice')->getRoleUser('worksheet_operation');
    foreach($ip_arrs as $ip) {
      if(empty(trim($ip))) {
        continue;
      }
      $duplicate = $entity->createDuplicate();
      $duplicate->set('code', $code);
      $duplicate->set('manage_ip', $ip);
      $duplicate->handle_record_info = array(
        'uid' => $uid,
        'time' => REQUEST_TIME,
        'operation_id' => 1,
        'is_abnormal' => 0
      );
      $duplicate->statistic_record = array(
        array('uid' => $uid, 'event' => 1)
      );
      if($duplicate->save()) {
        if(!in_array('worksheet_operation', $roles)) {
          $voice = \Drupal::service("voice.voice_server");
          $voice->openVioce('当前系统有一个新工单', $uids);
        }
      }
      $code = date('YmdHis').rand(100000,999999);
    }
    drupal_set_message('工单创建成功');
    $form_state->setRedirectUrl(new Url('admin.worksheet.sop'));
  }
}
