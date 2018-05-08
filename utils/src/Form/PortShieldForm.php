<?php

/**
 * @file
 * 开关端口
 *
 * Contains \Drupal\utils\Form\PortShieldForm.
 */

namespace Drupal\utils\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\utils\PhpSocketClient;

class PortShieldForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'port_switch_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#prefix'] = '<div id="dialog-error-message">';
    $form['#suffix'] = '</div>';
    $form['add'] = array(
      '#type' => 'textarea',
      '#title' => '绑定的IP',
      '#placeholder' => '一行一个IP',
      '#attributes' => array(
        'style' => 'width:500px;'
      )
    );
    $form['delete'] = array(
      '#type' => 'textarea',
      '#title' => '解绑的IP',
      '#placeholder' => '一行一个IP',
    );
    $form['port_op'] = array(
      '#type' => 'select',
      '#title' => '端口操作',
      '#options' => array('' => '不做操作', 'enable' => '启用', 'undo enable' => '禁用')
    );
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => '确定',
      '#ajax' => array(
        'callback' => '::submitSaveForm',
        'event' => 'click',
      )
    );
    $form['actions']['cancel'] = array(
      '#type' => 'submit',
      '#value' => '取消',
      '#ajax' => array(
        'callback' => '::submitCancelForm',
        'event' => 'click',
      )
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $adds = $form_state->getValue('add');
    $add_ips = explode("\n", $adds);
    $add_options = array();
    foreach($add_ips as $item) {
      $ip = trim($item);
      if(empty($ip)) {
        continue;
      }
      if(strcmp(long2ip(sprintf("%u",ip2long($ip))), $ip)) {
        $form_state->setErrorByName('add', '绑定的IP中有IP格式错误');
        continue;
      }
      $add_options[] = $ip;
    }
    $form_state->add_options = $add_options;
    
    $dels = $form_state->getValue('delete');
    $del_ips = explode("\n", $dels);
    $del_options = array();
    foreach($del_ips as $item) {
      $ip = trim($item);
      if(empty($ip)) {
        continue;
      }
      if(strcmp(long2ip(sprintf("%u",ip2long($ip))), $ip)) {
        $form_state->setErrorByName('delete', '解绑的IP中有IP格式错误');
        continue;
      }
      $del_options[] = $ip;
    }
    $form_state->del_options = $del_options;
  }
  
  public function submitSaveForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    if ($form_state->getErrors()) {
      unset($form['#prefix'], $form['#suffix']);
      $form['status_messages'] = array(
        '#type' => 'status_messages',
        '#weight' => -10,
      );
      $response->addCommand(new HtmlCommand('#dialog-error-message', $form));
      return $response;
    }
    $add_options = $form_state->add_options;
    $del_options = $form_state->del_options;
    $port_op = $form_state->getValue('port_op');
    $ip = $_GET['ip'];
    $port = $_GET['port'];
    $manageip = $_GET['manageip'];
    $switchname = $_GET['switchname'];
    $settings = \Drupal::service('settings');
    $sock_ip = $settings->get('py_socket_ip');
    if(!empty($sock_ip)) {
      $sock = new PhpSocketClient($sock_ip, 9000);
      $send = array('ip' => $ip, 'port' => $port, 'op' => 'source_guard_modify', 'config_list' => array(
        'bind_ip' => $add_options,
        'unbind_ip' => $del_options,
        'port_op' => $port_op
      ));
      $res = $sock->command(json_encode($send), 'port_config');

      //记录日志
      $db_service = \Drupal::service('utils.networkconfig');
      $log_id = $db_service->insertLog(array(
        'created' => time(),
        'uid' => \Drupal::currentUser()->id(),
        'type' => 'portconfig',
        'command' => '接口配置：交换机['. $ip .']端口['. $port .']调整源防护为[绑定的IP：'. implode(',', $add_options) .'解绑的ip：'. implode(',', $del_options) .'端口操作：'. $port_op .']',
        'response' => $res
      ));
    }
    $response->addCommand(new CloseModalDialogCommand());
    $response->addCommand(new RedirectCommand(\Drupal::url('admin.utils.port.config', array('manageip' =>$manageip,'port' => $port, 'switchname' => $switchname,'switchip' => $ip))));
    return $response;
  }

  public function submitCancelForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    return $response;
  }
}
