<?php
/**
 * @file   为用户设置初始信用额度
 * Contains \Drupal\member\Form\SetCreditForUserForm.
 */

namespace Drupal\member\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\hostlog\HostLogFactory;

class SetCreditForUserForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'set_credit';
  }

   /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $client = $this->getRequest()->get('user');

    // ---  显示客户信息 ----
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
    // 若用户已经设定初始额度  则不能设置
    $credit = \Drupal::service('member.memberservice')->getClientCredit($client);
    if($credit) {
      drupal_set_message($this->t('This client has been set an initial credit!'), 'warning');
      $form['client']['original'] = array(
        '#type' => 'number',
        '#title' => t('Credit'),
        '#disabled' => TRUE,
        '#value' => $credit ? $credit->credit ? $credit->credit : 0.00 : '0.00'
      );
      return $form['client'];
    }

    //----- 设置信用额度 ----------

    $form['credit'] = array(
      '#type' => 'fieldset',
      '#title' => t('Enhance credit')
    );
    $form['credit']['set'] = array(
      '#type' => 'number',
      '#title' => t('Credit'),
    );
    $form['credit']['credit_confirm'] = array(
      '#type' => 'number',
      '#title' => t('Cofirm the amount'),
    );
    $form['credit']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $set = $form_state->getValue('set');
    $credit_confirm = $form_state->getValue('credit_confirm');
    if(!$set) {
      $form_state->setErrorByName('set',$this->t('Please enter the credit.'));
    }
    if($set<0) {
      $form_state->setErrorByName('set',$this->t('Amount must be a positive number.'));
    }
    if($set != $credit_confirm) {
      $form_state->setErrorByName('credit_confirm',$this->t('Twice the amount entered does not match.'));
		}
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $credit_confirm = $form_state->getValue('credit_confirm');
    $client_uid = $form_state->getValue('user_id');
     //  调整后的额度
    $funds = array(
      'credit' => $credit_confirm,
      'uid'  => $client_uid
    );

    // 操作记录
    $op_record = array(
      'amount' => $credit_confirm,
      'message' => '初始化额度',
      'op_uid' => \Drupal::currentUser()->id(),
      'client_uid' => $client_uid,
      'created' => REQUEST_TIME
    );
    $rs = \Drupal::service('member.memberservice')->setClientCredit($funds , $op_record);
    if($rs) {
      $entity = entity_load('user', $client_uid);
      $entity->other_data = array('data' => $op_record, 'data_name' => 'user_funds_data', 'data_id' => $client_uid);
      HostLogFactory::OperationLog('member')->log($entity, 'insert');
      drupal_set_message($this->t('Set credit successful.'));
    }
    else {
     drupal_set_message($this->t('Set credit unsuccessful.'),'error');
    }
    $form_state->setRedirectUrl(new Url('member.funds.credit'));
  }

}
