<?php

/**
 * @file
 * Contains \Drupal\worksheet\Form\WorkSheetRoomAddForm.
 */

namespace Drupal\worksheet\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class WorkSheetRoomAddForm extends ContentEntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['tid']['widget']['#default_value'] = '161';
    $form['job_content']['widget']['#ajax'] = array(
      'callback' => array(get_class($this), 'contentDateCallback'),
      'method' => 'html',
      'wrapper' => 'content-date-wrappers'
    );
    $job_content = $form_state->getValue('job_content');
    if(!empty($job_content) && isset($_POST['_drupal_ajax'])) {
      $value = $job_content[0]['value'];
      $config = \Drupal::config('worksheet.settings');
      $content_time = $config->get('room_content_time');
      $form['job_hours']['widget'][0]['value']['#value'] = $content_time[$value];
    }
    $default_code = date('YmdHis').rand(100000,999999);
    $form['code']['widget'][0]['value']['#default_value'] = $default_code;
    $form['requirement']['widget'][0]['value']['#default_value'] = "需求：\r\n\r\n目的：";
    unset($form['handle_info']);
    $form['#theme'] = 'room_add_form';
    return $form;
  }
  /**
   * 回调函数
   */
  public static function contentDateCallback(array $form, FormStateInterface $form_state) {
    return $form['job_hours'];
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
    $year = date('Y', time());
    $month = date('m', time());
    $day = date('d', time());
    $info = \Drupal::service("worksheet.date")->getMonthInfo((int)$year, (int)$month);
    $key = (int)$day;
    $day_info = $info[$key];
    if($day_info['work'] == 1) {
      $exec_time = mktime(15,0,0, $month, $day, $year);
      if(time() <= $exec_time) {
        $entity->set('handle_date', 0);
      } else {
        $entity->set('handle_date', 1);
      }
    } else {
      $entity->set('handle_date', 1);
    }
    $entity->set('job_hours', $form_state->getUserInput()['job_hours'][0]['value']);
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
    $form_state->setRedirectUrl(new Url('admin.worksheet.room.list'));
  }
}
