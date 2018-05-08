<?php

/**
 * @file
 * 报警值设置From
 *
 * Contains \Drupal\qy_wd\Form\AlarmEditForm.
 */

namespace Drupal\qy_wd\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class AlarmEditForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'qy_alarm_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = 0) {
    $db_service= \Drupal::service('qy_wd.db_service');
    $alarm = $db_service->load_alarmById($id);
    if(empty($alarm)) {
      return $this->redirect('admin.wd.alarm');
    }
    $form['max_bps'] = array(
      '#type' => 'number',
      '#title' => '最大报警值',
      '#default_value' => $alarm->max_bps,
      '#min' => 1,
      '#required' => true,
      '#field_suffix' => '(单位:Mbps)'
    );
    $form['min_bps'] = array(
      '#type' => 'number',
      '#title' => '最小报警值',
      '#default_value' => $alarm->min_bps,
      '#min' => 0,
      '#required' => true,
      '#field_suffix' => '(单位:Mbps)'
    );
    $form['delay_time'] = array(
      '#type' => 'number',
      '#title' => '报警延迟时间(秒)',
      '#default_value' => $alarm->delay_time,
      '#min' => 1,
      '#required' => true,
    );
    $form['alarm_id'] = array(
      '#type' => 'value',
      '#value' => $alarm->id
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => '保存'
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $id = $form_state->getValue('alarm_id');
    $max_bps = $form_state->getValue('max_bps');
    $min_bps = $form_state->getValue('min_bps');
    $delay_time = $form_state->getValue('delay_time');
    $db_service= \Drupal::service('qy_wd.db_service');
    $db_service->update_alarm(array(
      'max_bps' => $max_bps,
      'min_bps' => $min_bps,
      'delay_time' => $delay_time
    ), $id);
    drupal_set_message('保存成功');
    $form_state->setRedirectUrl(new Url('admin.wd.alarm'));
  }
}
