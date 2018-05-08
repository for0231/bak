<?php

/**
 * @file
 * Contains \Drupal\part\Form\ServerTypeForm.
 */

namespace Drupal\server\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provide a form controller for server edit.
 */
class ServerTypeAddServerForm extends ContentEntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['number'] = array(
      '#type' => 'number',
      '#title' => t('Server Number'),
      '#description' => $this->t('The number of servers to storage'),
      '#default_value' => 1,
      '#min' => 1,
      '#weight' => 50
    );

    $form['server_description'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('The server description'),
      '#maxlength' => 255,
      '#weight' => 51
    );

    $options_rooms = array('' => '请选择机房');
    $entity_rooms = entity_load_multiple('room');
    foreach ($entity_rooms as $row) {
      $options_rooms[$row->id()] = $row->label();
    }
    $form['rid'] = array(
      '#type' => 'select',
      '#title' => '所属机房',
      '#required' => true,
      '#options' => $options_rooms,
      '#default_value' => 1,
      '#weight' => 45,
    );
    $form['name']['#disabled'] = true;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    //判断cpu
    $cpu_null = true;
    $cpu_val = $form_state->getValue('cpu');
    foreach($cpu_val as $item) {
      if(!empty($item['target_id'])) {
        $cpu_null = false;
        break;
      }
    }
    if($cpu_null) {
      $form_state->setErrorByName('cpu', t('Please complete the server parts cpu setting.'));
    }

    //判断主板
    $mainboard_null = $form_state->getValue('mainboard');
    if(empty($mainboard_null)) {
      $form_state->setErrorByName('mainboard', $this->t('Please complete the server parts mainboard setting.'));
    }
    //判断内存
    $memory_null = true;
    $memory_val = $form_state->getValue('memory');
     foreach($memory_val as $item) {
      if(!empty($item['target_id'])) {
        $memory_null = false;
        break;
      }
    }
    if($memory_null) {
      $form_state->setErrorByName('memory', $this->t('Please complete the server parts memory setting.')); 
    }
    //判断硬盘
    $harddisk_null = true;
    $harddisk_val = $form_state->getValue('harddisk');
    foreach($harddisk_val as $item) {
      if(!empty($item['target_id'])) {
        $harddisk_null = false;
        break;
      }
    }
    if($harddisk_null) {
      $form_state->setErrorByName('harddisk', $this->t('Please complete the server parts hard disk setting.'));
    }
    //判断机箱
    $classis_null = $form_state->getValue('chassis');
    if(empty($classis_null)) {
      $form_state->setErrorByName('chassis', $this->t('Please complete the server parts chassis setting.')); 
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $number = $form_state->getValue('number');
    $description = $form_state->getValue('server_description');
    $entity = $this->entity;
    $server_number = $entity->get('server_number')->value;
    $entity->set('server_number', $server_number + $number);

    $entity->new_server_number = $number;
    $entity->new_server_description = $description;
    $entity->save();

    drupal_set_message($this->t('Server added successfully'));
    $form_state->setRedirectUrl(new Url('admin.server_type'));
  }
}
