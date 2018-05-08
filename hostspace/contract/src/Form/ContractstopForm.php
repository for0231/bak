<?php
/**
 * @file
 * Contains Drupal\contract\Form\ContractstopForm
 */

namespace Drupal\contract\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class ContractstopForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return strtr('是否终止此合同？合同编号：%code', array(
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
    return '此合同将终止，其所有的资金计划也将终止。';
  }
  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Stop');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $entity->set('status', 3);
    $entity->save();
    drupal_set_message('指定合同已经终止！');
    $form_state->setRedirectUrl($this->getCancelUrl());
  }




}
