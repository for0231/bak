<?php

/**
 * @file
 * Contains \Drupal\idc\Form\RoomDeleteForm.
 */

namespace Drupal\idc\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\hostlog\HostLogFactory;

/**
 * Provides the part delete confirmation form.
 */
class RoomDeleteForm  extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete this idc room ? name: %name。', array(
      '%name' => $this->entity->label()
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('admin.idc.room');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This room will be delete. Please confirm this idc room has not any been used storage.');
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $entities = entity_load_multiple_by_properties('room_cabinet', array('rid' => $entity->id()));
    if(!empty($entities)) {
      $form_state->setErrorByName('op', t('The room is in use and cannot be deleted'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $entity->delete();
    HostLogFactory::OperationLog('idc')->log($entity, 'delete');
    drupal_set_message($this->t('Room deleted successfully'));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }
}
