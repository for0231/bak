<?php

/**
 * @file
 * 报警值设置From
 *
 * Contains \Drupal\qy_jd\Form\AlarmEditForm.
 */

namespace Drupal\qy_jd\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class AlarmEditForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jd_alarm_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = 0) {
    $db_service= \Drupal::service('qy_jd.db_service');
    $alarm = $db_service->load_alarmById($id);
    if(empty($alarm)) {
      $netcom = $db_service->load_netcomById($id);
      if(empty($netcom)) {
        return $this->redirect('admin.jd.alarm');
      }
    }
    $form['max_bps'] = array(
      '#type' => 'number',
      '#title' => '最大报警值',
      '#default_value' => empty($alarm) ? '' : $alarm->max_bps,
      '#min' => 1,
      '#required' => true,
      '#field_suffix' => '(单位:Mbps)'
    );
    $form['min_bps'] = array(
      '#type' => 'number',
      '#title' => '最小报警值',
      '#default_value' => empty($alarm) ? '0' :  $alarm->min_bps,
      '#min' => 0,
      '#required' => true,
      '#field_suffix' => '(单位:Mbps)'
    );
    $form['delay_time'] = array(
      '#type' => 'number',
      '#title' => '报警延迟时间(秒)',
      '#default_value' => empty($alarm) ? '30' : $alarm->delay_time,
      '#min' => 1,
      '#required' => true,
    );
    $form['alarm_id'] = array(
      '#type' => 'value',
      '#value' => $id
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
    $db_service= \Drupal::service('qy_jd.db_service');
    $alarm = $db_service->load_alarmById($id);
    if(empty($alarm)) {
      $db_service->add_alarm(array(
        'id' => $id,
        'max_bps' => $max_bps,
        'min_bps' => $min_bps,
        'delay_time' => $delay_time
      ));
    } else {
      $db_service->update_alarm(array(
        'max_bps' => $max_bps,
        'min_bps' => $min_bps,
        'delay_time' => $delay_time
      ), $id);
    }
    drupal_set_message('保存成功');
    $form_state->setRedirectUrl(new Url('admin.jd.alarm'));
  }
}
