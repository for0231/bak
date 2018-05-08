<?php

/**
 * @file
 * Contains \Drupal\order\user\CancelOrder.
 */

namespace Drupal\order\user;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Form\EnforcedResponseException;

/**
 * Provides the article delete confirmation form.
 */

class CancelOrder extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    if($entity->getSimpleValue('status') != 0 || $entity->getObjectId('uid') != $this->currentUser()->id()) {
      throw new EnforcedResponseException($this->redirect('user.order')); 
    }
    $form['#attached']['library'] = array('order/drupal.order-cancel-form');
    return parent::buildForm($form, $form_state);
  }

	/**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to cancel this order ? Order : %order', array(
        '%order' => $this->entity->getSimpleValue('code'),
      )
    );
  }
	
	 /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('user.order');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This order will be canceled.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Confirm');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $entity->set('status', 9);
    $entity->save();
    $product_service = \Drupal::service('order.product');
    $products = $product_service->getProductByOrderId($entity->id());
    $exist_hostclient = array();
    foreach($products as $product) {
      if($product->action == 2 || $product->action == 3) {
        $exist_hostclient[] = $product->product_id;
      }
    }
    $hostclients = array_unique($exist_hostclient);
    foreach($hostclients as $hostclient_id) {
      $hostclient = entity_load('hostclient', $hostclient_id);
      $hostclient->set('unpaid_order', 0);
      $hostclient->save();
    }
    drupal_set_message($this->t('The order:%order has been canceled.',array('%order'=> $this->entity->getSimpleValue('code'))));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }
}
