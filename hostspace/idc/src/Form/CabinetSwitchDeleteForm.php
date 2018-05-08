<?php

/**
 * @file
 * Contains \Drupal\idc\Form\CabinetSwitchDeleteForm.
 */

namespace Drupal\idc\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\hostlog\HostLogFactory;

/**
 * Provides the part delete confirmation form.
 */
class CabinetSwitchDeleteForm  extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete this cabinet switch ? switch ip: %ip。', array(
      '%ip' => $this->entity->getObject('ips_id')->label()
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('admin.idc.cabinet.seat', array(
      'room_cabinet' => $this->entity->getObjectId('cabinet_id')
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This part will be delete. Please confirm this cabinet has not any been used storage.');
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $cabinet_id = $entity->getObjectId('cabinet_id');
    $ips_id =  $entity->getObjectId('ips_id');
    $entities = entity_load_multiple_by_properties('cabinet_server', array('cabinet_id' => $cabinet_id, 'switch_p__target_id' => $ips_id));
    if(!empty($entities)) {
      $form_state->setErrorByName('op', t('The switch is in use and cannot be deleted'));
    } else {
      $entities = entity_load_multiple_by_properties('cabinet_server', array('cabinet_id' => $cabinet_id, 'switch_m__target_id' => $ips_id));
      if(!empty($entities)) {
        $form_state->setErrorByName('op', t('The switch is in use and cannot be deleted'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $entity->delete();
    HostLogFactory::OperationLog('idc')->log($entity, 'delete');
    drupal_set_message($this->t('The switch successfully removed'));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }
}
