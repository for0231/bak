<?php

/**
 * @file
 * Contains \Drupal\idc\Form\CabinetServerMoveForm.
 */

namespace Drupal\idc\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\hostlog\HostLogFactory;

class CabinetServerMoveForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'idc_move_server';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $cabinet_server = null) {
    $entity = entity_load('cabinet_server', $cabinet_server);
    if(empty($entity)) {
      return;
    }
    $cabinet = $entity->getObject('cabinet_id');
    $room_id = $cabinet->getObjectId('rid');
    $room_cabinets = entity_load_multiple_by_properties('room_cabinet', array('rid' => $room_id));
    $cabinet_options = array();
    foreach($room_cabinets as $key =>$room_cabinet) {
      $cabinet_options[$key] = $room_cabinet->label();
    }
    $form['cabinet_server_id'] = array(
      '#type' => 'value',
      '#value' => $cabinet_server
    );
    $form['move_cabinet'] = array(
      '#type' => 'select',
      '#title' => $this->t('Moving to the cabinet'),
      '#options' => $cabinet_options,
      '#required' => true,
      '#ajax' => array(
         'callback' => '::loadMoveCabinetInfo',
         'wrapper' => 'move_server_wrapper',
         'method' => 'html'
      ),
    );
    $form['cabinet_info'] = array(
      '#type' => 'container',
      '#id' => 'move_server_wrapper'
    );
    $seat_options = array();
    $switch_options = array();
    if($move_cabinet_id = $form_state->getValue('move_cabinet')) {
      $seat_options = $this->getSeatOptions($move_cabinet_id);
      $switch_options = $this->getSwitchOptions($move_cabinet_id);
    }
    $form['cabinet_info']['detail']['move_seat'] = array(
      '#type' => 'select',
      '#title' => $this->t('Please select a seat'),
      '#options' => $seat_options,
      '#required' => true
    );

    $form['cabinet_info']['detail']['switch_p'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('field-type-multi-part')
      ),
      'widget' => array(
        'switch_p_target' => array(
          '#type' => 'select',
          '#title' => $this->t('Switch P'),
          '#options' => $switch_options,
          '#required' => true,
          '#parents' => array('switch_p', 'target_id'),
        ),
        'switch_p_port' => array(
          '#type' => 'number',
          '#size' => 5,
          '#required' => true,
          '#parents' => array('switch_p', 'value'),
        )
      )
    );

    $form['cabinet_info']['detail']['switch_m'] = array(  
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('field-type-multi-part')
      ),
      'widget' => array(
        'switch_m_target' => array(
          '#type' => 'select',
          '#title' => $this->t('Switch M'),
          '#options' => $switch_options,
          '#required' => true,
          '#parents' => array('switch_m', 'target_id'),
        ),
        'switch_m_port' => array(
          '#type' => 'number',
          '#size' => 5,
          '#required' => true,
          '#parents' => array('switch_m', 'value'),
        )
      )
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Confirm')
    );
    
    $form['#attached']['library'] = array('server/drupal.multi-part-widget');

    return $form; 
  }

  public static function loadMoveCabinetInfo(array $form, FormStateInterface $form_state) {
    return $form['cabinet_info']['detail'];
  }

  /**
   * 获取机位信息
   */
  private function getSeatOptions($cabinet_id) {
    $options = array();
    $cabinet = entity_load('room_cabinet', $cabinet_id);
    $seat_number = $cabinet->getSimpleValue('seat');
    for($i = 1; $i <= $seat_number; $i++) {
      $entities = entity_load_multiple_by_properties('cabinet_server', array('cabinet_id' =>$cabinet_id, 'start_seat' => $i));
      if(empty($entities)) {
        $entities = entity_load_multiple_by_properties('cabinet_switch', array('cabinet_id' =>$cabinet_id, 'start_seat' => $i));
      }
      if(empty($entities)) {
        $options[$i] = '#' . $i; 
      } else {
        $entity = reset($entities);
        $size = $entity->getSimpleValue('seat_size');
        if($size > 1) {
          $i= $i + $size -1;
        }
      }
    }
    return $options;
  }

  /**
   * 获取交换机信息
   */
  private function getSwitchOptions($cabinet_id) {
    $options = array();
    $list = entity_load_multiple_by_properties('cabinet_switch', array('cabinet_id' => $cabinet_id));
    foreach($list as $entity) {
      $ips = $entity->getObject('ips_id');
      $options[$ips->id()] = $ips->label();
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $move_cabinet = $form_state->getValue('move_cabinet');
    $move_seat = $form_state->getValue('move_seat');
    if(empty($move_seat)) {
       return;
    }
    //得到机位大小
    $cabinet_server_id = $form_state->getValue('cabinet_server_id');
    $cabinet_server = entity_load('cabinet_server', $cabinet_server_id);
    $seat_size = $cabinet_server->getSimpleValue('seat_size');
    $sum = $move_seat + $seat_size;
     
    $cabinet = entity_load('room_cabinet', $move_cabinet);
    $seat_number = $cabinet->getSimpleValue('seat');
    if($sum > $seat_number + 1) {
      $form_state->setErrorByName('seat_size',$this->t('The seat: #%seat been not exists.', array('%seat' =>$seat_number + 1)));
      return;
    }
    for($i = $move_seat; $i< $sum; $i++) {
      $servers =  entity_load_multiple_by_properties('cabinet_server', array('start_seat' => $i, 'cabinet_id'=>$move_cabinet));
      if(!empty($servers)) {
        $form_state->setErrorByName('seat_size',$this->t('The seat: #%seat been Use.', array('%seat' =>$i)));
        break;
      }
      $switchs =  entity_load_multiple_by_properties('cabinet_switch', array('start_seat' => $i, 'cabinet_id'=>$move_cabinet));
      if(!empty($switchs)) {
        $form_state->setErrorByName('seat_size',$this->t('The seat: #%seat been Use.', array('%seat' =>$i)));
        break;
      }
    }
  }
 
 
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cabinet_server_id = $form_state->getValue('cabinet_server_id');
    $cabinet_server = entity_load('cabinet_server', $cabinet_server_id);    
    $old_cabinet_id = $cabinet_server->getObjectId('cabinet_id');
    $old_cabinet_name = $cabinet_server->getObject('cabinet_id')->label();
    $old_seat = $cabinet_server->getSimpleValue('start_seat');
    //移动的信息
    $move_cabinet = $form_state->getValue('move_cabinet');
    $move_seat = $form_state->getValue('move_seat');
    $switch_p = $form_state->getValue('switch_p');
    $switch_m = $form_state->getValue('switch_m');
    $cabinet_server->set('cabinet_id', $move_cabinet);
    $cabinet_server->set('switch_p', $switch_p);
    $cabinet_server->set('switch_m', $switch_m);
    $cabinet_server->set('start_seat', $move_seat);
    if($old_cabinet_id != $move_cabinet) {
      $cabinet_server->move_cabinet_before = $old_cabinet_id;
    }
    $cabinet_server->save();
    $cabinet_server->move_seat_before = $old_seat;
    $cabinet_server->move_cabinet_label_before = $old_cabinet_name;
    HostLogFactory::OperationLog('idc')->log($cabinet_server, 'update');
    $form_state->setRedirectUrl(new Url('admin.idc.cabinet.seat', array('room_cabinet' => $old_cabinet_id)));
    drupal_set_message('Move machine successfully');
  }
}
