<?php
/**
 * @file   提升用户的信用额度
 * Contains \Drupal\member\Form\AdjustCreditForm.
 */

namespace Drupal\member\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\hostlog\HostLogFactory;

class AdjustCreditForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'credit_up';
  }

   /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // ---  显示客户信息 ----
    $client = $this->getRequest()->get('user');
    $form['client'] = array(
      '#type' => 'fieldset',
      '#title' => t('Client information')
    );
    $form['client']['info'] = array(
      '#theme' => 'admin_order_client',
      '#client_obj' => entity_load('user', $client)
    );
    $form['client']['user_id'] = array(
      '#type' => 'value',
      '#value' => $client
    );
    //----- 显示客户信息 ----------

    // ----- BEGIN 显示该用户的额度调整记录 BEGIN-------------
    $this->createAdjustRecord($form, $form_state, $client);
    // ----- END 用户的额度调整记录 END-------------


    //----- 信用额度调整 ----------

    // 得到调整的类型 
    // up 提升  low 降低
    $adjust = $this->getRequest()->get('adjust');
    // 原有的额度
    $credit = \Drupal::service('member.memberservice')->getClientCredit($client);

    $form['credit'] = array(
      '#type' => 'fieldset',
      '#title' => t('Enhance credit') 
    );
    $form['credit']['original'] = array(
      '#type' => 'number',
      '#title' => t('Original Credit'),
      '#disabled' => TRUE,
      '#value' => $credit ? $credit->credit ? $credit->credit : 0.00 : '0.00'
    );

    if($adjust == 'up') {  // 额度提升
      $form['credit']['up'] = array(
        '#type' => 'number',
        '#title' => t('Upgrade amount'),
      );
      $form['credit']['up_confirm'] = array(
        '#type' => 'number',
        '#title' => t('Cofirm the amount'),
      );
      $form['credit']['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Submit'),
      );
    } elseif($adjust == 'low') {  // 额度降低
      $form['credit']['low'] = array(
        '#type' => 'number',
        '#title' => t('Reduce amount'),
      );
      $form['credit']['low_confirm'] = array(
        '#type' => 'number',
        '#title' => t('Cofirm the amount'),
      );
      $form['credit']['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Submit'),
        '#submit' => array('::ReduceCredit'),
      );

    }
    return $form;
  }

  /**
   *  显示该用户的额度调整记录
   */
  private function createAdjustRecord(array &$form, FormStateInterface $form_state, $uid) {
    $all_record = \Drupal::service('member.memberservice')-> getCreditAdjustRecord($uid);
    $form['record'] = array(
      '#type' => 'details',
      '#title' => t('Credit adjustment record') ,
      '#open' => true
    );
    $form['record']['list'] = array(
      '#type' => 'table',
      '#header' => array(t('ID'), t('Client'), t('Operator'), t('Message'), t('Amount'), t('Created'))
    );
    if(!empty($all_record)) {
      foreach($all_record as $record) {
        $client = \Drupal::service('member.memberservice')->queryDataFromDB('client', $record->client_uid);
        $client_name = $client ? ($client->client_name ? $client->client_name : entity_load('user', $record->client_uid)->label()) : entity_load('user', $record->client_uid)->label();
        $op_user_name = $client_name;
        if($record->op_uid) {
          $op_user = \Drupal::service('member.memberservice')->queryDataFromDB('employee', $record->op_uid);
          $op_user_name = $op_user->employee_name ? $op_user->employee_name : entity_load('user', $record->op_uid)->label();
        }
        
        $row[$record->id] = array(
          'id' => $record->id,
          'client' => $client_name,
          'operator' => $op_user_name,
          'message' => $record->message, 
          'amount' => '￥'.$record->amount,
          'created' => format_date($record->created, 'custom', 'Y-m-d H:i:s')
        );
      }
      $form['record']['list']['#rows'] = $row;
      $form['record']['record_pager'] = array('#type' => 'pager');
   }
   
   return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $adjust = $this->getRequest()->get('adjust');
    // 原有的额度
    $original = $form_state->getValue('original');

    if($adjust == 'up') {  // 额度提升表单验证
      $up = $form_state->getValue('up');
      $up_confirm = $form_state->getValue('up_confirm');
      if(!$up) {
        $form_state->setErrorByName('up',$this->t('Please enter the credit.'));
      }
      if($up<0) {
        $form_state->setErrorByName('set',$this->t('Amount must be a positive number.'));
      }
      if($up != $up_confirm) {
        $form_state->setErrorByName('up_confirm',$this->t('Twice the amount entered does not match.'));
	  	}  
    } elseif($adjust == 'low') {  // 额度降低验证

      $low = $form_state->getValue('low');
      $low_confirm = $form_state->getValue('low_confirm');
      // 降低的额度不能大于原有额度
      if($low >$original) {
        $form_state->setErrorByName('low',$this->t('The original amount is insufficient.'));
      }
      
      if(!$low) {
        $form_state->setErrorByName('low',$this->t('Please enter the credit.'));
      }
      if($low<0) {
        $form_state->setErrorByName('set',$this->t('Amount must be a positive number.'));
      }

      if($low != $low_confirm) {
        $form_state->setErrorByName('low_confirm',$this->t('Twice the amount entered does not match.'));
	  	}
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // 提升的额度
    $up_confirm = $form_state->getValue('up_confirm');
    // 会员编号
    $client_uid = $form_state->getValue('user_id');
    // 原有的额度
    $original = $form_state->getValue('original');

    $funds = array(
      'credit' => $original + $up_confirm
    );

    // 操作记录    
    $op_record = array(
      'amount' => $up_confirm,
      'message' => '提升额度',
      'op_uid' => \Drupal::currentUser()->id(),
      'client_uid' => $client_uid,
      'created' => REQUEST_TIME
    );

    $rs = \Drupal::service('member.memberservice')->adjustClientCredit($client_uid, $funds , $op_record);
    if($rs) {
      $entity = entity_load('user', $client_uid);
      $entity->other_data = array('data' => $op_record, 'data_name' => 'user_funds_data', 'data_id' => $client_uid);
      HostLogFactory::OperationLog('member')->log($entity, 'up_fund');
      
      drupal_set_message($this->t('Adjust credit successful.'));
      $form_state->setRedirectUrl(new Url('member.funds.credit'));
    }
   
  }

  /**
   * 降低信用额度
   */
  public function ReduceCredit(array &$form, FormStateInterface $form_state) {
    // 降低的额度
    $low_confirm = $form_state->getValue('low_confirm');
    // 会员id
    $client_uid = $form_state->getValue('user_id');
    // 原有额度
    $original = $form_state->getValue('original');
    //  调整后的额度
    $funds = array(
      'credit' => $original - $low_confirm
    );
    // 操作记录
    $op_record = array(
      'amount' => $low_confirm,
      'message' => '降低额度',
      'op_uid' => \Drupal::currentUser()->id(),
      'client_uid' => $client_uid,
      'created' => REQUEST_TIME
    );
    $rs = \Drupal::service('member.memberservice')->adjustClientCredit($client_uid, $funds , $op_record);
    if($rs) {
      $entity = entity_load('user', $client_uid);
      $entity->other_data = array('data' => $op_record, 'data_name' => 'user_funds_data', 'data_id' => $client_uid);
      HostLogFactory::OperationLog('member')->log($entity, 'low_fund');
      drupal_set_message($this->t('Adjust credit successful.'));
    }
    else {
     drupal_set_message($this->t('Adjust credit unsuccessful.'),'error');
    } 
     $form_state->setRedirectUrl(new Url('member.funds.credit'));   
  }
}
