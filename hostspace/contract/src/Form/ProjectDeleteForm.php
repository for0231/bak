<?php
/**
 * @file
 * Contains Drupal\contract\Form\ProjectDeleteForm
 */

namespace Drupal\contract\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class ProjectDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return strtr('是否要删除此项目？项目编号：%code', array(
      '%code' => $this->entity->label()
      ));       
  }

	/**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('project.admin');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '此项目将被删除。';
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
    $contract = entity_load_multiple_by_properties('host_contract', array('project' => $entity->id()));
    if(!empty($contract)) {
      drupal_set_message('存在与此项目关联的合同，因此无法删除该项目！', 'warning');
      $form_state->setRedirectUrl($this->getCancelUrl());
      return;
    } 
    $this->entity->delete();
    drupal_set_message('指定项目已经成功删除！');
    $form_state->setRedirectUrl($this->getCancelUrl());
  }




}
