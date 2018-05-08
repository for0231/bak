<?php
/**
 * @file
 * Contains \Drupal\worksheet\Form\WorkSheetLogisticsOperateForm.
 */

namespace Drupal\worksheet\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\SafeMarkup;

class WorkSheetLogisticsOperateForm extends WorkSheetOperateBaseForm {
  //状态对应的字段
  protected function statusField() {
    $status_field = array(
      1 => array(
        'worksheet_business' => array('client','contacts','order_code','logistics_company','send_time','estimate_time','requirement', 'next_step')
      ),
      5 => array(
        'worksheet_business' => array('client','contacts','order_code','logistics_company','send_time','estimate_time','requirement', 'next_step')
      ),
      15 => array(
        'worksheet_operation' => array('order_code','logistics_company','send_time','estimate_time','requirement', 'next_step', 'remember', 'op_dept')
      )
    );
    $entity = $this->entity;
    $status = $entity->get('status')->value;
    $uid = $entity->get('uid')->target_id;
    if($status == 15 && $uid == \Drupal::currentUser()->id()) { //建单人和处理人都是自己则可以编辑
      $status_field[15]['worksheet_operation'][] = 'client';
      $status_field[15]['worksheet_operation'][] = 'contacts';
    }
    return $status_field;
  }
  
  //状态对应的按扭
  protected function statusBution() {
    $options = parent::statusBution();
    $options[15]['worksheet_operation'] = array('submit','abnormal_audit','transfer', 'tobusiness', 'opwait');
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
    $form['#theme'] = 'logistics_operate_form';
    return $form;
  }
  /**
   *完成
   */
  public function submitComplete(array $form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();
    $entity = $this->entity;
    $status = $entity->get('status')->value;
    if($input['op']=='完成' &&  $status ==30) {
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
      $entity->save();
      drupal_set_message('完成操作成功');
    }
    $form_state->setRedirectUrl(new Url('admin.worksheet.logistics.list'));
  }
}
