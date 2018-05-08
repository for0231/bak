<?php
/**
 * @file
 * Contains Drupal\contract\Form\GoodsPlanAddForm
 */

namespace Drupal\contract\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class GoodsPlanAddForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'add_goods_plane';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $host_contract=null) {
    $contract_entity = entity_load('host_contract', $host_contract);
    $form['contract_id'] = array(
      '#type' => 'hidden',
      '#value' =>$host_contract
    );
    $form['funds_plan'] = array(
      '#type' => 'fieldset',
      '#title' => '合同交货计划',
      '#open' => true,
    );
    $form['funds_plan']['name'] = array(
      '#type' => 'textfield',
      '#required' =>TRUE,
      '#title' => '货物名称'
    );
    $form['funds_plan']['method'] = array(
      '#type' => 'textfield',
      '#title' => '交货方式',
      '#required' =>TRUE,
    );
    $form['funds_plan']['expire'] = array(
    	'#title' => '交货时间',
    	'#type' => 'textfield',
      '#size' => 12,
      '#required' =>TRUE,
    );
    $form['funds_plan']['remark'] = array(
    	'#title' => '说明',
    	'#type' => 'textarea',
    );

    $form['submit'] = array(
    	'#value' => '添加',
    	'#type' => 'submit',
    );
    //---给表单绑定时间控件的js文件
    $form['#attached']['library'] = array('core/jquery.ui.datepicker', 'common/hostspace.select_datetime');
    return $form;
  }

 /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $name = $form_state->getValue('name');
    $method = $form_state->getValue('method');
    $stamp = $form_state->getValue('expire');
    $remark = $form_state->getValue('remark');
    if(strtotime($stamp) < REQUEST_TIME) {
	   	$form_state->setErrorByName('expire', '交货时间不能在当前时间之前，请重新选择时间！');
    }
  
    $contract = $form_state->getValue('contract_id');
    $fields = array(
      'name' => $name,
      'delivery_stamp' => strtotime($stamp),
      'remark' => $remark,
      'status' => 0,
      'contract' => $contract,
      'method' => $method,
      'created' => REQUEST_TIME,
    );
    $form_state->fields = $fields;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $fields = $form_state->fields;
    $rs = \Drupal::service('contract.contractservice')->saveGoodsPlan($fields);
    if($rs) {
      drupal_set_message('交货计划添加成!');
      $form_state->setRedirectUrl(new Url('entity.host_contract.edit_form', array('host_contract' =>$fields['contract'] )));
    }else {
      drupal_set_message('交货计划添失败!', 'error');
    }
  }

}
