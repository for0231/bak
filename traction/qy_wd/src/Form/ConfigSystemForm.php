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

class ConfigSystemForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'qy_System_Config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = 0) {
    $config = \Drupal::config('qy_wd.settings');
    $min_bps = $config->get('min_bps');

    $form['min_bps'] = array(
      '#type' => 'number',
      '#title' => '流量过滤值',
      '#default_value' => $min_bps,
      '#min' => 1,
      '#max' => 4000,
      '#required' => true,
      '#field_suffix' => '(单位:Mbps)',
      '#description' => '当IP的流量小于至值不会拿来排序和牵引'
    );
    $form['listen_unit'] = array(
      '#type' => 'textfield',
      '#title' => '监听的单元',
      '#default_value' => $config->get('listen_unit'),
      '#disabled' => true,
      '#description' => '设置线路时会自动保存此项目'
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
      '#value' => '保存'
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $min_bps = $form_state->getValue('min_bps');
    $config = \Drupal::configFactory()->getEditable('qy_wd.settings');
    $config->set('min_bps', $min_bps);
    $config->set('bps', $form_state->getValue('bps'));
    $config->set('pps', $form_state->getValue('pps'));
    $config->set('time', $form_state->getValue('time'));
    $config->save();
    drupal_set_message('保存成功');
  }
}
