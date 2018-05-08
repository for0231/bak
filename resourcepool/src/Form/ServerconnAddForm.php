<?php

/**
 * @file
 * Contains \Drupal\resourcepool\Form\ServercommAddForm.
 */

namespace Drupal\resourcepool\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
/**
 * 线路表单
 */
class ServerconnAddForm extends FormBase {

 protected $db_service;

  public function __construct($db_service) {
    $this->db_service = $db_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
       \Drupal::service('resourcepool.dbservice')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'server_conn_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state,$no=0) {
    $form['room'] = array(
      '#type' => 'textfield',
      '#title' => '机房',
      '#maxlength' => 50,
    );
    $form['rack_no'] = array(
      '#type' => 'textfield',
      '#title' => '机柜号',
      '#required' => false,
      '#maxlength' => 50,
    );
    $form['server_id'] = array(
      '#type' => 'textfield',
      '#title' => '服务器编号',
      '#required' => false,
      '#maxlength' => 50
    );
    $form['server_part'] = array(
      '#type' => 'textfield',
      '#title' => '服务器配置',
      '#required' => false,
      '#maxlength' => 50,
    );
    $form['location_u'] = array(
      '#type' => 'textfield',
      '#title' => 'U位',
      '#required' => false,
      '#maxlength' => 50
    );
    $form['node'] = array(
      '#type' => 'number',
      '#title' => 'Node',
    );
    $form['nic1_address'] = array(
      '#type' => 'textfield',
      '#title' => 'NIC1 IP地址',
      '#maxlength' => 50
    );
    $form['ic_address'] = array(
      '#type' => 'textfield',
      '#title' => 'NIC1链接交换机名称',
      '#required' => false,
      '#maxlength' => 50
    );
    $form['nic1'] = array(
      '#type' => 'textfield',
      '#title' => 'NIC1链接交换机端口',
      '#required' => false,
      '#maxlength' => 50
    );
    $form['nic1_bandwidth'] = array(
      '#type' => 'textfield',
      '#title' => 'NIC1 带宽',
      '#required' => false,
      '#maxlength' => 50
    );
    $form['nic1_vlan'] = array(
      '#type' => 'number',
      '#title' => 'NIC1 VLAN',
    );
    $form['ic2_address'] = array(
      '#type' => 'textfield',
      '#title' => 'NIC2链接交换机名称',
      '#required' => false,
      '#maxlength' => 50
    );
    $form['nic2'] = array(
      '#type' => 'textfield',
      '#title' => 'NIC2链接交换机端口',
      '#required' => false,
      '#maxlength' => 50
    );
    $form['nic2_bandwidth'] = array(
      '#type' => 'textfield',
      '#title' => 'NIC2 带宽',
      '#required' => false,
      '#maxlength' => 50
    );
    $form['nic2_vlan'] = array(
      '#type' => 'number',
      '#title' => 'NIC2 VLAN',
    );
    $form['cacti1'] = array(
      '#type' => 'textfield',
      '#title' => 'Cacti1',
      '#required' => false
    );
    $form['cacti2'] = array(
      '#type' => 'textfield',
      '#title' => 'Cacti2',
      '#required' => false
    );
    $form['client_name'] = array(
      '#type' => 'textfield',
      '#title' => '客户',
      '#maxlength' => 50
    );
    $form['no'] = array(
      '#type' => 'value',
      '#value' => 0
    );
    if ($no){
      $server_conn = $this->db_service->loadEntityById('resource_server_connection',$no);
      if(!empty($server_conn)){
        $form['server_id']['#default_value']= $server_conn->server_id;
        $form['location_u']['#default_value']= $server_conn->location_u;
        $form['node']['#default_value']= $server_conn->node;
        $form['nic1_address']['#default_value']= $server_conn->nic1_address;
        $form['ic_address']['#default_value']= $server_conn->ic_address;
        $form['cacti1']['#default_value']= $server_conn->cacti1;
        $form['nic1']['#default_value']=  $server_conn->nic1;
        $form['nic1_bandwidth']['#default_value']= $server_conn->nic1_bandwidth;
        $form['nic1_vlan']['#default_value']= $server_conn->nic1_vlan;
        $form['ic2_address']['#default_value']= $server_conn->ic2_address;
        $form['cacti2']['#default_value']= $server_conn->cacti2;
        $form['nic2']['#default_value']= $server_conn->nic2;
        $form['nic2_bandwidth']['#default_value']= $server_conn->nic2_bandwidth;
        $form['nic2_vlan']['#default_value']= $server_conn->nic2_vlan;
        $form['server_part']['#default_value']= $server_conn->server_part;
        $form['client_name']['#default_value']= $server_conn->client_name;
        $form['room']['#default_value']= $server_conn->room;
        $form['rack_no']['#default_value']= $server_conn->rack_no;
        $form['no']['#value'] = $no;
      }
    }
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => '保存',
    );
    
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $cacti1 = $form_state->getValue('cacti1');
    $cacti2 = $form_state->getValue('cacti2');
    if(!empty($cacti1)){
      if(empty(substr($cacti1,0,7)==='http://')){
        $form_state->setErrorByName('cacti1','cacti1地址格式错误');
      }
    }
    if(!empty($cacti2)){
      if(empty(substr($cacti2,0,7)==='http://')){
        $form_state->setErrorByName('cacti2','cacti2地址格式错误');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $page= empty($_GET['page'])?0:$_GET['page'];
    $no = $form_state->getValue('no');
    $values = array(
      'server_id' => $form_state->getValue('server_id'),
      'location_u' => $form_state->getValue('location_u'),
      'node' => empty($form_state->getValue('node'))?0:$form_state->getValue('node'),
      'nic1_address' => $form_state->getValue('nic1_address'),
      'ic_address' => $form_state->getValue('ic_address'),
      'cacti1' => $form_state->getValue('cacti1'),
      'nic1' => $form_state->getValue('nic1'),
      'nic1_bandwidth' => $form_state->getValue('nic1_bandwidth'),
      'nic1_vlan' => empty($form_state->getValue('nic1_vlan'))?null:$form_state->getValue('nic1_vlan'),
      'ic2_address' => $form_state->getValue('ic2_address'),
      'cacti2' => $form_state->getValue('cacti2'),
      'nic2' => $form_state->getValue('nic2'),
      'nic2_bandwidth' => $form_state->getValue('nic2_bandwidth'),
      'nic2_vlan' => empty($form_state->getValue('nic2_vlan'))?null:$form_state->getValue('nic2_vlan'),
      'server_part' => $form_state->getValue('server_part'),
      'room' => $form_state->getValue('room'),
      'rack_no' => $form_state->getValue('rack_no'),
      'client_name' => $form_state->getValue('client_name')
    );
    if($no) {
      $this->db_service->update_entity_byno($values, $no,'resource_server_connection');
    } else {
      $this->db_service->add_entity($values,'resource_server_connection');
      
    }
    drupal_set_message('保存成功');
    $form_state->setRedirectUrl(new Url('admin.resource.serverconn.list',array('page'=>$page)));
  }
}

