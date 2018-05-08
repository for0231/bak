<?php

/**
 * @file
 * Contains \Drupal\idc\Form\CabinetServerForm.
 */

namespace Drupal\idc\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\hostlog\HostLogFactory;

class CabinetServerForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form =  parent::form($form, $form_state);
    unset($form['group_name']);
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
    $form['room_id'] = array(
      '#type' => 'value',
      '#value' => $cabinet->get('rid')->target_id
    );
    $_SESSION['room_cabinet_server_ipm_rid'] = $cabinet->get('rid')->target_id;
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
    $entity->set('parent_id', 0);
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
    $manage_ip = $form_state->getValue('ipm_id')[0]['target_id'];
    if(empty($manage_ip)) {
      $form_state->setErrorByName('ipm_id', '请选择管理IP');
      return;
    }
    $room_id = $form_state->getValue('room_id');
    $ipm = entity_load('ipm', $manage_ip);
    if($ipm->get('rid')->value != $room_id) {
      $form_state->setErrorByName('ipm_id', '管理IP不属于此机房');
    }

    $server_id = $form_state->getValue('server_id')[0]['target_id'];
    if(empty($server_id)) {
      $form_state->setErrorByName('server_id', '请选择服务器');
      return;
    }
    $switch_p = $form_state->getValue('switch_p')[0]['target_id'];
    $switch_p_value = $form_state->getValue('switch_p')[0]['value'];
    if(empty($switch_p) || empty($switch_p_value)) {
      $form_state->setErrorByName('switch_p', '请选择交换机P');
      return;
    }
    $switch_m = $form_state->getValue('switch_m')[0]['target_id'];
    $switch_m_value = $form_state->getValue('switch_m')[0]['value'];
    if(empty($switch_m) || empty($switch_m_value)) {
      $form_state->setErrorByName('switch_m', '请选择交换机M');
      return;
    }

    $storage = $this->entityManager->getStorage('cabinet_server');
    $cabinet_ipms = $storage->loadByProperties(array('ipm_id' => $manage_ip));
    if(!empty($cabinet_ipms)) {
      $cabinet_ipm = reset($cabinet_ipms);
      $ipm_text = $cabinet_ipm->getObject('ipm_id')->label();
      $form_state->setErrorByName('ipm_id',$this->t('The manage ip: %ip been exists.', array('%ip' =>$ipm_text)));
    }

    $cabinet_servers = $storage->loadByProperties(array('server_id' => $server_id));
    if(!empty($cabinet_servers)) {
      $cabinet_server = reset($cabinet_servers);
      $server_text = $cabinet_server->getObject('server_id')->label();
      $form_state->setErrorByName('server_id',$this->t('The server: %server been exists.', array('%server' =>$server_text)));
    }

    $entity = $this->entity;
    $cabinet_id = $entity->getObjectId('cabinet_id');
    $start_seat = $entity->getSimpleValue('start_seat');
    $seat_size = $form_state->getValue('seat_size')[0]['value'];
    if($seat_size == -1) {
      $seat_size = $form_state->getValue('seat_size')[0]['custom_value'];
    }
    if(empty($seat_size)) {
      $form_state->setErrorByName('seat_size', '请选择机型');
      return;
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

    $start_seat = $entity->getSimpleValue('start_seat');
    $description = $form_state->getValue('description');
    $entity_ip = $entity->getObject('ipm_id');
    $entity_ip->set('description', $description);
    $entity_ip->set('port', $start_seat);
    $entity_ip->save();
    HostLogFactory::OperationLog('idc')->log($entity, $action);

    drupal_set_message($this->t('Increase the server successfully'));
    $form_state->setRedirectUrl(new Url('admin.idc.cabinet.seat', array(
      'room_cabinet' => $entity->getObjectId('cabinet_id')
    )));
  }
}
