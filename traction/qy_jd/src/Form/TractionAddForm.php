<?php

/**
 * @file
 * Contains \Drupal\qy_jd\Form\TractionAddForm.
 */

namespace Drupal\qy_jd\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * 手动增加牵引表单类
 */
class TractionAddForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jd_traction_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $def_value = '';
    $netOptions = array();
    $db_service= \Drupal::service('qy_jd.db_service');
    $routes = $db_service->load_route(array('status' => 1));
    foreach($routes as $route) {
      $netOptions[$route->id] = $route->routename;
      if(empty($def_value) && $route->is_global) {
        $def_value = $route->id;
      }
    }
    $form['net_type'] = array(
      '#type' => 'select',
      '#title' => '线路',
      '#required' => true,
      '#options' => $netOptions,
      '#default_value' => $def_value
    );
    $form['ip'] = array(
      '#type' => 'textfield',
      '#title' => 'ip',
      '#required' => true,
      '#size' => 20
    );
    $form['bps'] = array(
      '#type' => 'number',
      '#title' => 'BPS',
      '#required' => true,
      '#field_suffix' => 'm/bps'
    );
    $form['pps'] = array(
      '#type' => 'number',
      '#title' => 'PPS',
      '#required' => true,
      '#step' => 'any',
      '#field_suffix' => '万/pps'
    );
    $form['time'] = array(
      '#type' => 'number',
      '#title' => '牵引时间(分)',
      '#default_value' => 0,
      '#required' => true,
      '#field_suffix' => '[0 代表永久]'
    );
    $form['note'] = array(
      '#type' => 'textfield',
      '#title' => '说明'
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
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $ip = trim($form_state->getValue('ip'));
    $rotue_id = trim($form_state->getValue('net_type'));
    //判断IP格式是否正确
    if(strcmp(long2ip(sprintf("%u",ip2long($ip))), $ip)) {
       $form_state->setErrorByName('ip',$this->t('Ip格式错误'));
    }
    //判断IP是否存在。
    $db_service= \Drupal::service('qy_jd.db_service');
    $qy = $db_service->load_qy(array('ip'=> $ip, 'net_type' => $rotue_id));
    if(!empty($qy)) {
      $form_state->setErrorByName('ip',$this->t('Ip已牵引了'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $db_service= \Drupal::service('qy_jd.db_service');
    $routes = $db_service->load_route(array('status' => 1));
    $rotue_id = trim($form_state->getValue('net_type'));
    $route = $routes[$rotue_id];
    $user = \Drupal::currentUser();
    $db_service->add_qy(array(
      'ip' => trim($form_state->getValue('ip')),
      'pps' => $form_state->getValue('pps'),
      'bps' => $form_state->getValue('bps'),
      'net_type' => $rotue_id,
      'start' => REQUEST_TIME,
      'time' => $form_state->getValue('time'),
      'type' => 'Add',
      'note' => $form_state->getValue('note'),
      'opter' => $user->getUsername(),
      'uid' => $user->id(),
      'gjft' => 1,
      'state' => $route->is_global
    ));
    drupal_set_message('保存成功');
  }
}
