<?php

/**
 * @file
 * 报警值设置From
 *
 * Contains \Drupal\qy_remote\Form\RemoteConfigForm.
 */

namespace Drupal\qy_remote\Form;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class RemoteConfigForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'qy_remote_Config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['qy_remote.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = 0) {
    $config = \Drupal::config('qy_remote.settings');
    $databases = array('default' => 'Default');
    $info = Database::getAllConnectionInfo();
    if(!empty($info['default'])) {
      $conn = $info['default'];
      foreach($conn as $key => $item) {
        if($key != 'default') {
          $databases[$key] = $item['host'];
        }
      }
    }
    $traction_database = $config->get('traction_database');
    $form['traction_database'] = array(
      '#type' => 'select',
      '#title' => '连接数据库',
      '#options' => $databases,
      '#default_value' => $traction_database,
      '#required' => true
    );

    $firewall = qy_module_firewall();
    $open = $config->get('traction_open_firewall');
    if($traction_database == 'default') {
      $open = qy_module_open_firewall();
    }
    $form['traction_open_firewall'] = array(
      '#type' => 'checkboxes',
      '#title' => '开启的防火墙',
      '#options' => $firewall,
      '#default_value' => $open,
      '#required' => TRUE,
      /*'#states' => array(
        'disabled' => array(
          'select#edit-traction-database' => array('value' => 'default')
        )
      )*/
    );
    $toutes_optons = array('' => '-请选择-');
    $db_services = qy_remote_firewall_dbserver();
    foreach($db_services as $key => $db_service) {
      $routes = $db_service->load_route(array('status' => 1), false);
      foreach($routes as $route) {
        $toutes_optons[$key . '-' . $route->id] = $route->routename;
      }
    }
    $traction_telecom_route = $config->get('traction_telecom_route');
    $form['traction_telecom_route'] = array(
      '#type' => 'select',
      '#title' => '开启的电信线路',
      '#options' => $toutes_optons,
      '#default_value' => $traction_telecom_route,
      '#description' => '选择的线路流量值将通过策略表来产生其它的将通过线路表产生'
    );

    $traction_disable_ips = $config->get('traction_disable_ips');
    $form['traction_disable_ips'] = array(
      '#type' => 'textarea',
      '#title' => '禁止三方接口牵引的IP',
      '#default_value' => implode("\r\n", $traction_disable_ips),
      '#description' => '输入规则：一行一个IP段，规则如下：192.168.1.0/24、192.168.1.1/32。'
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $disable_ip_value = array();
    $disable_ips = $form_state->getValue('traction_disable_ips');
    if(!empty($disable_ips)) {
      $ip_arrs = explode("\r\n", $disable_ips);
      foreach($ip_arrs as $item) {
        $ip_segment = explode("/", trim($item));
        if(count($ip_segment) != 2) {
          $form_state->setErrorByName('traction_disable_ips', '输入的格式有错误');
          break;
        }
        $ip = $ip_segment[0];
        $mask = $ip_segment[1];
        if(strcmp(long2ip(sprintf("%u",ip2long($ip))), $ip)) {
          $form_state->setErrorByName('traction_disable_ips', '输入的格式有错误');
          break;
        }
        if(!is_numeric($mask)) {
          $form_state->setErrorByName('traction_disable_ips', '输入的格式有错误');
          break;
        }
        if($mask < 24 || $mask > 32) {
          $form_state->setErrorByName('traction_disable_ips', '输入的IP有错误');
          break;
        }
        $disable_ip_value[] = $ip . '/' . $mask;
      }
    }
    $form_state->disable_ip_value = $disable_ip_value;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('qy_remote.settings')
      ->set('traction_database', $form_state->getValue('traction_database'))
      ->set('traction_open_firewall', $form_state->getValue('traction_open_firewall'))
      ->set('traction_telecom_route', $form_state->getValue('traction_telecom_route'))
      ->set('traction_disable_ips', $form_state->disable_ip_value)
      ->save();
    parent::submitForm($form, $form_state);
  }
}
