<?php

/**
 * @file
 * Contains \Drupal\sop\Form\SopTaskFailureForm.
 */

namespace Drupal\sop\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;


/**
 * Provide for sop failure add.
 */
class SopTaskFailureForm extends ContentEntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $entity = $this->entity;
    $form['mips'] = array(
      '#type' => 'textfield',
      '#title' => '管理IP',
      '#required' => TRUE,
      // '#disabled' => $disabled_bool,.
      '#default_value' => $entity->isNew() ? '' : $entity->get('mips')->entity->label() . '(' . $entity->get('mips')->entity->id() . ')' ,
      '#autocomplete_route_name' => 'sop.sop_task_server.room.autocomplete',
      '#attributes' => array(
        'class' => array('form-control input-sm'),
        'js_room_mip' => 'autocomplete_task_room',
      ),
      '#attached' => array(
        'library' => array('sop/sop.sop_task_room.autocompletemip'),
      ),
    );
    if (!$entity->isNew()) {
      $client_user = user_load($entity->get('client_uid')->target_id);
    }
    $form['client_uid'] = array(
      '#type' => 'textfield',
      '#title' => '客户',
      '#id' => 'sop_task_room_client',
      '#description' => '格式:用户名|客户名|昵称|公司名',
      '#default_value' => isset($client_user) ? $client_user->getUsername() : '',
      // '#disabled' => $disabled_bool,.
      '#required' => TRUE,
      '#autocomplete_route_name' => 'sop.sop_task_server.client.autocomplete',
      '#attributes' => array(
        'class' => array('form-control input-sm'),
      ),
    );
    $options = sop_task_failure_level();
    // @todo 未完成选择客户显示其客户的管理IP
    $form['sop_type'] = array(
      '#type' => 'select',
      '#options' => $options,
      '#title' => t('工单类型'),
      '#default_value' => isset($this->entity) ? $this->entity->get('sop_type')->value : '',
    );
    $form['#theme'] = array('sop_task_failure_form');
    return $form;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function save(array $form, FormStateInterface $form_state) {
    $client_user = user_load_by_name($form_state->getValue('client_uid'));
    $this->entity->set('client_uid', $client_user->id());

    $ipm_string = explode('(', $form_state->getValue('mips'));
    $entity_ipm = entity_load_multiple_by_properties('ipm', array('ip' => $ipm_string[0]));
    $entity_ipm = reset($entity_ipm);
    $this->entity->set('mips', $entity_ipm->id());

    $this->entity->save();
    drupal_set_message($this->t('故障与重装工单保存成功!'));
    $form_state->setRedirectUrl(new Url('admin.sop_task.list'));
  }

}
