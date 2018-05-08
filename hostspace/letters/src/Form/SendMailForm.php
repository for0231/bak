<?php

/**
 * @file
 * Contains \Drupal\letters\Form\SendMailForm.
 */

namespace Drupal\letters\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class SendMailForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'send_mail_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['to_user'] = array(
      '#type' => 'fieldset',
      '#title' => t('Receiving object')
    );
    $form['to_user']['receive_type'] = array(
      '#type' => 'select',
      '#title' => '接收对象',
      '#options' => array('' => '选择接收对象', 'group' => '用户组', 'person' => '指定用户'),
      '#ajax' => array(
        'callback' => array(get_class($this), 'loadElement'),
        'wrapper' => 'ele_wrapper',
        'method' => 'html'
      )
    );
    $form['to_user']['ele'] = array(
      '#type' => 'container',
      '#id' => 'ele_wrapper'
    );
    $receive_type = $form_state->getValue('receive_type');
    if($receive_type == 'group') {
      $roles = entity_load_multiple('user_role');
      foreach($roles as $role) {
        $role_arr[$role->id()] = $role->label();
      }
      $form['to_user']['ele']['group'] = array(
         '#type' => 'select',
         '#title' => '选择用户组',
         '#options' => $role_arr
      );

    } elseif($receive_type == 'person') {
      $roles = entity_load_multiple('user_role');
      foreach($roles as $role) {
        $role_arr[$role->id()] = $role->label();
      }
      $form['to_user']['ele']['dept'] = array(
        '#type' => 'select',
        '#title' => '选择用户组',
        '#options' => $role_arr,
        '#ajax' => array(
         'callback' => array(get_class($this), 'loadperson'),
         'wrapper' => 'person_wrapper',
         'method' => 'html'
       )
      );
      $form['to_user']['ele']['person_wrapper'] = array(
        '#type' => 'container', 
        '#id' => 'person_wrapper'
      );
      $role_id = $form_state->getValue('dept');
      if($role_id) {
        $people = \Drupal::service('letters.letterservice')->getUserByRole($role_id);
        
        foreach($people as $p) {
          $user_entity = entity_load('user', $p->entity_id);
          $user_type = $user_entity->get('user_type')->value;
          $user = \Drupal::service('member.memberservice')->queryDataFromDB($user_type, $p->entity_id);
          $person_arr[$user->uid] = ($user_type == 'client') ? $user->client_name ? $user->client_name : $user_entity->getUsername() : $user->employee_name;
 
        }
        $form['to_user']['ele']['person_wrapper']['person'] = array(
          '#type' => 'select',
          '#title' => '选择用户',
          '#options' => $person_arr
        );
      }
    }
     $form['letter'] = array(
      '#type' => 'fieldset',
      '#title' => '信件内容'
    );
    $form['letter']['title'] = array(
      '#type' => 'textfield',
      '#title' => '标题'
    );
    $form['letter']['content'] = array(
      '#type' => 'text_format',
      '#title' => '内容'
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Send')
    );

    return $form;
  }

  public function loadElement (array $form, FormStateInterface $form_state){
    return $form['to_user']['ele'];
  }
  public function loadperson (array $form, FormStateInterface $form_state){
    return $form['to_user']['ele']['person_wrapper'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // 验证接收对象
    $receive_type = $form_state->getValue('receive_type');
    if(!$receive_type) {
      $form_state->setErrorByName('receive_type',$this->t('Please select receiving object.'));
    }
    if($receive_type == 'group') {  // 用户组对象
      if(!$form_state->getValue('group')) {
        $form_state->setErrorByName('group',$this->t('Please select user group.'));
      }
    } elseif($receive_type == 'person') {  // 个人对象
      if(!$form_state->getValue('person')) {
        $form_state->setErrorByName('person',$this->t('Please select user.'));
      }
    }
    // 验证标题
    if(!$form_state->getValue('title')) {
      $form_state->setErrorByName('title',$this->t('Please enter the letters title.'));
    }
    // 验证内容
    if(!$form_state->getValue('content')['value']) {
      $form_state->setErrorByName('content',$this->t('Please enter the letters content.'));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // 接收对象的类型
    $receive_type = $form_state->getValue('receive_type');

    $title = $form_state->getValue('title');
    $content = $form_state->getValue('content')['value'];
    // 发送邮件
    $outbox = array(
      'title' => $title,
      'content' => $content,
      'post_time' => REQUEST_TIME,
      'uid' => \Drupal::currentUser()->id(),
    );

    // 信件的接受对象的id数组
    $uid_arr = array();

   if($receive_type == 'group') {
      $group = $form_state->getValue('group');
      $outbox += array('to_group' => $group);
      // 根据选择的用户组编号 查询改组下面的所有用户
      $user_arr = \Drupal::service('letters.letterservice')->getUserByRole($group);
      // 得到接受对象的id数组
      foreach($user_arr as $user) {
        $uid_arr[$user->entity_id] = $user->entity_id;
      }
    } elseif($receive_type == 'person') {
      $person = $form_state->getValue('person');
      $outbox += array('to_uid' => $person);
      $uid_arr[$person] = $person;
    }
    $inbox = array();
    // 组装收件箱的数据数组
    foreach($uid_arr as $uid) {
      $inbox[$uid] = array(
        'uid' => $uid,
        'title' => $title,
        'content' => $content,
        'receive_time' => REQUEST_TIME,
        'from_uid' => \Drupal::currentUser()->id()
      );
    }
    // 发送信件
    $result = \Drupal::service('letters.letterservice')->saveStationLetter($outbox, $inbox);

    $result ? drupal_set_message('信件发送成功 !') : drupal_set_message('信件发送失败 !', 'error');
  }
}
