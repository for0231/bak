<?php

/**
 * @file
 * 开关端口
 *
 * Contains \Drupal\utils\Form\PortSwitchForm.
 */

namespace Drupal\utils\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\utils\PhpSocketClient;

class PortSwitchForm extends FormBase {
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
    $form['switch_port'] = array(
      '#type' => 'select',
      '#title' => '开关端口',
      '#options' => array('undo shutdown' => 'ON', 'shutdown' => 'OFF'),
      '#wrapper_attributes' => array('class' => array('container-inline'))
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

  public function submitSaveForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $input = $form_state->getValue('switch_port');
    $ip = $_GET['ip'];
    $port = $_GET['port'];
    $manageip = $_GET['manageip'];
    $switchname = $_GET['switchname'];
    $settings = \Drupal::service('settings');
    $sock_ip = $settings->get('py_socket_ip');
    if(!empty($sock_ip)) {
      $sock = new PhpSocketClient($sock_ip, 9000);
      $send = array('ip' => $ip, 'port' => $port, 'op' => 'port_switch_modify', 'config_list' => array($input));
      $res = $sock->command(json_encode($send), 'port_config');

      //记录日志
      $db_service = \Drupal::service('utils.networkconfig');
      $log_id = $db_service->insertLog(array(
        'created' => time(),
        'uid' => \Drupal::currentUser()->id(),
        'type' => 'portconfig',
        'command' => '接口配置：交换机['. $ip .']端口['. $port .']调整开关端口为['. $input .']',
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
