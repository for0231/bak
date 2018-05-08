<?php

/**
 * @file
 * 配置系统
 *
 * Contains \Drupal\utils\Form\PortConfigForm.
 */

namespace Drupal\utils\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AlertCommand;
use Drupal\utils\PhpSocketClient;

class PortConfigForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'port_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if(empty($_GET['manageip']) || empty($_GET['port']) || empty($_GET['switchname']) || empty($_GET['switchip'])) {
      return array('#markup' => '参数错误');
    }
    $manageip = $_GET['manageip'];
    $port = $_GET['port'];
    $switchname = $_GET['switchname'];
    $switchip = $_GET['switchip'];
    
    $form['manage_ip'] = array(
      '#type' => 'textfield',
      '#title' => '管理IP',
      '#default_value' => $manageip,
      '#disabled' => true
    );
    $form['switch_name'] = array(
      '#type' => 'textfield',
      '#title' => '交换机名称',
      '#default_value' => $switchname,
      '#disabled' => true
    );
    $form['switch_ip'] = array(
      '#type' => 'textfield',
      '#title' => '交换机IP',
      '#default_value' => $switchip,
      '#disabled' => true
    );
    $form['witch_port'] = array(
      '#type' => 'textfield',
      '#title' => '端口',
      '#default_value' => $port,
      '#disabled' => true
    );
    $res = array();
    $settings = \Drupal::service('settings');
    $sock_ip = $settings->get('py_socket_ip');
    if(!empty($sock_ip)) {
      $send = array('ip' => $switchip, 'port' => $port, 'op' => 'load');
      try {
        $sock = new PhpSocketClient($sock_ip, 9000);
        $data = $sock->command(json_encode($send), 'port_config');
        $res = json_decode($data, true);
      } catch (\Exception $e) {
        drupal_set_message('连接交换机不成功', 'error');
      }
    }
    $result = isset($res['result']) ? $res['result'] : '';
    $form['res'] = array(
      '#type' => 'textarea',
      '#title' => '运行结果',
      '#default_value' => $result,
      '#rows' => 15,
      '#disabled' => true
    );
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['bandwidth_in'] = array(
      '#type' => 'submit',
      '#value' => '调整带宽(IN)',
      '#disabled' => true,
      '#ajax' => array(
        'url' => new Url('admin.utils.port.config.broadband', array('manageip' =>$manageip,'port' => $port, 'switchname' => $switchname,'ip' => $switchip,'type' => 'in')),
        'dialogType' => 'modal'
      ),
    );
    $form['actions']['bandwidth_out'] = array(
      '#type' => 'submit',
      '#value' => '调整带宽(OUT)',
      '#disabled' => true,
      '#ajax' => array(
        'url' => new Url('admin.utils.port.config.broadband', array('manageip' =>$manageip,'port' => $port, 'switchname' => $switchname,'ip' => $switchip,'type' => 'out')),
        'dialogType' => 'modal'
      )
    );
    $form['actions']['vlan'] = array(
      '#type' => 'submit',
      '#value' => '调整Vlan',
      '#disabled' => true,
      '#ajax' => array(
        'url' => new Url('admin.utils.port.config.vlan', array('manageip' =>$manageip,'port' => $port, 'switchname' => $switchname,'ip' => $switchip)),
        'dialogType' => 'modal'
      )
    );
    $form['actions']['switch_port'] = array(
      '#type' => 'submit',
      '#value' => '开关端口',
      '#disabled' => true,
      '#ajax' => array(
        'url' => new Url('admin.utils.port.config.switchport', array('manageip' =>$manageip,'port' => $port, 'switchname' => $switchname,'ip' => $switchip)),
        'dialogType' => 'modal'
      )
    );
    $form['actions']['source_shield'] = array(
      '#type' => 'submit',
      '#value' => '源防护',
      '#disabled' => true,
      '#ajax' => array(
        'url' => new Url('admin.utils.port.config.shield', array('manageip' =>$manageip,'port' => $port, 'switchname' => $switchname,'ip' => $switchip)),
        'dialogType' => 'modal'
      )
    );
    $form['actions']['desc'] = array(
      '#type' => 'submit',
      '#value' => '描述',
      '#disabled' => true,
      '#ajax' => array(
        'url' => new Url('admin.utils.port.config.desc', array(
           'manageip' =>$manageip,
           'port' => $port, 
           'switchname' => $switchname,
           'ip' => $switchip,
           'desc' => empty($res['desc_flag']) ? '' : $res['desc_flag']
        )),
        'dialogType' => 'modal'
      )
    );
    
    $form['actions2'] = array('#type' => 'actions');
    $form['actions2']['save'] = array(
      '#type' => 'submit',
      '#value' => '保存',
      '#disabled' => true,
      '#ajax' => array(
        'url' => new Url('admin.utils.port.config.save', array(
           'manageip' =>$manageip,
           'port' => $port, 
           'switchname' => $switchname,
           'ip' => $switchip
        )),
        'dialogType' => 'modal'
      )
    );
    $form['actions2']['back'] = array(
      '#type' => 'link',
      '#title' => '返回列表',
      '#attributes' => array('class'=> array('button')),
      '#url' => new Url('admin.resourcepool.rackpart.list')
    );
    if(stripos($result, 'Error') === false) {
      $form['actions']['desc']['#disabled'] = false;
    }
    if(isset($res['desc_flag']) && !empty($res['desc_flag']))  {
      $form['actions']['bandwidth_in']['#disabled'] = false;
      $form['actions']['bandwidth_out']['#disabled'] = false;
      $form['actions']['vlan']['#disabled'] = false;
      $form['actions']['switch_port']['#disabled'] = false;
      $form['actions']['source_shield']['#disabled'] = false;
      $form['actions2']['save']['#disabled'] = false;
    }
    
    #$form['#attached']['library'][] = 'utils/drupal.utils-base';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
  }
}
