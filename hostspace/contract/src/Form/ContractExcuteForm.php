<?php
/**
 * @file
 * Contains Drupal\contract\Form\ContractExcuteForm
 */

namespace Drupal\contract\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class ContractExcuteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $diff = $this->getDiff();
    if($diff > 0) {
      $msg =  strtr('该合同的资金计划总额跟合同总金额不一致,相差金额为：￥%diff。请完善资金计划！', array('%diff' => $diff));
      drupal_set_message($msg, 'warning');
    }
    return strtr('是否执行此合同？合同编号：%code', array(
      '%code' => $this->entity->label()
      ));       
  }

  private function getDiff() {
    $host_contract = $this->entity->id();
    // 合同总金额
    $total = $this->entity->getproperty('amount');
    // 收款总计
    $income = \Drupal::service('contract.contractservice')->getAmount(1, $host_contract);
    // 付款总计
    $pay= \Drupal::service('contract.contractservice')->getAmount(2, $host_contract);
    $diff = ($total- $income + $pay);
    return $diff;
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
    return '此合同将开始执行，其所有的资金计划也将开始执行。';
  }
  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Execute');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $diff = $this->getDiff();
    if($diff > 0) {
      $form_state->setRedirectUrl($this->getCancelUrl());
      return;
    }
    $entity = $this->entity;
    $entity->set('status', 2)->save();
    drupal_set_message('指定合同已经开始执行！');
    $form_state->setRedirectUrl($this->getCancelUrl());
  }




}
