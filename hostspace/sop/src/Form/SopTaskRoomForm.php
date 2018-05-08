<?php

/**
 * @file
 * Contains \Drupal\sop\Form\SopTaskRoomForm.
 */

namespace Drupal\sop\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;


/**
 * Provide for sop room add.
 */
class SopTaskRoomForm extends ContentEntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    // $form = parent::form($form, $form_state);.
    $entity = $this->entity;
    $form['mip'] = array(
      '#type' => 'textfield',
      '#title' => '管理IP',
      '#required' => TRUE,
      // '#disabled' => $disabled_bool,.
      '#default_value' => $entity->isNew() ? '' : $entity->get('mips')->entity->label() . '(' . $entity->get('mips')->entity->id() . ')' ,
      '#autocomplete_route_name' => 'sop.sop_task_server.room.autocomplete',
      '#attributes' => array(
        'class' => array('form-control input-sm'),
      ),
    );

    $options = sop_task_room_level();
    $form['sop_type'] = array(
      '#type' => 'select',
      '#options' => $options,
      '#title' => t('工单类型'),
      '#default_value' => isset($this->entity) ? $this->entity->get('sop_type')->value : '',
    );
    $form['description'] = array(
      '#type' => 'text_format',
      '#title' => t('内容'),
      '#default_value' => isset($this->entity) ? $this->entity->get('description')->value : '',
    );
    $form['#theme'] = array('sop_task_room_form');
    return $form;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function save(array $form, FormStateInterface $form_state) {
    $ipm_string = explode('(', $form_state->getValue('mip'));
    $entity_ipm = entity_load_multiple_by_properties('ipm', array('ip' => $ipm_string[0]));
    $entity_ipm = reset($entity_ipm);
    $this->entity->set('mip', $entity_ipm->id());
    $this->entity->set('description', $form_state->getValue('description'));

    $this->entity->save();
    drupal_set_message($this->t('机房事务工单保存成功!'));
    $form_state->setRedirectUrl(new Url('admin.sop_task.list'));
  }

}
