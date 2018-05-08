<?php

/**
 * @file 网站提醒的类型设置
 * Contains Drupal\hostlog\Form\ReminderTypeForm
 */

namespace Drupal\hostlog\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class ReminderTypeForm extends FormBase {

   /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hostlog_reminder_type_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['reminder_type_settings'] = array(
      '#type' => 'details',
      '#open' => true,
      '#title' => t('Reminder Settings(提醒类型设置)'),
      '#description' => t('类型:描述 eg.order__create: 订单创建,类型用两个连字符连接'),
    );
    $form['reminder_type_settings']['type'] = array(
      '#type' => 'textfield',
      '#title' => t('Type'),
    );
    $form['reminder_type_settings']['description'] = array(
      '#type' => 'textfield',
      '#title' => t('Description'),
    );

    $form['reminder_type_settings']['actions'] = array(
      '#type' => 'actions',
      '#attributes' => array('class' => array('container-inline')),
    );
    $form['reminder_type_settings']['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $type = $form_state->getValue('type');
    $description = $form_state->getValue('description');
    if (empty($type)) {
      $form_state->setErrorByName('type', $this->t('Type can not be empty'));
    }
    if (empty($description)) {
      $form_state->setErrorByName('description', $this->t('Description can not be empty'));
    }
  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $type = trim($form_state->getValue('type'));
    $description = $form_state->getValue('description');
    $context = array(
      'type' => $type,
      'description' => $description,
      'uid' => \Drupal::currentUser()->id(),
      'uuid' => \Drupal::service('uuid')->generate(),
      'timestamp' => REQUEST_TIME,
    );
    \Drupal::service('operation.reminder')->typelog($context);
    drupal_set_message(t('保存成功'));
  }
}
