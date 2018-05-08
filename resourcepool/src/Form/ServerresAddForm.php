<?php

/**
 * @file
 * Contains \Drupal\resourcepool\Form\ServerresAddForm.
 */

namespace Drupal\resourcepool\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
/**
 * 线路表单
 */
class ServerresAddForm extends FormBase {

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
    return 'server_res_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state,$no=0) {
    $form['onsale_status'] = array(
      '#type' => 'select',
      '#title' => '在售状态',
      '#options' => array('已售'=>'已售','可售'=>'可售','测试'=>'测试','自用'=>'自用'),
      '#required' => false,
      '#default_value' => '已售',
    );
    $form['client_name'] = array(
      '#type' => 'textfield',
      '#title' => '客户',
      '#maxlength' => 50
    );
    $form['contract_no'] = array(
      '#type' => 'textfield',
      '#title' => '合同编号',
      '#maxlength' => 50
    );
    $form['rent_time'] = array(
      '#type' => 'textfield',
      '#title' => '租用时间',
      '#required' => false,
      '#maxlength' => 50
    );
    $form['end_time'] = array(
      '#type' => 'textfield',
      '#title' => '到期时间',
      '#required' => false,
      '#maxlength' => 50
    );
    $form['price'] = array(
      '#type' => 'textfield',
      '#title' => '价格',
      '#maxlength' => 50
    );
    $form['note'] = array(
      '#type' => 'textfield',
      '#title' => '备注',
      '#maxlength' => 50
    );
    $form['no'] = array(
      '#type' => 'value',
      '#value' => 0
    );
    if ($no){
      $server_conn = $this->db_service->loadEntityById('resource_server_connection',$no);
      if(!empty($server_conn)){
        $form['onsale_status']['#default_value']= $server_conn->onsale_status;
        $form['client_name']['#default_value']= $server_conn->client_name;
        $form['contract_no']['#default_value']= $server_conn->contract_no;
        $form['rent_time']['#default_value']= date("Y-m-d",$server_conn->rent_time);
        $form['end_time']['#default_value']= date("Y-m-d",$server_conn->end_time);
        $form['note']['#default_value']= $server_conn->note;
        $form['price']['#default_value']= empty($server_conn->price)?null:$server_conn->price;
        $form['no']['#value'] = $no;
      }
    }
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => '保存',
    );
    $form['#attached'] = [
      'library' => ['resourcepool/drupal.respool-time'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $no = $form_state->getValue('no');
    $values = array(
      'onsale_status' => $form_state->getValue('onsale_status'),
      'client_name' => $form_state->getValue('client_name'),
      'contract_no' => $form_state->getValue('contract_no'),
      'rent_time' => strtotime($form_state->getValue('rent_time')),
      'end_time' => strtotime($form_state->getValue('end_time')),
      'note' => $form_state->getValue('note'),
      'price' => empty($form_state->getValue('price'))?null:$form_state->getValue('price'),
    );
    if($no) {
      $this->db_service->update_entity_byno($values, $no,'resource_server_connection');
    } else {
      $this->db_service->add_entity($values,'resource_server_connection');
    }
    drupal_set_message('保存成功');
    $form_state->setRedirectUrl(new Url('admin.resource.server.resource'));
  }
}

