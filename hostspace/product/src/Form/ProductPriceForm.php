<?php

/**
 * @file
 * Contains \Drupal\product\Form\ProductPriceForm.
 */

namespace Drupal\product\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Render\Element;
use Drupal\hostlog\HostLogFactory;

class ProductPriceForm extends ContentEntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $entity = $this->entity;
    if($entity->isNew()) {
      $request = \Drupal::request()->attributes->all();
      $productId = $request['product'];
      $entity->set('productId', $productId);
    }

    $options = get_payment_mode_options();
    $form['payment_mode'] = array(
      '#type' => 'select',
      '#title' => t('Payment mode'),
      '#options' => $options,
      '#weight' => 10,
      '#required' => true,
      '#disabled' => true,
      '#default_value' => 'month' //暂时只支付月付 $entity->getSimpleValue('payment_mode')
    );
    return $form; 
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $level = $form_state->getValue('user_level');
    if(!empty($level)) {
      $entity = $this->entity;
      $filter = array(
        'productId' => $entity->getObjectId('productId'),
        'user_level' => $level[0]['target_id'],
        'payment_mode' => $form_state->getValue('payment_mode'),
      );
      $entities = entity_load_multiple_by_properties('product_price', $filter);
      if(!empty($entities)) {
        if($entity->isNew()) {
          $form_state->setErrorByName('op',$this->t('The product price data been exists.'));
        } else {
          if($entity->id() != reset($entities)->id()) {
            $form_state->setErrorByName('op',$this->t('The product price data been exists.'));
          }
        }
      }
    }
  }
 
  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $action = 'update';
    if($entity->isnew()) {
      $action = 'insert';
    }
    $entity->save();
    HostLogFactory::OperationLog('product')->log($entity, $action);
    drupal_set_message($this->t('Product price saved successfully'));
    $form_state->setRedirectUrl(new Url('entity.product.price_view', array('product' => $entity->getObjectId('productId'))));
  }
}
