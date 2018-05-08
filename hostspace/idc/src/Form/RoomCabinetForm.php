<?php

/**
 * @file
 * Contains \Drupal\idc\Form\RoomCabinetForm.
 */

namespace Drupal\idc\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\hostlog\HostLogFactory;

class RoomCabinetForm extends ContentEntityForm {
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $storage = $this->entityManager->getStorage('room_cabinet');
    $code = $form_state->getValue('code')[0]['value'];
    $cabinets = $storage->loadByProperties(array('code' => $code));
    if(!empty($cabinets)) {
      $entity = $this->entity;
      if($entity->isNew()) {
        $form_state->setErrorByName('code',$this->t('The code: %code been exists.', array('%code' =>$code)));
      } else {
        $cabinet = reset($cabinets);
        if($cabinet->id() != $entity->id()) {
           $form_state->setErrorByName('code',$this->t('The code: %code been exists.', array('%code' =>$code)));
        }
      }
    }
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $action = 'insert';
    if(!$entity->isNew()) {
      $used_seat = $entity->getSimpleValue('used_seat');
      $seat = $entity->getSimpleValue('seat');
      $entity->set('unused_seat', $seat - $used_seat);
      $action = 'update';
    }
    $entity->save();
    HostLogFactory::OperationLog('idc')->log($entity, $action);
    drupal_set_message($this->t('Cabinet saved successfully'));
    $form_state->setRedirectUrl(new Url('entity.room.loke_over', array(
      'room' => $entity->getObjectId('rid')
    )));
  }
}
