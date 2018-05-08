<?php

/**
 * @file
 * Contains \Drupal\order\Form\TrialApplyAuditForm.
 */

namespace Drupal\order\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\hostlog\HostLogFactory;

class TrialApplyAuditForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'audit_trial_apply_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $trial_id = null) {
    $order_service = \Drupal::service('order.orderservice');
    $trial = $order_service->getTrialById($trial_id);

    $form['trial'] = array(
      '#type' => 'value',
      '#value' => $trial
    );

    $client = entity_load('user', $trial->client_id);
    $form['client_info'] = array(
      '#theme' => 'admin_order_client',
      '#client_obj' => $client,
    );
    
    $order = entity_load('order', $trial->order_id); 
    $form['order_detail'] = array(
      '#type' => 'link',
      '#title' => $this->t('Corresponding order'). '：' . $order->getSimpleValue('code'),
      '#url' => new Url('entity.order.detail_view', array('order' => $trial->order_id)),  
    ); 
 
    $order_product = \Drupal::service('order.product')->getProductById($trial->order_product_id);
    $form['product'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Trial product'),
      '#default_value' => $order_product->product_name,
      '#disabled' => TRUE
    );
    
    $member_service = \Drupal::service('member.memberservice');
    $ask = $member_service->queryDataFromDB('employee', $trial->ask_uid);
    $form['apply_uid'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Applicant'),
      '#default_value' => isset($ask->employee_name) ? $ask->employee_name : 'admin',
      '#disabled' => TRUE
    );

    $form['apply_date'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Application Period'),
      '#default_value' => date('Y-m-d H:i:s',$trial->ask_date),
      '#disabled' => TRUE
    );
    
    $form['apply_description'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Apply description'),
      '#default_value' => $trial->ask_description,
      '#disabled' => true
    );

    if($trial->status != 1) {
      $audit = $member_service->queryDataFromDB('employee', $trial->audit_uid);
      $form['audit_user']  = array(
        '#type' => 'textfield',
        '#title' => $this->t('Auditor'),
        '#default_value' => $audit ? $audit->employee_name : 'admin',
        '#disabled' => TRUE
      );
      $form['audit_data'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Audit time'),
        '#default_value' => date('Y-m-d H:i:s', $trial->audit_date),
        '#disabled' => true
      );
   
      $form['result'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Audit result'),
        '#disabled' => true,
        '#default_value' => trialServerStatus()[$trial->status]
      ); 
    }
    $form['audit_description'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Audit description'),
      '#maxlength' => 1000,
      '#default_value' => $trial->audit_description
    );
    if($trial->status == 1) {
      $form['agree'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Approved')
      );

      $form['refuse'] = array(
        '#type' => 'submit',
        '#name' => 'refuse',
        '#value' => $this->t('Disapprove'),
        '#submit' => array('::refuseSubmitForm')
      );
    } else {
      $form['audit_description']['#disabled'] = true;
    }
    return $form;
  }

  /**
   * 拒绝试用
   */
  public function refuseSubmitForm(array &$form, FormStateInterface $form_state) {
    $trial = $form_state->getValue('trial');
    $trial->audit_uid = $this->currentUser()->id();
    $trial->audit_date = REQUEST_TIME;
    $trial->audit_description = $form_state->getValue('audit_description');
    $trial->status = 3;

    $order = entity_load('order', $trial->order_id);
    $order->set('status', 0);
    \Drupal::service('order.orderservice')->TrialRefuse($trial, $order);
    //---写日志----
    $order->other_data = array('data_id' => $trial->id, 'data_name' => 'order_server_trial', 'data' => (array)$trial);
    $order->other_status = 'trial_apply';
    HostLogFactory::OperationLog('order')->log($order, 'trial_audit');
   
    drupal_set_message($this->t('Has refused to a trial application.'));
    $form_state->setRedirectUrl(new Url('admin.order.trial.list'));
  }

  /**
   * 同意试用
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $trial = $form_state->getValue('trial');
    $trial->audit_uid = $this->currentUser()->id();
    $trial->audit_date = REQUEST_TIME;
    $trial->audit_description = $form_state->getValue('audit_description');
    $trial->status = 2;

    $order = entity_load('order', $trial->order_id);
    $order->set('status', 0);
    $hostclient = \Drupal::service('order.orderservice')->TrialAgree($trial, $order);
    //---写日志----
    if(!empty($hostclient)) {
      $order->other_data = array('data_id' => $trial->id, 'data_name' => 'order_server_trial', 'data' => (array)$trial);
      $order->other_status = 'trial_apply';
      HostLogFactory::OperationLog('order')->log($order, 'trial_audit');

      $hostclient->other_status = 'distribution_server';
      HostLogFactory::OperationLog('order')->log($hostclient, 'insert');
    }
    drupal_set_message($this->t('Has agreed to a trial application.'));
    $form_state->setRedirectUrl(new Url('admin.order.trial.list'));
  }
}
