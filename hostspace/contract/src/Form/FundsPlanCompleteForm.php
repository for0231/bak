<?php

/**
 * @file
 * Contains \Drupal\contract\Form\FundsPlanCompleteForm.
 */

namespace Drupal\contract\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

class FundsPlanCompleteForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'funds_plane_complete';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $funds_plan=null, $host_contract=null) {
    $form['funds_plan'] = array(
      '#type' => 'hidden',
      '#value' => $funds_plan
    );
    $form['host_contract'] = array(
      '#type' => 'hidden',
      '#value' => $host_contract
    );   
    $plan = \Drupal::service('contract.contractservice')->getplanById($funds_plan);
    $form['question'] = array( 
      '#type' => 'container',
      '#markup' => strtr('该条资金计划是否确认完成？。资金总额：￥%amount', array('%amount' => $plan->amount))
    );
    $form['yes'] = array(
      '#type' => 'submit',
      '#value' => '确认',        
    );
    $form['cancel'] = array(
      '#type' => 'link',
      '#title' => 'Cancel',
      '#url' => new Url('entity.host_contract.edit_form', array('host_contract' => $host_contract))
    );
    return $form;
  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $id = $form_state->getValue('funds_plan');
    $contract = $form_state->getValue('host_contract');
    $rs = \Drupal::service('contract.contractservice')->modifyFunds($id, array('status' => 1, 'success_time' => REQUEST_TIME));
    if($rs) {
      drupal_set_message('资金计划已完成!');
      $form_state->setRedirectUrl(new Url('contract.funds.income_list'));
    }else {
      drupal_set_message('操作失败，请稍候重试!', 'error');
    }

  }
}
