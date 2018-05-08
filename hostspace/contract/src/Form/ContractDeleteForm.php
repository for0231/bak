<?php
/**
 * @file
 * Contains Drupal\contract\Form\ContractDeleteForm
 */

namespace Drupal\contract\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class ContractDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return strtr('是否删除此合同？合同编号：%code', array(
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
    return '此合同将被删除，其所有的资金计划也将同时被删除。';
  }
  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $entity->delete();
    // 查询该合同下的所有资金计划
    $data = \Drupal::service('contract.contractservice')->getContractAllPlan($this->entity->id());
    if(!empty($data)) {
      foreach($data as $plan) {
        \Drupal::service('contract.contractservice')->deletePlanById($plan->id);      
      }    
    }
    drupal_set_message('指定合同已经和其所有的资金计划已经被删除！');
    $form_state->setRedirectUrl($this->getCancelUrl());
  }




}
