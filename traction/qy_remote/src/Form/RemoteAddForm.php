<?php

/**
 * @file
 * 提供三方接口的表单
 *
 * Contains \Drupal\qy_remote\Form\RemoteAddForm.
 */

namespace Drupal\qy_remote\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class RemoteAddForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'qy_remote_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
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
      '#default_value' => 15,
      '#min' => 1,
      '#required' => true,
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
        $form_state->setErrorByName('ip', 'Ip格式错误');
      }
      if($this->checkedDisable($ip)) {
        $form_state->setErrorByName('ip', '禁止牵引'. $ip .'这个IP');
      }
    }
  }

  /**
   * 判断IP是否被禁止牵引
   */
  private function checkedDisable($ip) {
    $config = \Drupal::config('qy_remote.settings');
    $disable_ips = $config->get('traction_disable_ips');
    foreach($disable_ips as $item) {
      $ip_segment = explode("/", $item);
      $endIp = $ip_segment[0];
      if($ip_segment[1] < 32) {
        $endIp = $this->endIp($ip_segment[0], $ip_segment[1]);
      }
      $start = ip2long($ip_segment[0]);
      $end = ip2long($endIp);
      $current = ip2long($ip);
      if($current >= $start && $current <= $end) {
        return true;
      }
    }
    return false;
  }

  /**
   * 得到192.168.1.0/24最大IP
   */
  private function endIp($ip, $mask_number) {
    $n = 32 - $mask_number;
    $ips = explode('.', $ip);
    $num = pow(2, $n);
    $max = $ips[3] + $num - 1;
    return $ips[0] .'.'. $ips[1]. '.' . $ips[2] . '.' . $max;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::config('qy_remote.settings');
    $telecom_route = $config->get('traction_telecom_route'); //得到电信线路
    $db_services = qy_remote_firewall_dbserver();
    $error_ips = array();
    foreach($db_services as $module_name => $db_service) {
      $add_roures = array();
      $routes = $db_service->load_route(array('status' => 1), false);
      if(empty($routes)) {
        continue;
      }
      foreach($routes as $key => $route) {
        if($route->is_global) {
          $add_roures[$key] = $route;
          break;
        }
      }
      if(empty($add_roures)) {
        $add_roures = $routes;
      }
      $user = \Drupal::currentUser();
      $ips = $form_state->getValue('ips');
      $ip_arrs = explode("\r\n", $ips);
      foreach($ip_arrs as $ip) {
        $ip = trim($ip);
        foreach($add_roures as $route) {
          $policy = getPolicyByIP($db_service, $route->id, $ip);
          if(empty($policy)) {
            $error_ips[] = $ip;
            break;
          }
          $rand_bps = rand(100, 1024);
          $bps = $policy->bps + $rand_bps;
          $pps = $bps * 148 / 1000;
          $qys = $db_service->load_qy(array('ip'=> $ip, 'net_type' => $route->id));
          if(!empty($qys)) {
            $qy = reset($qys);
            if($qy->uid < 1 || $qy->type=='Add') {
              $db_service->update_qy(array(
                'bps' => $bps,
                'pps' => $pps,
                'uid' => $user->id(),
                'opter' => $user->getUsername(),
                'type'=>'cleaning',
                'start' => REQUEST_TIME + rand(0,2),
                'time' => $form_state->getValue('time'),
                'gjft' => 1,
                'state' => $route->is_global
              ), $qy->id);
            }
            continue;
          }
          $db_service->add_qy(array(
            'ip' => $ip,
            'bps' => $bps,
            'pps' => $pps,
            'net_type' => $route->id,
            'start' => REQUEST_TIME + rand(0,2),
            'time' => $form_state->getValue('time'),
            'type' => 'cleaning',
            'note' => $form_state->getValue('note'),
            'opter' => $user->getUsername(),
            'uid' => $user->id(),
            'gjft' => 1,
            'state' => $route->is_global
          ));
        }
      }
    }
    if(empty($error_ips)) {
      drupal_set_message('保存成功');
    } else {
      $errstr = implode(',', $error_ips);
      drupal_set_message('保存成功, 但IP: '. $errstr .'在部分路线执行失败。');
    }
    $form_state->setRedirectUrl(new Url('admin.remote.traction'));
  }
}
