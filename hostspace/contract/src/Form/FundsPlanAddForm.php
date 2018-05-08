<?php
/**
 * @file
 * Contains Drupal\contract\Form\FundsPlanAddForm
 */

namespace Drupal\contract\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class FundsPlanAddForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'add_funds_plane';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $host_contract=null) {
    $contract_entity = entity_load('host_contract', $host_contract);
    $form['amount_detail'] = array(
      '#type' => 'fieldset',
      '#title' => '资金计划总额必须与合同金额一致！',
      '#attributes' => array('class' => array('container-inline')),
      '#disabled' => TRUE
    );
    $total = $contract_entity->getproperty('amount');
    $form['amount_detail']['contract_amount'] = array(
      '#type' => 'textfield',
      '#value' =>  $total,
      '#title' => '合同总金额'. '(RMB)',
      '#size' =>12 
    );
    $rsin = \Drupal::service('contract.contractservice')->getAmount(1, $host_contract);
    $income = $rsin ? $rsin : 0;
    $form['amount_detail']['income'] = array(
      '#type' => 'textfield',
      '#value' => $income,
      '#title' => '收款金额'. '(RMB)',
      '#size' =>12 
    ); 
    $rsout= \Drupal::service('contract.contractservice')->getAmount(2, $host_contract);
    $pay = $rsout ? $rsout : 0;
    $form['amount_detail']['spend'] = array(
      '#type' => 'textfield',
      '#value' => $pay,
      '#title' => '付款金额'. '(RMB)',
      '#size' =>12 
    ); 
     $form['amount_detail']['diff'] = array(
      '#type' => 'textfield',
      '#value' => ($total- ($income-$pay)),
      '#title' => '差额'. '(RMB)',
      '#size' =>12 
    ); 
    $form['contract_id'] = array(
      '#type' => 'hidden',
      '#value' =>$host_contract
    );
    $form['funds_plan'] = array(
      '#type' => 'fieldset',
      '#title' => '资金计划',
      '#open' => true,
    );
    $form['funds_plan']['amount'] = array(
      '#type' => 'number',
      '#required' =>TRUE,
      '#title' => '金额'
    );
    $form['funds_plan']['type'] = array(
      '#type' => 'select',
      '#title' => '资金性质',
      '#required' =>TRUE,
      '#options' => fundsType()
    );
    $form['funds_plan']['method'] = array(
      '#type' => 'select',
      '#title' => '结算方式',
      '#required' =>TRUE,
      '#options' => fundsMethod()
    );
    $form['funds_plan']['date'] = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('container-inline')),
    );
    $form['funds_plan']['date']['expire'] = array(
    	'#title' => '完成时间',
    	'#type' => 'textfield',
      '#size' => 12,
      '#required' =>TRUE,
    );
    $form['funds_plan']['remark'] = array(
    	'#title' => '资金说明',
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
    $total = $form_state->getValue('contract_amount');
    $diff = $form_state->getValue('diff');
    $amount = $form_state->getValue('amount');
    if($diff < $amount) {
	   	$form_state->setErrorByName('amount', '资金计划总额必须不能大于合同总额！目前合同差额为：'.$diff);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $amount = $form_state->getValue('amount');
    $type = $form_state->getValue('type');
    $success_time = $form_state->getValue('expire');
    $remark = $form_state->getValue('remark');
    $status = 0;
    $contract = $form_state->getValue('contract_id');
    $method = $form_state->getValue('method');
    $created = REQUEST_TIME;
    $fields = array(
      'amount' =>$amount,
      'type' =>$type ,
      'plan_time' =>strtotime($success_time),
      'remark' =>$remark,
      'status' =>$status = 0,
      'contract' =>$contract,
      'method' =>$method,
      'created' =>$created,
    );
    $rs = \Drupal::service('contract.contractservice')->saveFundsPlan($fields);
    if($rs) {
      drupal_set_message('资金计划添加成!');
      $form_state->setRedirectUrl(new Url('entity.host_contract.edit_form', array('host_contract' =>$contract )));
    }else {
      drupal_set_message('资金计划添失败!', 'error');
    }
  }

}
