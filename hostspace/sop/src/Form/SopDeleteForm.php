<?php

/**
 * @file
 * Contains \Drupal\sop\Form\SopDeleteForm.
 */

namespace Drupal\sop\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides the part delete confirmation form.
 */
class SopDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete this sop ? Module: %module, Id: %id', array(
      '%module' => $this->entity->get('module')->value,
      '%id' => $this->entity->get('sid')->value,
    ));
  }
  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    // Point to the entity of which this sop.
    return new Url('admin.sop_task.list');
  }
  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This sop will be delete. Please confirm this sop has not any been used storage.');
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
    // Delete the sop task(as server, room, ...) entity first.
    $entity = $this->entity;
    $module = $entity->get('module')->value;
    $sid = $entity->get('sid')->value;
    $sop_task = entity_load($module, $sid);

    // 删除之前，记录日志.
    $sop_task->other_status = 'sop_common_task';
    $entity->other_status = 'sop_specified_task';
    HostLogFactory::OperationLog('sop')->log($sop_task, 'delete');
    HostLogFactory::OperationLog('sop')->log($entity, 'delete');
    // Delete the sop task.
    $sop_task->delete();
    // Delete the sop and its replies.
    $entity->delete();

    drupal_set_message($this->t('The sop have been deleted.'));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
