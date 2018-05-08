<?php

/**
 * @file
 * Contains \Drupal\contract\Form\GoodsPlanCompleteForm.
 */

namespace Drupal\contract\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

class GoodsPlanCompleteForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'goods_plane_complete';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $goods_plan=null, $host_contract=null) {
    $form['goods_plan'] = array(
      '#type' => 'hidden',
      '#value' => $goods_plan
    );
    $form['host_contract'] = array(
      '#type' => 'hidden',
      '#value' => $host_contract
    );   
    $form['question'] = array( 
      '#type' => 'container',
      '#markup' => '是否确认完成该条交货计划。'
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
    $id = $form_state->getValue('goods_plan');
    $contract = $form_state->getValue('host_contract');
    $rs = \Drupal::service('contract.contractservice')->modifyGoods($id, array('status' => 1));
    if($rs) {
      drupal_set_message('交货计划已完成!');
      $form_state->setRedirectUrl(new Url('entity.host_contract.edit_form', array('host_contract' =>$contract )));
    }else {
      drupal_set_message('操作失败，请稍候重试!', 'error');
    }

  }
}
