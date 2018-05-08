<?php

/**
 * @file
 * Contains \Drupal\qy_jd\Form\SysConfigureForm.
 */

namespace Drupal\qy_jd\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a system configure form.
 */
class SysConfigureForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jd_system_configure_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('qy_jd.settings');
    $form['min_bps'] = array(
      '#type' => 'number',
      '#title' => '流量过滤值',
      '#default_value' => $config->get('min_bps'),
      '#step' => 0.1,
      '#min' => 0.1,
      '#max' => 4000,
      '#required' => true,
      '#field_suffix' => '(单位: Mbps)',
      '#description' => '当IP的流量小于至值不会拿来排序和牵引'
    );
    $form['flow_continue_sec'] = array(
      '#type' => 'number',
      '#title' => '连接超流量次数',
      '#main' => 1,
      '#max' => 10,
      '#required' => true,
      '#default_value' => $config->get('flow_continue_sec'),
      '#description' => '设置IP连续几次超流量才会被牵引。值区间[1，10]'
    );
    $form['overall']  = array(
      '#type' => 'fieldset',
      '#title' => '策略缺省设置(用IP查找不到策略时用此值来判断)'
    );
    $form['overall']['bps'] = array(
      '#type' => 'number',
      '#title' => 'BPS',
      '#min' => 10,
      '#default_value' => $config->get('bps'),
      '#required' => true,
      '#field_suffix' => '(单位: Mbps)'
    );
    $form['overall']['pps'] = array(
      '#type' => 'number',
      '#title' => 'PPS',
      '#min' => 1,
      '#default_value' => $config->get('pps'),
      '#required' => true,
      '#field_suffix' => '(单位: 万/pps)'
    );
    $form['overall']['time'] = array(
      '#type' => 'number',
      '#title' => '牵引时间',
      '#min' => 1,
      '#default_value' => $config->get('time'),
      '#required' => true,
      '#field_suffix' => '(单位: 分钟)'
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => '保存设置'
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $min_bps = $form_state->getValue('min_bps');
    $config = \Drupal::configFactory()->getEditable('qy_jd.settings');
    $config->set('min_bps', $min_bps);
    $config->set('bps', $form_state->getValue('bps'));
    $config->set('pps', $form_state->getValue('pps'));
    $config->set('time', $form_state->getValue('time'));
    $config->set('flow_continue_sec', $form_state->getValue('flow_continue_sec'));
    $config->save();
    drupal_set_message('保存成功');
  }
}
