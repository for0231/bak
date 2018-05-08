<?php
/**
 * @file
 * Contains Drupal\contract\Form\ContractCompleteForm
 */

namespace Drupal\contract\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class ContractCompleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return strtr('是否结束此合同？合同编号：%code', array(
      '%code' => $this->entity->label()
      ));       
  }

	/**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('contract.admin');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '此合同将结束，其所有的资金计划也将结束。';
  }
  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Complete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $entity->set('status', 4)->save();
    // 查询该合同下的所有资金计划
    $data = \Drupal::service('contract.contractservice')->getContractAllPlan($this->entity->id());
    if(!empty($data)) {
      foreach($data as $plan) {
        \Drupal::service('contract.contractservice')->modifyFunds($plan->id, array('status' => 1, 'success_time' => REQUEST_TIME));      
      }    
    }

    // 查询该合同下的所有货物计划
    $data_goods = \Drupal::service('contract.contractservice')->getAllGoodsPlanByContract($this->entity->id());
    if(!empty($data_goods)) {
      foreach($data_goods as $plan) {
        \Drupal::service('contract.contractservice')->modifyGoods($plan->id, array('status' => 1));      
      }    
    }
    drupal_set_message('指定合同已经开始结束！');
    $form_state->setRedirectUrl($this->getCancelUrl());
  }




}
