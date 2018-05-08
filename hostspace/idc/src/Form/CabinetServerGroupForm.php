<?php

/**
 * @file
 * Contains \Drupal\idc\Form\CabinetServerGroupForm.
 */

namespace Drupal\idc\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\hostlog\HostLogFactory;

class CabinetServerGroupForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form =  parent::form($form, $form_state);
    unset($form['ipm_id']);
    unset($form['server_id']);
    unset($form['switch_p']);
    unset($form['switch_m']);
    $request = \Drupal::request()->attributes->all();
    $cabinetId = $request['room_cabinet'];
    $seat = $request['seat'];
    $entity = $this->entity;
    $cabinet = entity_load('room_cabinet', $cabinetId);
    $room = $cabinet->getObject('rid');
    $form['rid'] = array(
      '#type' => 'hidden',
      '#value' => $cabinet->get('rid')->target_id,
      '#attributes' => array(
        'id' => 'autocomplete_server_room',
      ),
    );
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
    $entity->set('ipm_id', 0);
    $entity->set('server_id', 0);
    $entity->set('switch_p', array('target_id' => 0, 'value' => 0));
    $entity->set('switch_m', array('target_id' => 0, 'value' => 0));
    $entity->set('parent_id', 0);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $entity = $this->entity;
    $cabinet_id = $entity->getObjectId('cabinet_id');
    $start_seat = $entity->getSimpleValue('start_seat');
    $seat_size = $form_state->getValue('seat_size')[0]['value'];
    if($seat_size == -1) {
      $seat_size = $form_state->getValue('seat_size')[0]['custom_value'];
    }
    $sum = $start_seat + $seat_size;
    for($i = $start_seat; $i< $sum; $i++) {
      $servers =  entity_load_multiple_by_properties('cabinet_server', array('start_seat' => $i, 'cabinet_id'=>$cabinet_id));
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
      $form_state->setErrorByName('seat_size',$this->t('The seat: #%seat been not exists.', array('%seat' =>$seatCount + 1)));
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
    //HostLogFactory::OperationLog('idc')->log($entity, $action);
    drupal_set_message('保存服务组成功');
    $form_state->setRedirectUrl(new Url('admin.idc.cabinet.seat', array(
      'room_cabinet' => $entity->getObjectId('cabinet_id')
    )));
  }
}
