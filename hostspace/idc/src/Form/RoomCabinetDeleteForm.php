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
class RoomCabinetDeleteForm  extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete this idc room cabinet ? code: %code。', array(
      '%code' => $this->entity->label()
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.room.loke_over', array('room' => $this->entity->getObjectId('rid')));
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This cabinet will be delete. Please confirm this cabinet has not any been used storage.');
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $entities = entity_load_multiple_by_properties('cabinet_switch', array('cabinet_id' => $entity->id()));
    if(!empty($entities)) {
      $form_state->setErrorByName('op', t('The cabinet is in use and cannot be deleted'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $entity->delete();
    HostLogFactory::OperationLog('idc')->log($entity, 'delete');
    drupal_set_message($this->t('Cabinet deleted successfully'));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }
}
