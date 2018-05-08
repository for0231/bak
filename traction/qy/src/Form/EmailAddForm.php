<?php

/**
 * @file
 * Contains \Drupal\qy\Form\EmailAddForm.
 */

namespace Drupal\qy\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * 增加策略表单类
 */
class EmailAddForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'qy_email_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $mail_id = 0) {
    $form['username'] = array(
      '#type' => 'textfield',
      '#required' => true,
      '#title' => '用户名'
    );
    $form['ip_group'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('form-item')
      )
    );
    $form['ip_group']['title'] = array(
      '#type' => 'label',
      '#title' => 'IP'
    );
    $form['ip_group']['ip'] = array(
      '#type' => 'textfield',
      '#size' => 24,
      '#required' => true,
      '#field_suffix' => '/',
      '#prefix' => '<div class = "container-inline">'
    );
    $form['ip_group']['mask_number'] = array(
      '#type' => 'number',
      '#required' => true,
      '#min' => 0,
      '#max' => 32,
      '#size' => 1,
      '#suffix' => '</div>'
    );
    $form['email'] = array(
      '#type' => 'textfield',
      '#required' => true,
      '#title' => '邮箱'
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => '保存',
    );
    $form['mail_id'] = array(
      '#type' => 'value',
      '#value' => 0
    );
    if($mail_id) {
      $mail_service = \Drupal::service('qy.emial_service');
      $email = $mail_service->load_emailById($mail_id);
      if($email) {
        $form['username']['#default_value'] = $email->username;
        $form['ip_group']['ip']['#default_value'] = $email->ip;
        $form['ip_group']['mask_number']['#default_value'] = $email->mask_number;
        $form['email']['#default_value'] = $email->email;
        $form['mail_id']['#value'] = $mail_id;
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $ip = trim($form_state->getValue('ip'));
    //判断IP格式是否正确
    if(strcmp(long2ip(sprintf("%u",ip2long($ip))), $ip)) {
      $form_state->setErrorByName('ip',$this->t('Ip格式错误'));
    }
  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $mail_service = \Drupal::service('qy.emial_service');
    $value = array(
      'username' => $form_state->getValue('username'),
      'ip' => $form_state->getValue('ip'),
      'mask_number' => $form_state->getValue('mask_number'),
      'email' => $form_state->getValue('email')
    );
    $mail_id = $form_state->getValue('mail_id');
    if($mail_id) {
      $mail_service->update_email($value, $mail_id);
    } else {
      $mail_service->add_email($value);
    }
    drupal_set_message('保存成功');
    $form_state->setRedirectUrl(new Url('admin.qy.mail.list'));
  }
}