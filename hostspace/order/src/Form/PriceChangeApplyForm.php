<?php

/**
 * @file
 * Contains \Drupal\order\Form\PriceChangeApplyForm.
 */

namespace Drupal\order\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Form\EnforcedResponseException;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\hostlog\HostLogFactory;

class PriceChangeApplyForm extends ContentEntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $order = $this->entity;
    $order_price = $order->getSimpleValue('order_price');
    $discount_price = $order->getSimpleValue('discount_price');
    //订单价格
    $form['orderprice'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Order price'),
      '#value' => '￥' . ($order_price - $discount_price),
      '#disabled' => TRUE
    );
    //优惠金额
     $form['discount_amount'] = array(
      '#type' => 'number',
      '#step' => 'any',
      '#required' => TRUE,
      '#title' => $this->t('Discount amount'),
    );

    $form['reason'] = array(
      '#type' => 'textarea',
      '#title' => t('Reasons for applying changes.'),
      '#maxlength' => 1000
    );

    return $form;
  }

  /**
   * Returns an array of supported actions for the current entity form.
   *
   * @todo Consider introducing a 'preview' action here, since it is used by
   *   many entity types.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Apply');
    return $actions;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    //折扣价格
    $discount_amount = $form_state->getValue('discount_amount');
    //申请理由
    $reason = $form_state->getValue('reason');

    //往数据表order_change_price插入申请记录
    $field_arr = array(
      'order_id' => $entity->id(),
      'order_code' => $entity->getSimpleValue('code'),
      'client_id' => $entity->getObjectId('uid'),
      'order_price' => $entity->getSimpleValue('order_price') - $entity->getSimpleValue('discount_price'),
      'change_price' => $discount_amount,
      'ask_uid' => \Drupal::currentUser()->id(),
      'created' => REQUEST_TIME,
      'description' => $reason,
      'status' => 1   //设置改价状态为1=待审核
    );
    $order_service = \Drupal::service('order.orderservice');
    $change_price_id = $order_service->saveApplyRecord($field_arr);
    //设置订单实体的改价状态为1=待审核
    $entity->set('status',1);
    $entity->save();
    //----------写日志---------
    $price_change = $order_service->getPriceChangeById($change_price_id);
    $entity->other_data = array('data_id' => $change_price_id, 'data_name' => 'order_change_price', 'data' => (array)$price_change);
    $entity->other_status = 'change_price_apply';
    HostLogFactory::OperationLog('order')->log($entity, 'price_apply');

    drupal_set_message($this->t('Application is successful!Please wait for the audit results.'));
    // 跳转到订单列表页面
    $form_state->setRedirectUrl(new Url('admin.order.list'));
  }
}
