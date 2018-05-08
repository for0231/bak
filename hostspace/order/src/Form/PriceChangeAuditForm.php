<?php

/**
 * @file
 * Contains \Drupal\order\Form\PriceChangeAuditForm.
 */

namespace Drupal\order\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\hostlog\HostLogFactory;

class PriceChangeAuditForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'audit_price_change_apply_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $price_change_id = null) {
    $order_service = \Drupal::service('order.orderservice');
    $price_change = $order_service->getPriceChangeById($price_change_id);
    $order = entity_load('order', $price_change->order_id);
  
    $form['price_change'] = array(
      '#type' => 'value',
      '#value' => $price_change
    );   
 
    $client = $order->getObject('uid');
    $form['client_info'] = array(
      '#theme' => 'admin_order_client',
      '#client_obj' => $client,
    );

    $form['order_detail'] = array(
      '#type' => 'link',
      '#title' => t('Corresponding order'). '：' . $order->getSimpleValue('code'),
      '#url' => new Url('entity.order.detail_view', array('order' => $price_change->order_id)),  
    );
    $form['order_price'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Order price'),
      '#default_value' => '￥' . $price_change->order_price,
      '#disabled' => TRUE
    );
    $form['change_price'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Discount amount'),
      '#default_value' => '￥' . $price_change->change_price,
      '#disabled' => TRUE
    );
    $form['description'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Apply price change description'),
      '#default_value' => $price_change->description,
      '#disabled' => TRUE
    );
    $form['change_price'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Discount amount'),
      '#default_value' => '￥' . $price_change->change_price,
      '#disabled' => TRUE
    );
    $member_service = \Drupal::service('member.memberservice');
    $ask = $member_service->queryDataFromDB('employee', $price_change->ask_uid);
    $form['detail']['ask_uid'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Applicant'),
      '#default_value' => $ask ? $ask->employee_name : 'admin',
      '#disabled' => TRUE
    );
    $form['detail']['created'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Application Period'),
      '#default_value' => date('Y-m-d H:i:s', $price_change->created),
      '#disabled' => TRUE
    );
    
    if($price_change->status != 1) {
       $audit = $member_service->queryDataFromDB('employee', $price_change->audit_uid);
       $form['audit_uid']  = array(
        '#type' => 'textfield',
        '#title' => $this->t('Auditor'),
        '#default_value' => $audit ? $audit->employee_name : 'admin',
        '#disabled' => TRUE
      );
      $form['audit_stamp'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Audit time'),
        '#default_value' => date('Y-m-d H:i:s', $price_change->audit_stamp),
        '#disabled' => true
      );   
      $form['result'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Audit result'),
        '#disabled' => true,
        '#default_value' => changePriceStatus()[$price_change->status]
      );
    }
    $form['addit_remark'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Audit description'),
      '#maxlength' => 1000,
      '#default_value' => $price_change->addit_remark
    );
   
    if($price_change->status == 1) {
      $form['agree'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Approved')
      );

      $form['refuse'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Disapprove'),
        '#submit' => array('::refuseSubmitForm')
      );
    } else {
      $form['addit_remark']['#disabled'] = true;
    }
    
    return $form;
  }

  /**
   * 拒绝改价
   */
  public function refuseSubmitForm(array &$form, FormStateInterface $form_state) {
    $price_change = $form_state->getValue('price_change');
    $addit_remark = $form_state->getValue('addit_remark');
    $field_arr = array(
      'audit_uid' => \Drupal::currentUser()->id(),
      'audit_stamp' => REQUEST_TIME,
      'addit_remark' => $addit_remark,
      'status' => 3  //审核不通过
    );
    //修改订单状态
    $order = entity_load('order', $price_change->order_id);
    $order->set('status', 0);
    \Drupal::service('order.orderservice')->auditPriceChangeApplation($price_change->id, $field_arr, $order);
    //-----写日志-----
    $price_change->audit_uid = $field_arr['audit_uid'];
    $price_change->audit_stamp = $field_arr['audit_stamp'];
    $price_change->addit_remark = $field_arr['addit_remark'];
    $price_change->status = $field_arr['status'];
    $order->other_data = array('data_id' => $price_change->id, 'data_name' => 'order_change_price', 'data' => (array)$price_change);
    $order->other_status = 'change_price_apply';
    HostLogFactory::OperationLog('order')->log($order, 'price_audit');

    drupal_set_message($this->t('Has refused to change the price for.'));
    $form_state->setRedirectUrl(new Url('admin.change_price.list'));
  }

  /**
   * 同意改价
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $price_change = $form_state->getValue('price_change');
    $addit_remark = $form_state->getValue('addit_remark');
    $field_arr = array(
      'audit_uid' => \Drupal::currentUser()->id(),
      'audit_stamp' => REQUEST_TIME,
      'addit_remark' => $addit_remark,
      'status' => 2  //审核通过
    );
    //改价成功之后，设置订单状态为未支付 。并将优惠价格存入订单表
    $order = entity_load('order', $price_change->order_id);
    $order->set('status', 0);
    $order->set('discount_price', $price_change->change_price + $order->getSimpleValue('discount_price'));
    \Drupal::service('order.orderservice')->auditPriceChangeApplation($price_change->id, $field_arr, $order);
    //-------写日志--------
    $price_change->audit_uid = $field_arr['audit_uid'];
    $price_change->audit_stamp = $field_arr['audit_stamp'];
    $price_change->addit_remark = $field_arr['addit_remark'];
    $price_change->status = $field_arr['status'];
    $order->other_data = array('data_id' => $price_change->id, 'data_name' => 'order_change_price', 'data' => (array)$price_change);
    $order->other_status = 'change_price_apply';
    HostLogFactory::OperationLog('order')->log($order, 'price_audit');

    drupal_set_message($this->t('Has agreed to change the price for.'));
    $form_state->setRedirectUrl(new Url('admin.change_price.list'));
  }
}
