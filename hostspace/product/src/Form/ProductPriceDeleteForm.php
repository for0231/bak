<?php

/**
 * @file
 * Contains \Drupal\idc\Form\ProductPriceDeleteForm.
 */

namespace Drupal\product\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\hostlog\HostLogFactory;

/**
 * Provides the part delete confirmation form.
 */
class ProductPriceDeleteForm  extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $entity = $this->entity;
    return $this->t('Are you sure you want to delete product price? Agent level: %level, price: %price ', array(
      '%level' => $entity->getObject('user_level')->label(),
      '%price' => $entity->label()
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.product.price_view', array('product' => $this->entity->getObjectId('productId')));
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This product price will be delete. Please confirm this product price has not any been used storage.');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $entity->delete();
    HostLogFactory::OperationLog('product')->log($entity, 'delete');
    drupal_set_message($this->t('Product price deleted successfully'));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }
}
