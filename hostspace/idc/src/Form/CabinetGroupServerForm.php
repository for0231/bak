<?php

/**
 * @file
 * Contains \Drupal\idc\Form\CabinetGroupServerForm.
 */

namespace Drupal\idc\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\hostlog\HostLogFactory;

class CabinetGroupServerForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form =  parent::form($form, $form_state);
    unset($form['group_name']);
    unset($form['seat_size']);
    $request = \Drupal::request()->attributes->all();
    $cabinetId = $request['room_cabinet'];
    $group_id = $request['group_id'];
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
    $form['group_node'] = array(
      '#type' => 'textfield',
      '#title' => t('Group node'),
      '#weight' => 24
    );
    $form['description'] = array(
      '#type' => 'textarea',
      '#title' => t('Description'),
      '#weight' => 25
    );
    $entity->set('cabinet_id', $cabinetId);
    $entity->set('parent_id', $group_id);
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
    $group_node = trim($form_state->getValue('group_node'));
    if(empty($group_node)) {
      $form_state->setErrorByName('group_node', '请输入组节点');
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
    $node = $form_state->getValue('group_node');
    $entity->set('group_name', $node);
    $entity->set('start_seat', 0);
    $entity->set('seat_size', 0);
    $entity->save();

    $description = $form_state->getValue('description');
    $entity_ip = $entity->getObject('ipm_id');
    $entity_ip->set('description', $description);
    $entity_ip->set('port', $entity->getSimpleValue('parent_id'));
    $entity_ip->save();
    HostLogFactory::OperationLog('idc')->log($entity, $action);

    drupal_set_message('保存服务组成功');
    $form_state->setRedirectUrl(new Url('admin.idc.cabinet.seat', array(
      'room_cabinet' => $entity->getObjectId('cabinet_id')
    )));
  }
}
