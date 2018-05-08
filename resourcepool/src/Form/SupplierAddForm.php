<?php

/**
 * @file
 * Contains \Drupal\resourcepool\Form\SupplierAddForm
 */

namespace Drupal\resourcepool\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
/**
 * 线路表单
 */
class SupplierAddForm extends FormBase {

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
    return 'supplier_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state,$no=0) {
    $form['supplier_name'] = array(
      '#type' => 'textfield',
      '#title' => '供应商名称',
      '#maxlength' => 50
    );
    $form['supplier_type'] = array(
      '#type' => 'textfield',
      '#title' => '供应商类型',
      '#maxlength' => 50
    );
    $form['notice'] = array(
      '#type' => 'textfield',
      '#title' => '注意事项',
      '#maxlength' => 50
    );
    $form['note'] = array(
      '#type' => 'textfield',
      '#title' => '备注',
      '#maxlength' => 50
    );
    $form['supplier_info'] = array(
      '#type' => 'textarea',
      '#title' => '供应商信息',
    );
    $form['no'] = array(
      '#type' => 'value',
      '#value' => 0
    );
    if ($no){
      $server_conn = $this->db_service->loadEntityById('resource_supplier',$no);
      if(!empty($server_conn)){
        $form['supplier_name']['#default_value']= $server_conn->supplier_name;
        $form['supplier_type']['#default_value']= $server_conn->supplier_type;
        $form['supplier_info']['#default_value']= $server_conn->supplier_info;
        $form['notice']['#default_value']= $server_conn->notice;
        $form['note']['#default_value']= $server_conn->note;
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
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $no = $form_state->getValue('no');
    $values = array(
      'supplier_name' => $form_state->getValue('supplier_name'),
      'supplier_type' => $form_state->getValue('supplier_type'),
      'supplier_info' => $form_state->getValue('supplier_info'),
      'notice' => $form_state->getValue('notice'),
      'note' => $form_state->getValue('note'),
    );
    if($no) {
      $this->db_service->update_entity_byno($values, $no,'resource_supplier');
    } else {
      $this->db_service->add_entity($values,'resource_supplier');
    }
    drupal_set_message('保存成功');
    $form_state->setRedirectUrl(new Url('admin.resourcepool.supplierlist'));
  }
}

