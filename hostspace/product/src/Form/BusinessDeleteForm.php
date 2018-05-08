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
class BusinessDeleteForm  extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete business ? name: %name。', array(
      '%name' => $this->entity->label()
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('admin.product.business');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This business will be delete. Please confirm this business has not any been used storage.');
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $default_business = \Drupal::service('product.default.business')->getBusinessById($entity->id());
    if(!empty($default_business)) {
      $form_state->setErrorByName('op', t('Failed to delete, business is already in use.'));
      return;
    }
    $business_price = entity_load_multiple_by_properties('product_business_price', array(
      'businessId' => $entity->id()
    ));
    if(!empty($business_price)) {
      $form_state->setErrorByName('op', t('Failed to delete, business is already in use.'));
      return;
    }
    $lib = $entity->getSimpleValue('resource_lib');
    if($lib != 'none') {
      $entity_type = $lib == 'create' ? 'product_business_content' : 'product_business_entity_content';
      $child_content = entity_load_multiple_by_properties($entity_type, array('businessId' => $entity->id()));
      if(count($child_content)) {
         $form_state->setErrorByName('op', t('Please first delete buisness content'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $entity->delete();
    HostLogFactory::OperationLog('product')->log($entity, 'delete');
    drupal_set_message($this->t('Business deleted successfully'));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }
}
