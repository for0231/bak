<?php

/**
 * @file
 * Contains \Drupal\idc\Form\CabinetServerDeleteForm.
 */

namespace Drupal\idc\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\hostlog\HostLogFactory;

/**
 * Provides the part delete confirmation form.
 */
class CabinetServerDeleteForm  extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $entity = $this->entity;
    if($entity->getObjectId('ipm_id') > 0) {
      return $this->t('Are you sure you want to delete this cabinet server ? server code: %code。', array(
        '%code' => $this->entity->getObject('server_id')->label(),
      ));
    } else {
      return $this->t('Are you sure you want to delete this cabinet server group? group: %group。', array(
        '%group' => $this->entity->getSimpleValue('group_name'),
      ));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $entity = $this->entity;
    if($parentId = $entity->getSimpleValue('parent_id')) {
      return new Url('admin.idc.seat.server.group', array('groupId' => $parentId));
    }
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
    if($entity->getObjectId('ipm_id') > 0) {
      $ipm = $entity->getObject('ipm_id');
      if($ipm->get('status')->value == 5) {
        $form_state->setErrorByName('op', t('The server is in use and cannot be deleted'));
      }
    } else {
      $child_datas = entity_load_multiple_by_properties('cabinet_server', array('parent_id' => $entity->id()));
      foreach($child_datas as $item) {
        $ipm = $item->getObject('ipm_id');
        if($ipm->get('status')->value == 5) {
          $form_state->setErrorByName('op', t('The server is in use and cannot be deleted'));
          return;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $child_datas = entity_load_multiple_by_properties('cabinet_server', array('parent_id' => $entity->id()));
    foreach($child_datas as $item) {
      $item->delete();
      HostLogFactory::OperationLog('idc')->log($item, 'delete');
    }
    $entity->delete();
    HostLogFactory::OperationLog('idc')->log($entity, 'delete');
    drupal_set_message($this->t('The server successfully removed'));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }
}
