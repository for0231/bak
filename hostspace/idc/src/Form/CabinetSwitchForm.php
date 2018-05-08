<?php

/**
 * @file
 * Contains \Drupal\idc\Form\CabinetSwitchForm.
 */

namespace Drupal\idc\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\hostlog\HostLogFactory;

class CabinetSwitchForm extends ContentEntityForm { 
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form =  parent::form($form, $form_state); 
    $request = \Drupal::request()->attributes->all();
    $cabinetId = $request['room_cabinet'];
    $seat = $request['seat'];
    $entity = $this->entity;
    $cabinet = entity_load('room_cabinet', $cabinetId);
    $room = $cabinet->getObject('rid'); 
    $form['room_label'] = array(
      '#type' => 'label',
      '#title' => t('Room：@room', array(
        '@room' => $room->label()
      ))
    );
    $form['cabinet_label'] = array(
      '#type' => 'label',
      '#title' => t('Cabinet：@cabinet', array(
        '@cabinet' => $cabinet->label()
      ))
    ); 
    $entity->set('cabinet_id', $cabinetId);
    $entity->set('start_seat', $seat);
    
    $form['description'] = array(
      '#type' => 'textarea',
      '#title' => t('Description'),
      '#weight' => 25 
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $switch_ip = $form_state->getValue('ips_id')[0]['target_id'];
    $storage = $this->entityManager->getStorage('cabinet_switch');
    $cabinet_switchs = $storage->loadByProperties(array('ips_id' => $switch_ip));
    if(!empty($cabinet_switchs)) {
        $cabinet_switch = reset($cabinet_switchs);
        $ips_text = $cabinet_switch->getObject('ips_id')->label();
        $form_state->setErrorByName('ips_id',$this->t('The switch ip: %ip been exists.', array('%ip' =>$ips_text)));
    }

    $entity = $this->entity;
    $cabinet_id = $entity->getObjectId('cabinet_id');
    $start_seat = $entity->getSimpleValue('start_seat');
    $seat_size = $form_state->getValue('seat_size')[0]['value'];
    $sum = $start_seat + $seat_size;
    for($i = $start_seat; $i< $sum; $i++) {
      $servers =  entity_load_multiple_by_properties('cabinet_server', array('start_seat' => $i,'cabinet_id'=>$cabinet_id));
      if(!empty($servers)) {
        $form_state->setErrorByName('seat_size',$this->t('The seat: #%seat been Use.', array('%seat' =>$i)));
        break; 
      }
      $switchs =  entity_load_multiple_by_properties('cabinet_switch', array('start_seat' => $i, 'cabinet_id'=>$cabinet_id));
      if(!empty($switchs)) {
        $form_state->setErrorByName('seat_size',$this->t('The seat: #%seat been Use.', array('%seat' =>$i)));
        break; 
      }
    }
    
    $seatCount = $entity->getObject('cabinet_id')->getSimpleValue('seat');
    if($start_seat + $seat_size > $seatCount + 1) {
      $form_state->setErrorByName('seat_size',$this->t('The seat: #%seat not exists.', array('%seat' =>$seatCount + 1)));
    }
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $action = 'update';
    if($entity->isnew()) {
      $action = 'insert';
    }
    $entity->save();
    $description = $form_state->getValue('description');
    $entity_ip = $entity->getObject('ips_id'); 
    $entity_ip->set('description', $description);
    $entity_ip->save();
    HostLogFactory::OperationLog('idc')->log($entity, $action);
    drupal_set_message($this->t('Increase the switch successfully'));
    $form_state->setRedirectUrl(new Url('admin.idc.cabinet.seat', array(
      'room_cabinet' => $entity->getObjectId('cabinet_id')
    )));
  }
} 
