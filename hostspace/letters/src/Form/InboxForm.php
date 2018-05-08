<?php

/**
 * @file
 * Contains \Drupal\letters\Form\InboxForm.
 */

namespace Drupal\letters\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\SafeMarkup;

class InboxForm extends FormBase {

  /**
   * 创建表头
   */
  private function createHeader() {
    return array(
      'id' => 'ID',
      'title' => '标题',
      'post' => '发送者',
      'send_time' => '发送时间', 
      'op' => '操作'
    ); 
  }

  /**
   * 创建行数据
   */
  private function appendRowDate() {
    $current_user = \Drupal::currentUser();
    $data = \Drupal::service('letters.letterservice')->getinboxData($current_user->id());
    $rows = array();
    $i = 1;
    foreach($data as $letter) {
      $from_user = '';      
      $user_entity = entity_load('user', $letter->from_uid);
      $user_type = $user_entity->get('user_type')->value;
      $user = $user_type ?\Drupal::service('member.memberservice')->queryDataFromDB($user_type, $letter->from_uid) : '';
      $from_user = $user ?($user_type == 'client') ? $user->client_name ? $user->client_name : $user_entity->getUsername() : $user->employee_name : $user_entity->label();       

      $rows[$i] = array(
        'id' => $i,
       // 'title' => $letter->title,
      );
      $mark = array();
      // 标记未读信件
      if($letter->is_read == 0) {
        $mark = array(
          '#theme' => 'mark',
          '#status' => MARK_NEW,
          //'#mark_type' => MARK_READ,
        );
      }
      $rows[$i]['title']['data'] = array(
        '#type' => 'link',
        '#title' => $letter->title,
        '#suffix' => $mark ? ' ' . drupal_render($mark) : '',
        '#url' =>  new Url('letter.detail', array('letter_id' => $letter->id, 'type' => 'inbox')),
      );

      $rows[$i] += array(
        'post' =>  $from_user,
        'send_time' => format_date($letter->receive_time, 'custom', 'Y-m-d H:i:s'), 
      );
      $rows[$i]['operations']['data'] = array(
        '#type' => 'operations',     
        '#links' => $this->getOperations($letter->id) 
      );
      $i++;
    }
    return $rows;
  }

  /**
   * 构建操作的链接数组   
   */
  private function getOperations($id) {
    $op['View'] = array(
      'title' => t('View'),
      'url' => new Url('letter.detail', array('letter_id' => $id, 'type' => 'inbox'))
    );
    $op['delete'] = array(
      'title' => t('Delete'),
      'url' => new Url('letter.delete', array('flag' => 'inbox', 'letter_id' => $id))
    );
    return $op;
  } 

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'inbox_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    
    $form['outbox'] = array(
      '#type' => 'table',
      '#header' => $this->createHeader(),
      '#rows' => $this->appendRowDate() 
    );
    $form['pager']['#type'] = 'pager'; 

    return $form; 
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }
}
