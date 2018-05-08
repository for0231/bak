<?php
/**
 * @file
 * Contains \Drupal\worksheet\Form\WorkSheetSwitchOperateForm.
 */

namespace Drupal\worksheet\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\SafeMarkup;

class WorkSheetSwitchOperateForm extends WorkSheetOperateBaseForm {
  //状态对应的字段
  protected function statusField() {
    $status_field = array(
      1 => array(
       'worksheet_business' => array('tid','manage_ip','client', 'contacts')
      ),
      5 => array(
       'worksheet_business' => array('tid','manage_ip','client', 'contacts')
      ),
      15 => array(
        'worksheet_operation' => array('handle_info')
      )
    );
    $entity = $this->entity;
    $status = $entity->get('status')->value;
    $uid = $entity->get('uid')->target_id;
    if($status == 15 && $uid == \Drupal::currentUser()->id()) { //建单人和处理人都是自己则可以编辑
      $status_field[15]['worksheet_operation'][] = 'tid';
      $status_field[15]['worksheet_operation'][] = 'client';
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
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['#theme'] = 'switch_operate_form';
    return $form;
  }
}
