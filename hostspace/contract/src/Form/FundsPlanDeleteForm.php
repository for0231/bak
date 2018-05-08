<?php

/**
 * @file
 * Contains \Drupal\contract\Form\FundsPlanDeleteForm.
 */

namespace Drupal\contract\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

class FundsPlanDeleteForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'funds_plane_delete';
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
    $form['question'] = array( 
      '#type' => 'container',
      '#markup' => $this->t('是否要删除这条资金计划？此操作不可逆，请谨慎操作。')
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
    $id = $form_state->getValue('funds_plan');
    $contract = $form_state->getValue('host_contract');
    $rs = \Drupal::service('contract.contractservice')->deletePlanById($id);
    if($rs) {
      drupal_set_message('资金计划已删除!');
      $form_state->setRedirectUrl(new Url('entity.host_contract.edit_form', array('host_contract' =>$contract )));
    }else {
      drupal_set_message('资金未能删除!', 'error');
    }

  }
}
