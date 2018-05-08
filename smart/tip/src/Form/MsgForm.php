<?php

namespace Drupal\tip\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 *
 */
class MsgForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $entity = $this->entity;
    $multiple_roles = \Drupal::entityTypeManager()->getStorage('user_role')->loadMultiple();

    foreach ($multiple_roles as $key => $val) {
      $option_roles[$key] = $val->label();
    }
    $form['destination'] = [
      '#type' => 'details',
      '#title' => '目标用户',
      '#description' => '请选择两项方式的任意一个进行消息发送.',
      '#open' => TRUE,
    ];

    $form['destination']['group'] = [
      '#type' => 'select',
      '#title' => '用户组',
      '#options' => $option_roles,
      '#multiple' => 'multiple',
      '#default_value' => 0,
    ];
    $form['destination']['user'] = [
      '#type' => 'textfield',
      '#title' => '特定用户',
      '#id' => 'tip_msg_user',
      '#autocomplete_route_name' => 'tip.msg.user.autocomplete',
      '#default_value' => isset($entity->get('uid')->target_id) ? $entity->get('uid')->entity->id() : '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $user_string = $form_state->getValue('user');
    $user = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties([
      'name' => $user_string,
    ]);
    if (count($user) == 0) {
      $form_state->setErrorByName('user', 'retype');
    }
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function save(array $form, FormStateInterface $form_state) {
    // $roles = $form_state->getValue('group');.
    $user_string = $form_state->getValue('user');
    $user = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties([
      'name' => $user_string,
    ]);
    $user = current($user);
    $this->entity->set('uid', $user->id());
    $this->entity->save();
    drupal_set_message('保存成功');
  }

}
