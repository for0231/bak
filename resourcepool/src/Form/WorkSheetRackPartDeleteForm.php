<?php


namespace Drupal\resourcepool\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides the part delete confirmation form.
 */
class WorkSheetRackPartDeleteForm  extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return '确定要删除客户信息';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('admin.resourcepool.rackpart.list');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return "删除后将无法恢复";
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $entity->delete();
    drupal_set_message('删除成功');
    $form_state->setRedirectUrl($this->getCancelUrl());
  }
}
