<?php

/**
 * @file
 * Contains \Drupal\idc\Form\RoomDeleteForm.
 */

namespace Drupal\product\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\hostlog\HostLogFactory;

/**
 * Provides the part delete confirmation form.
 */
class ProductDeleteForm  extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete product ? name: %name。', array(
      '%name' => $this->entity->label()
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('admin.product.list');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This product will be delete. Please confirm this product has not any been used storage.');
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $entity->delete();
    HostLogFactory::OperationLog('product')->log($entity, 'delete');
    drupal_set_message($this->t('Product deleted successfully'));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }
}
