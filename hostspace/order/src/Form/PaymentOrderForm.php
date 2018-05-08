<?php

/**
 * @file
 * Contains \Drupal\order\Form\BuildOrderForm.
 */

namespace Drupal\order\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\EnforcedResponseException;
use Drupal\Core\Url;
use Drupal\order\ServerDistribution;

class PaymentOrderForm extends ContentEntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    if($entity->getObjectId('uid') != $this->currentUser()->id() || $entity->getSimpleValue('status') != 0) {
      throw new EnforcedResponseException($this->redirect('user.order'));
    }
    $form = parent::form($form, $form_state);
    $payment = $entity->getSimpleValue('order_price') - $entity->getSimpleValue('discount_price');
    $form['price'] = array(
      '#markup' => t(' Payment：￥%price', array('%price' => $payment))
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
    $actions['submit']['#value'] = $this->t('Confirm payment');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $entity = $this->entity;
    //判断库存 - 他说不判断，被表单被勾过去了我这儿就不起作用了。
    /*$product_type = array();
    $order_product_service = \Drupal::service('order.product');
    $order_service = \Drupal::service('order.orderservice');
    $product_list = $order_product_service ->getProductByOrderId($entity->id());
    foreach($product_list as $product) {
      if($product->action == 1) {
        $value = $product->product_num;
        if(array_key_exists($product->product_id, $product_type)) {
           $value += $product_type[$product->product_id];
        }
        $trial = $order_service->loadTrialHostclient($entity->id(), $product->opid);
        if(!empty($trial)) {
           $value--;
        }
        $product_type[$product->product_id] = $value;
      }
    }
    $dis = ServerDistribution::createInstance();
    foreach($product_type as $product_id => $value) {
      $product = entity_load('product', $product_id);
      $idc_stock = $dis->getServerStock($product->getObjectId('server_type'));
      if($value > $idc_stock) {
        $form_state->setErrorByName($product_id, $this->t('Sorry the product %product inventory shortage, the current stock of %number Units', array(
          '%product' => $product->label(),
          '%number' => $idc_stock
        )));
      }
    }*/
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function save(array $form, FormStateInterface $form_state) {
    // 验证支付结果
    //$payment_service = \Drupal::service('pay.payment');
    //$status = $payment_service->payment('Alipay', $this->entity);

    // 设置服务器订购的状态
    $entity = $this->entity;
    $entity->set('payment_date', REQUEST_TIME);
    $entity->set('payment_mode', 1);
    $paid_price = $entity->getSimpleValue('order_price') - $entity->getSimplevalue('discount_price');
    $entity->set('paid_price', $paid_price);
    $entity->set('status', 3);
    $entity->save();

    $dis = ServerDistribution::createInstance();
    $dis->orderDistributionServer($entity);

    $form_state->setRedirectUrl(new Url('user.order.payment.success', array('order' => $entity->id())));
  }
}
