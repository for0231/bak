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
class BusinessContentDeleteForm  extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete business content? name: %name。', array(
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
    return $this->t('This business content will be delete. Please confirm this business content has not any been used storage.');
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $business = $entity->getObject('businessId');
    $default_business = \Drupal::service('product.default.business')->getBusinessById($business->id());
    if(!empty($default_business)) {
      $op = $business->getSimpleValue('operate');
      $exist = false;
      if($op == 'select_content') {
        foreach($default_business as $default_bus) {
          if($default_bus->business_content == $entity->id()) {
            $exist = true;
            break;
          }
        }
      } else if ($op == 'select_and_number') {
        foreach($default_business as $default_bus) {
          $values = explode(':', $default_bus->business_content);
          if($values[0] == $entity->id()) {
            $exist = true;
            break;
          }
        }
      }
      if($exist) {
        $form_state->setErrorByName('op', t('Failed to delete, business content is already in use.'));
        return;
      }
    }
    $business_prices = entity_load_multiple_by_properties('product_business_price', array(
      'businessId' => $business->id()
    ));
    if(!empty($business_prices)) {
      $op = $business->getSimpleValue('operate');
      $exist = false;
      if($op == 'select_content') {
        foreach($business_prices as $business_price) {
          if($business_price->getSimpleValue('business_content') == $entity->id()) {
            $exist = true;
            break;
          }
        }
      } else if ($op == 'select_and_number') {
        foreach($business_prices as $business_price) {
          if($business_price->getSimpleValue('business_content') == $entity->id()) {
            $exist = true;
            break;
          }
        }
      }
      if($exist) { 
        $form_state->setErrorByName('op', t('Failed to delete, business content is already in use.'));
        return;
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
    drupal_set_message($this->t('Business content deleted successfully'));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }
}
