<?php

/**
 * @file
 * Contains \Drupal\product\Form\ProductBusinessPriceForm.
 */

namespace Drupal\product\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Render\Element;
use Drupal\hostlog\HostLogFactory;

class ProductBusinessPriceForm extends ContentEntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $entity = $this->entity;
    if($entity->isNew()) {
      $request = \Drupal::request()->attributes->all();
      $productId = $request['product'];
      if(!empty($productId)) {
        $entity->set('productId', $productId);
      }
    }

    //-----选择配件--------
    $business_options = array(); 
    $business_list = entity_load_multiple_by_properties('product_business',array());
    foreach($business_list as $business) {
      $business_options[$business->id()] = $business->label();
    }

    $submit_business_id = $form_state->getValue('businessId');
    $business_id = $submit_business_id;
    if(!$entity->isNew()) {
      $business_id = empty($submit_business_id) ? $entity->getObjectId('businessId') : $submit_business_id;
    }
   
    $form['business_group'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => 'container-inline'
      ),
      '#weight' => 12,
    );
    $form['business_group']['businessId'] = array(
      '#type' => 'select',
      '#title' => $this->t('Business'),
      '#options' => $business_options,
      '#required' => true,
      '#ajax' => array(
        'callback' => '::loadBusinessContent',
        'wrapper' => 'business_content_wrapper',
        'method' => 'html'
      ),
      '#default_value' => $business_id
    );
    $form['business_group']['content_wrapper'] = array(
      '#type' => 'container',
      '#id' => 'business_content_wrapper'
    );
    $form['business_group']['content_wrapper']['business_content'] = array();
    if(!empty($business_id)) {
      $business = $business_list[$business_id];
      $operate = $business->getSimpleValue('operate');
      if ($operate == 'select_content') {
        $ctl = product_business_control($business);
        $form['business_group']['content_wrapper']['business_content'] = array(
          '#required' => true,
          '#default_value' => $entity->getSimpleValue('business_content'),
        ) + $ctl;
      } else if ($operate == 'select_and_number'){
        $ctl = product_business_control($business);
        $form['business_group']['content_wrapper']['business_content'] = array(
          '#required' => true,
          '#default_value' => $entity->getSimpleValue('business_content')
        ) + $ctl['select'];
      }
    }
    return $form; 
  }
 
  public static function loadBusinessContent(array $form, FormStateInterface $form_state) {
    return $form['business_group']['content_wrapper']['business_content']; 
  }


  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $entity = $this->entity;
    $filter['businessId'] = $form_state->getValue('businessId');
    $business_content = $form_state->getValue('business_content');
    if(!empty($business_content)) {
      $filter['business_content'] = $business_content;
    }
    $business_prices = entity_load_multiple_by_properties('business_price', $filter);
    if(empty($business_prices)) {
      $form_state->setErrorByName('op',$this->t('This business is not set prices.'));
    } else {
      if(count($business_prices) > 1) {
        $form_state->setErrorByName('op',$this->t('Please choose the right business.'));
      } else {
        $business_price = reset($business_prices);
        $entity->set('price', $business_price->getSimpleValue('price'));
        $entity->set('payment_mode', $business_price->getSimpleValue('payment_mode'));
      }
    }

    $filter['productId'] = $entity->getObjectId('productId');
    $entities = entity_load_multiple_by_properties('product_business_price', $filter);
    if(!empty($entities)) {
      if($entity->isNew()) {
        $form_state->setErrorByName('op',$this->t('The business price data been exists.'));
      } else {
        if($entity->id() != reset($entities)->id()) {
          $form_state->setErrorByName('op',$this->t('The business price data been exists.'));
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
    drupal_set_message($this->t('Product business price saved successfully'));
    $form_state->setRedirectUrl(new Url('entity.product.business_price_view', array('product' => $entity->getObjectId('productId'))));
  }
}
