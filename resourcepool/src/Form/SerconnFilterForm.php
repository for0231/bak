<?php
namespace Drupal\resourcepool\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class SerconnFilterForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'serconn_filter_form';
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state,$type='') {
    $form['filters'] = array(
      '#type' => 'details',
      '#title' => '查询条件',
      '#open' => !empty($_SESSION['resource_pool']),
    );
    if($type=='Serres'){
      $form['filters']['rent_time'] = array(
        '#type' => 'textfield',
        '#title' => '租用时间',
        '#default_value' => empty($_SESSION['resource_pool']['rent_time'])?null: $_SESSION['resource_pool']['rent_time'],
      );
      $form['filters']['end_time'] = array(
        '#type' => 'textfield',
        '#title' => '到期时间',
        '#default_value' => empty($_SESSION['resource_pool']['end_time'])?null: $_SESSION['resource_pool']['end_time'],
      );
    }elseif($type='Serconn'){
      $form['filters']['onsale_status'] = array(
        '#type' => 'select',
        '#title' => '在售状态',
        '#options' => array(''=>'全部','已售'=>'已售','可售'=>'可售','测试'=>'测试','自用'=>'自用'),
        '#default_value' => empty($_SESSION['resource_pool']['onsale_status'])?null: $_SESSION['resource_pool']['onsale_status'],
      );
      $form['filters']['vlan'] = array(
        '#type' => 'textfield',
        '#title' => 'Vlan',
        '#default_value' => empty($_SESSION['resource_pool']['vlan'])? '': $_SESSION['resource_pool']['vlan']
      );
      $form['filters']['room'] = array(
        '#type' => 'textfield',
        '#title' => '机房',
        '#default_value' => empty($_SESSION['resource_pool']['room'])? '': $_SESSION['resource_pool']['room']
      );
    }
    $form['filters']['server_id'] = array(
      '#type' => 'textfield',
      '#title' => '服务器编号',
      '#default_value' => empty($_SESSION['resource_pool']['server_id']) ? '' : $_SESSION['resource_pool']['server_id']
    );
    $form['filters']['rack_no'] = array(
      '#type' => 'textfield',
      '#title' => '机柜',
      '#default_value' => empty($_SESSION['resource_pool']['rack_no'])? '': $_SESSION['resource_pool']['rack_no']
    );
    $form['filters']['nic1_address'] = array(
      '#type' => 'textfield',
      '#title' => 'NIC1 IP地址',
      '#default_value' => empty($_SESSION['resource_pool']['nic1_address'])? '': $_SESSION['resource_pool']['nic1_address']
    );
    $form['filters']['client_name'] = array(
      '#type' => 'textfield',
      '#title' => '客户',
      '#default_value' => empty($_SESSION['resource_pool']['client_name'])? '': $_SESSION['resource_pool']['client_name']
    );
    $form['filters']['submit'] = array(
      '#type' => 'submit',
      '#value' => '查询'
    );
    $form['filters']['reset'] = array(
      '#type' => 'submit',
      '#value' => '清空',
      '#submit' => array('::resetForm'),
    );
    $form['#attached'] = array(
      'library' => array('resourcepool/drupal.respool-time')
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['resource_pool']['server_id'] = $form_state->getValue('server_id');
    $_SESSION['resource_pool']['rack_no'] = $form_state->getValue('rack_no');
    $_SESSION['resource_pool']['nic1_address'] = $form_state->getValue('nic1_address');
    $_SESSION['resource_pool']['client_name'] = $form_state->getValue('client_name');
    $_SESSION['resource_pool']['rent_time'] = empty($form_state->getValue('rent_time'))?null:$form_state->getValue('rent_time');
    $_SESSION['resource_pool']['end_time'] = empty($form_state->getValue('end_time'))?null:$form_state->getValue('end_time');
    $_SESSION['resource_pool']['onsale_status'] = empty($form_state->getValue('onsale_status'))?null:$form_state->getValue('onsale_status');
    $_SESSION['resource_pool']['vlan'] =empty($form_state->getValue('vlan'))?null:$form_state->getValue('vlan');
    $_SESSION['resource_pool']['room'] = empty($form_state->getValue('room'))?null:$form_state->getValue('room');
  }
  
  /**
   * {@inheritdoc}
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['resource_pool'] = array();
  }
}
