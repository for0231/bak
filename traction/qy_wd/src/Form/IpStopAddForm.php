<?php

/**
 * @file
 * Contains \Drupal\qy_wd\Form\IpStopAddForm.
 */

namespace Drupal\qy_wd\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * 增加IP封停表单类
 */
class IpStopAddForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'qy_ip_stop_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $def_value = '';
    $netOptions = array();
    $db_service= \Drupal::service('qy_wd.db_service');
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

    $form['ips'] = array(
      '#type' => 'textarea',
      '#title' => 'IP',
      '#cols' => 20,
      '#rows' => 8,
      '#description' => '一行只能输入一个IP'
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
    $ips = $form_state->getValue('ips');
    $ip_arrs = explode("\r\n", $ips);
    foreach($ip_arrs as $ip) {
      $ip = trim($ip);
      //判断IP格式是否正确
      if(strcmp(long2ip(sprintf("%u",ip2long($ip))), $ip)) {
        $form_state->setErrorByName('ip',$this->t('Ip格式错误'));
      }
      //判断IP是否存在。
      $db_service= \Drupal::service('qy_wd.db_service');
      $qy = $db_service->load_qy(array('ip'=> $ip));
      if(!empty($qy)) {
        $form_state->setErrorByName('ip',$this->t('Ip已牵引了'));
      }
      //@todo是否在策略里面。
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $db_service= \Drupal::service('qy_wd.db_service');
    $routes = $db_service->load_route(array('status' => 1));
    $rotue_id = trim($form_state->getValue('net_type'));
    $route = $routes[$rotue_id];
    $user = \Drupal::currentUser();
    $ips = $form_state->getValue('ips');
    $ip_arrs = explode("\r\n", $ips);
    foreach($ip_arrs as $ip) {
      $policy = getPolicyByIP($db_service, $rotue_id, trim($ip));
      if(empty($policy)) {
        $bps = rand(500, 1000);
        $pps = $bps * 148 / 1000;
      } else {
        $ra= rand(100, 200);
        $bps = $policy->bps + $ra;
        $pps = $policy->pps + ($ra * 148 / 1000);
      }
      $db_service->add_qy(array(
        'ip' => trim($ip),
        'bps' => $bps,
        'pps' => $pps,
        'net_type' => $rotue_id,
        'start' => REQUEST_TIME,
        'time' => $form_state->getValue('time'),
        'type' => 'Stop',
        'note' => $form_state->getValue('note'),
        'opter' => $user->getUsername(),
        'uid' => $user->id(),
        'gjft' => 2,
        'state' => $route->is_global
      ));
    }
    drupal_set_message('保存成功');
    $form_state->setRedirectUrl(new Url('admin.wd.ip_stop'));
  }
}