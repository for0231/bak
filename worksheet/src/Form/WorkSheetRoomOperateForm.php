<?php

/**
 * @file
 * Contains \Drupal\worksheet\Form\WorkSheetRoomOperateForm.
 */

namespace Drupal\worksheet\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\SafeMarkup;

class WorkSheetRoomOperateForm extends WorkSheetOperateBaseForm {
  //状态对应的字段
  protected function statusField() {
    $status_field = array(
      1 => array(
        'worksheet_business'=> array('client','contacts','manage_ip','product_name', 'cabinet', 'port', 'requirement','next_step', 'op_dept', 'job_content', 'job_hours')
      ),
      5 => array(
        'worksheet_business'=> array('client','contacts','manage_ip','product_name', 'cabinet', 'port', 'requirement','next_step', 'op_dept', 'job_content', 'job_hours')
      ),
      15 => array(
        'worksheet_operation' => array('product_name','cabinet', 'port','requirement', 'handle_info', 'next_step', 'op_dept', 'job_content', 'job_hours', 'handle_date', 'remember')
      )
    );
    $entity = $this->entity;
    $status = $entity->get('status')->value;
    $uid = $entity->get('uid')->target_id;
    if($status == 15 && $uid == \Drupal::currentUser()->id()) { //建单人和处理人都是自己则可以编辑
      $status_field[15]['worksheet_operation'][] = 'client';
      $status_field[15]['worksheet_operation'][] = 'tid';
      $status_field[15]['worksheet_operation'][] = 'manage_ip';
      $status_field[15]['worksheet_operation'][] = 'contacts';
    }
    return $status_field;
  }
  //状态对应的按扭
  protected function statusBution() {
    $actions = parent::statusBution();
    $actions[35] = array('worksheet_business' => array('complete', 'abnormal_quality'));
    $actions[40]['worksheet_business'] = array('complete', 'abnormal_quality');
    return $actions;
  }
  /**
   * 设置交付信息
   */
  protected function deliverInfo() {
    return '';
  }
  /**
   * 重写跳转页
   */
  protected function redirectUrl() {
    if(isset($_GET['source']) && $_GET['source'] == '/admin/worksheet/room/list') {
      return new Url('admin.worksheet.room.list');
    }
    return new Url('admin.worksheet.sop');
  }
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    if($relation_code = $this->entity->get('relation_code')->value) {
      $form['more_link'] = array(
        '#type' => 'link',
        '#title' => '点击进入',
        '#url' => new Url('admin.worksheet.room.relation.sop', array('code' => $relation_code), array('attributes' => array('target' => '_blank')))
      );
    }
    unset($form['handle_date']['widget']['#options']['_none']);
    $form['#theme'] = 'room_operate_form';
    return $form;
  }
}