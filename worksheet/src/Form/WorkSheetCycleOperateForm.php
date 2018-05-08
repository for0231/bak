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

class WorkSheetCycleOperateForm extends WorkSheetOperateBaseForm {
  //状态对应的可编辑字段
  protected function statusField() {
    $status_field = array(
      15 => array(
        'worksheet_operation' => array('reason','exception')
      ),
    );
    return $status_field;
  }
  //状态对应的按扭
  protected function statusBution() {
    $options = parent::statusBution();
    $options[1]['worksheet_operation'] = array('accept');
    $options[15]['worksheet_operation'] = array('submit','transfer', 'opwait', 'complete');
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
    unset($form['exception']['widget']['#options']['_none']);
    $form['#theme'] = 'cycle_operate_form';
    return $form;
  }

  public function submitComplete(array $form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();
    $reason = rtrim($form_state->getValue('reason')[0]['value']);
    $status = $form_state->getValue('exception')[0]['value'];
    if(!$this->checkStatus($status,$reason)){
      drupal_set_message('异常状态下必须填写处理过程','error');
      return false;
    }
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

  public function checkStatus($status,$reason){
    if( $status == 2 && empty($reason) ){
      return false;
    }
    return true;
  }

}
