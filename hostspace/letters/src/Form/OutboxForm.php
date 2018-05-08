<?php

/**
 * @file
 * Contains \Drupal\letters\Form\OutboxForm.
 */

namespace Drupal\letters\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\SafeMarkup;

class OutboxForm extends FormBase {

  /**
   * 创建行数据
   */
  private function appendRowDate() {
    $current_user = \Drupal::currentUser();
    $data = \Drupal::service('letters.letterservice')->getOutboxData($current_user->id());
    $rows = array();
    $i = 1;
    $to_user = '';
    foreach($data as $letter) {
      $to_user = '';
      if($letter->to_uid) { 
        $user_entity = entity_load('user', $letter->to_uid);
       
        $user_type = $user_entity->get('user_type')->value;
        $user = \Drupal::service('member.memberservice')->queryDataFromDB($user_type, $letter->to_uid);      
        $to_user = ($user_type == 'client') ? $user->client_name ? $user->client_name : $user_entity->getUsername() : $user->employee_name;
        $to_user =  $to_user ? $to_user."<br >" : '';
      }
      $rows[$i] = array(
        'id' => $i,
        'title' => $letter->title,
        'send_time' => format_date($letter->post_time, 'custom', 'Y-m-d H:i:s'),     
        'receive' => SafeMarkup::format($to_user . $letter->to_group, array())  
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
      'url' => new Url('letter.detail', array('letter_id' => $id, 'type' => 'outbox'))
    );
    $op['delete'] = array(
      'title' => t('Delete'),
      'url' => new Url('letter.delete', array('flag' => 'outbox', 'letter_id' => $id))
    );
    return $op;
  } 

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'outbox_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['outbox']= array(
      '#type' => 'table',
      '#header' => array(
        'id' => 'ID',
        'title' => '标题',
        'send_time' => '发送时间', 
        'receive' => '接受对象',
        'op' => '操作'
      ),
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
