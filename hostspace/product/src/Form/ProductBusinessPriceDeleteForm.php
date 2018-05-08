<?php

/**
 * @file
 * Contains \Drupal\idc\Form\ProductBusinessPriceDeleteForm.
 */

namespace Drupal\product\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\hostlog\HostLogFactory;

/**
 * Provides the part delete confirmation form.
 */
class ProductBusinessPriceDeleteForm  extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $entity = $this->entity;
    return $this->t('Are you sure you want to delete product business? product: %product, business: %business ', array(
      '%product' => $entity->getObject('productId')->label(),
      '%business' => $entity->getObject('businessId')->label()
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.product.business_price_view', array('product' => $this->entity->getObjectId('productId')));
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This product business will be delete. Please confirm this product business has not any been used storage.');
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();
    HostLogFactory::OperationLog('product')->log($this->entity, 'delete');
    drupal_set_message($this->t('Product business deleted successfully'));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }
}
