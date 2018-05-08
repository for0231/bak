<?php

/**
 * @file
 * Contains \Drupal\idc\Form\BusinessPriceDeleteForm.
 */

namespace Drupal\product\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides the part delete confirmation form.
 */
class BusinessPriceDeleteForm  extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $entity = $this->entity;
    return $this->t('Are you sure you want to delete business price? business: %business, price: %price ', array(
      '%business' => $entity->getObject('businessId')->label(),
      '%price' => $entity->label()
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('admin.product.business_price');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This business price will be delete. Please confirm this business price has not any been used storage.');
  }
 

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $filter['businessId'] = $entity->getObjectId('businessId');
    $content = $entity->getSimpleValue('business_content');
    if(!empty($content)) {
      $filter['business_content'] = $content;
    }
    $entitys = entity_load_multiple_by_properties('product_business_price', $filter);
    if(count($entitys)) {
      $form_state->setErrorByName('op', t('Business price setting has been used.')); 
    }
  }

 
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();
    drupal_set_message($this->t('Product business price deleted successfully'));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }
}
