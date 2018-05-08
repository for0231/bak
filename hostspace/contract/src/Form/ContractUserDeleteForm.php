<?php
/**
 * @file
 * Contains Drupal\contract\Form\ContractContractUserDeleteForm
 */

namespace Drupal\contract\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class ContractUserDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return strtr('是否删除该用户？用户名称：%name', array(
      '%name' => $this->entity->label()
      ));       
  }

	/**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('contract.user.admin');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '此用户将被删除。此操作不可逆，请谨慎操作！';
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
    $project = entity_load_multiple_by_properties('host_project', array('client' => $entity->id()));
    if(!empty($project)) {
      drupal_set_message('存在与此用户关联的项目，因此无法删除该用户！', 'warning');
      $form_state->setRedirectUrl($this->getCancelUrl());
      return;
    } 
    $contract = entity_load_multiple_by_properties('host_contract', array('client' => $entity->id()));
    if(!empty($contract)) {
      drupal_set_message('存在与此用户关联的合同，因此无法删除该用户！', 'warning');
      $form_state->setRedirectUrl($this->getCancelUrl());
      return;
    } 
    $entity->delete(); 
    drupal_set_message('该用户已经被删除！');
    $form_state->setRedirectUrl($this->getCancelUrl());
  }




}
