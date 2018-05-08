<?php

/**
 * @file
 * Contains \Drupal\contract\Form\GoodsPlanDeleteForm.
 */

namespace Drupal\contract\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

class GoodsPlanDeleteForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'goods_plane_delete';
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
      '#markup' => $this->t('是否要删除此交货计划？此操作不可逆，请谨慎操作。')
    );
    $form['yes'] = array(
      '#type' => 'submit',
      '#value' => 'Delete',        
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
    $rs = \Drupal::service('contract.contractservice')->deleteGoodsPlanById($id);
    if($rs) {
      drupal_set_message('计划已删除!');
      $form_state->setRedirectUrl(new Url('entity.host_contract.edit_form', array('host_contract' =>$contract )));
    }else {
      drupal_set_message('计划未能删除!', 'error');
    }

  }
}
