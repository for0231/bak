<?php
/**
 * @file
 * Contains \Drupal\worksheet\Form\WorkSheetIpOperateForm.
 */

namespace Drupal\worksheet\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\SafeMarkup;

class WorkSheetIpOperateForm extends WorkSheetOperateBaseForm {
  //状态对应的字段
  protected function statusField() {
    $status_field = array(
      1 => array(
        'worksheet_business' => array('client','contacts','manage_ip','property','add_ip','rm_ip','broadband','requirement')
      ),
      5 => array(
        'worksheet_business' => array('client','contacts','manage_ip','property','add_ip','rm_ip','broadband','requirement')
      ),
      15 => array(
        'worksheet_operation' => array('property','add_ip','rm_ip','broadband','requirement', 'handle_info')
      )
    );
    $entity = $this->entity;
    $status = $entity->get('status')->value;
    $uid = $entity->get('uid')->target_id;
    if($status == 15 && $uid == \Drupal::currentUser()->id()) { //建单人和处理人都是自己则可以编辑
      $status_field[15]['worksheet_operation'][] = 'client';
      $status_field[15]['worksheet_operation'][] = 'manage_ip';
      $status_field[15]['worksheet_operation'][] = 'contacts';
    }
    return $status_field;
  }
  /**
   * 设置交付信息
   */
  protected function deliverInfo() {
    $entity = $this->entity;
    $info = '';
    $client = $entity->get('client')->value;
    $manage_ip = $entity->get('manage_ip')->value;
    $info = $client . '：'. $manage_ip . "\r\n";
    $add_ip = $entity->get('add_ip')->value;
    if(!empty($add_ip)) {
      $info .= "新增IP:\r\n" . $add_ip . "\r\n";
    }
    $rm_ip = $entity->get('rm_ip')->value;
    if(!empty($rm_ip)) {
      $info .= "停用IP:\r\n" . $rm_ip . "\r\n";
    }
    $broadband = $entity->get('broadband')->value;
    if(!empty($broadband)) {
      $info .= "带宽:已经调整为{$broadband}\r\n";
    }
    return $info;
  }
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['#theme'] = 'ip_operate_form';
    return $form;
  }
  /**
   * 重写-修正并转交运维
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function submitEditToOp(array $form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();
    $entity = $this->entity;
    $status = $entity->get('status')->value;
    if($input['op']=='修正并转交运维' && $status==5) {
      $uid = \Drupal::currentUser()->id();
      $entity->set('tid', 0);
      $entity->set('last_uid', 0);
      $handle_uid = $entity->get('handle_uid')->target_id;
      if($handle_uid > 0) {
        $entity->set('status', 15);
      } else {
        $entity->set('status', 10);
      }
      $entity->handle_record_info = array(
        'uid' => $uid,
        'time' => REQUEST_TIME,
        'operation_id' => 4,
        'is_abnormal' => 0
      );
      $entity->save();
      //设置声音
      $uids = \Drupal::service('worksheet.dbservice')->getRoleUser('worksheet_operation');
      $voice = \Drupal::service("voice.voice_server");
      $voice->openVioce('当前系统有一个业务修正工单', $uids);
      drupal_set_message('改正并转交运维操作成功');
    }
    $form_state->setRedirectUrl(new Url('admin.worksheet.sop'));
  }
}
