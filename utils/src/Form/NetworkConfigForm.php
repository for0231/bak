<?php

/**
 * @file
 * 配置系统
 *
 * Contains \Drupal\utils\Form\NetworkConfigForm.
 */

namespace Drupal\utils\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AlertCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\utils\PhpSocketClient;

class NetworkConfigForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_system_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['table'] = array(
      '#type' => 'table',
      '#header' => array('IP', '命令')
    );
    $form['table'][] = array(
      'ips' => array(
        '#type' => 'textarea',
        '#attributes' => array(
          'style' => 'width:400px; height:500px'
        ),
        '#description' => '一行一个IP, 最多可以写50个IP',
        '#wrapper_attributes' => array(
          'style' => 'width:410px;'
        )
      ),
      'cmds' => array(
        '#type' => 'textarea',
        '#attributes' => array(
          'style' => 'height:500px'
        ),
        '#description' => '要执行的命令'
      )
    );
    $form['actions'] = array(
      '#type' => 'actions'
    );
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => '提交',
      '#ajax' => array(
        'callback' => '::submitSaveForm',
        'event' => 'click',
        'progress' => array(
          'type' => 'throbber',
          'message' => '执行中请等待...'
        )
      ),
    );
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
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
  public function submitSaveForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $ips = $form_state->getValue('table')[0]['ips'];
    $cmd = $form_state->getValue('table')[0]['cmds'];
    if(empty(trim($ips))) {
      $response->addCommand(new AlertCommand('请输入IP'));
      return $response;
    }
    $settings = \Drupal::service('settings');
    $sock_ip = $settings->get('py_socket_ip');
    if(empty($sock_ip)) {
      $response->addCommand(new AlertCommand('请求地址不存在'));
      return $response;
    }
    $send = array(
      'ips' => $ips,
      'cmd' => $cmd
    );
    set_time_limit(0);
    $sock = new PhpSocketClient($sock_ip, 9000);
    if($sock === false) {
      $response->addCommand(new AlertCommand('连接失败'));
      return $response;
    }
    $db_service = \Drupal::service('utils.networkconfig');
    $log_id = $db_service->insertLog(array(
      'created' => time(),
      'uid' => \Drupal::currentUser()->id(),
      'type' => 'networkconfig',
      'command' => serialize($send)
    ));
    $res = $sock->command(json_encode($send), 'batch_config');
    if($res === false) {
      $response->addCommand(new AlertCommand('执行命令错误'));
      return $response;
    }
    $db_service->updateLog(array('response' => $res), $log_id);
    $sock->close();
    $response->addCommand(new OpenModalDialogCommand('执行结果', nl2br($res), array('width' => 600)));
    return $response;
  }
}
