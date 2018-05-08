<?php

/**
 * @file
 * Contains \Drupal\letters\Form\DetailForm.
 */

namespace Drupal\letters\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\SafeMarkup;

class DetailForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'detail_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $letter_id=null, $type=null) {
    $letter = \Drupal::service('letters.letterservice')->getLetter($letter_id, $type);
    if($type == 'outbox') {
      $form = $this->outboxDetail($form, $form_state, $letter);
    } elseif($type == 'inbox') {
      // 设为已读
      \Drupal::service('letters.letterservice')->setLetterHasReaded($letter->id);
     //绘制显示详情的表单元素
      $form = $this->inboxDetail($form, $form_state, $letter);
    }
    return $form; 
  }
  /**
   * 发件箱中的信件详情
   * 
   * @param $letter  
   *   信件对象
   */
  private function outboxDetail(array $form, FormStateInterface $form_state, $letter) {
    $to_user = '';
    if($letter->to_uid) {
      $user_entity = entity_load('user', $letter->to_uid);
      $user_type = $user_entity->get('user_type')->value;
      $user = \Drupal::service('member.memberservice')->queryDataFromDB($user_type, $letter->to_uid);
      $to_user = ($user_type == 'client') ? $user->client_name ? $user->client_name : $user_entity->getUsername() : $user->employee_name;
    }
    $form['title'] = array(
      '#type'=> 'container',
      '#markup' => '标题 : '. $letter->title
    );
    $form['user_obj'] = array(
      '#type' => 'container',
      '#markup' => '接收对象 : '.SafeMarkup::format($letter->to_group . " <br >" . $to_user, array()),
    );
    $form['send_date'] = array(
      '#type'=> 'container',
      '#markup' => '发送时间 : '. format_date($letter->post_time, 'custom', 'Y-m-d H:i:s')
    );
    $form['content'] = array(
      '#type'=> 'container',
      '#markup' => '信件内容 : '. SafeMarkup::format($letter->content, array())
    );
    return $form;
  }

  /**
   * 发件箱中的信件详情
   * 
   * @param $letter  
   *   信件对象
   */
  private function inboxDetail(array $form, FormStateInterface $form_state, $letter) {
    $to_user = '';
    $from_user = '';      
    $user_entity = entity_load('user', $letter->from_uid);
    $user_type = $user_entity->get('user_type')->value;
    $user = $user_type ?\Drupal::service('member.memberservice')->queryDataFromDB($user_type, $letter->from_uid) : '';
    $from_user = $user ?($user_type == 'client') ? $user->client_name ? $user->client_name : $user_entity->getUsername() : $user->employee_name : $user_entity->label();
    $form['title'] = array(
      '#type'=> 'container',
      '#markup' => '标题 : '. $letter->title
    );
    $form['user_obj'] = array(
      '#type' => 'container',
      '#markup' => '发件人 : '. $from_user,
    );
    $form['send_date'] = array(
      '#type'=> 'container',
      '#markup' => '发送时间 : '. format_date($letter->receive_time, 'custom', 'Y-m-d H:i:s')
    );
    $form['content'] = array(
      '#type'=> 'container',
      '#markup' => '信件内容 : '. SafeMarkup::format($letter->content, array())
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }
}
