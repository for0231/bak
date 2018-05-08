<?php

namespace Drupal\fw_config\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class MessageAddForm extends FormBase{
  
  public function getFormId() {
    return 'message_add_form';
  }
  
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['mac_address'] = array(
      '#type' => 'textfield',
      '#title' => '捕捉MAC地址',
    );
    $form['ip_address'] = array(
      '#type' => 'textfield',
      '#title' => '捕捉IP地址',
      '#required'=> true,
    );
    $form['type'] = array(
      '#type' => 'textfield',
      '#title' => '协议类型',
      '#value' => 'ip',
      '#disabled' => true,
    );
    $form['capture_dev_name'] = array(
      '#type' => 'select',
      '#title' => '设备接口',
      '#options' =>array(
        '' => 'all',
        'xgbe0' => 'xgbe0',
        'xgbe1' => 'xgbe1',
        'gbe0' => 'gbe0',
        'gbe1' => 'gbe1',
        'eth0' => 'eth0',
        'eth1' => 'eth1',
        'lo' => 'lo',
      )
    );
    $form['sample_rate'] = array(
      '#type' => 'textfield',
      '#title' => '捕捉采样比',
    );
    $form['count'] = array(
      '#type' => 'textfield',
      '#title' => '捕捉报文数目',
      '#placeholder' => '捕捉报文数目为整数',
      '#required' => true
    );
    $form['tftp_file'] = array(
      '#type' => 'textfield',
      '#title' => '远程TFTP文件',
    );
    $form['size'] = array(
      '#type' => 'textfield',
      '#title' => '捕捉数据的大小',
      '#disabled'=>true,
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => '提交',
    );
    if(empty($_POST)) {
      $write_arr = $this->getWriteFw();
      if(empty($write_arr)) {
        drupal_set_message('无请求防火墙！');
        return $form;
      }
      $conn = new \Drupal\fw_config\FwConnect();
      $url = 'http://'. $write_arr[0] .'/cgi-bin/service_capture.cgi';
      $login = $conn->login($write_arr[0], $write_arr[1], $write_arr[2]);
      if($login) {
        $xml = $conn->getRead($url);
        $capture_dev_name = (string)$xml->capture_device;
        if($capture_dev_name  == 'all') {
          $capture_dev_name = '';
        }
        $form['capture_dev_name']['#default_value'] = $capture_dev_name;
        $form['size']['#default_value'] = (string)$xml->size;
        $form['ip_address']['#default_value'] = (string)$xml->ip_address;
        $form['mac_address']['#default_value'] = (string)$xml->mac_address;
        $form['sample_rate']['#default_value'] = (string)$xml->sample_rate;
        $form['count']['#default_value'] = (string)$xml->count;
        $form['tftp_file']['#default_value'] = (string)$xml->file;
        
        $form['download'] = array(
          '#type' => 'link',
          '#title' => '下载',
          '#url' => Url::fromUri('http://'. $write_arr[0] .'/cgi-bin/service_capture.cgi?param_submit_type=download'),
          '#attributes' => array(
             'class' => array('button')
          )
        );
      }
    }
    return $form;
  }
 
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $write_arr = $this->getWriteFw();
    if(empty($write_arr)) {
      drupal_set_message('无请求防火墙！');
      return;
    }
    $conn = new \Drupal\fw_config\FwConnect();
    $url = 'http://'. $write_arr[0] .'/cgi-bin/service_capture.cgi';
    $login = $conn->login($write_arr[0], $write_arr[1], $write_arr[2]);
    if(!$login) {
      drupal_set_message('提交的防火墙为空!');
      return;
    }
    $xml = $conn->postData($url, array(
      'param_submit_type'=>'submit',
      'param_protocol_num' => 255,
      'param_capture_count' => $form_state->getValue('count'),
      'param_capture_dev_name' => $form_state->getValue('capture_dev_name'),
      'param_capture_ip_address' => $form_state->getValue('ip_address'),
      'param_capture_mac_address' => $form_state->getValue('mac_address'),
      'param_capture_protocol' => 'ip',
      'param_capture_sample_rate' => $form_state->getValue('sample_rate'),
      'param_capture_tftp_file' => $form_state->getValue('tftp_file')
    ));
  }
  private function getWriteFw() {
    $config = \Drupal::config('fw_config.settings');
    $write_fw = $config->get('write_fws');
    $write_fws = explode("\r\n", $write_fw);
    $write_arr = array();
    foreach($write_fws as $item) {
      $item_arr = explode("-", $item);
      if($item_arr[3] == 1) {
        $write_arr = $item_arr;
        break;
      }
    }
    return $write_arr;    
  }  
}
