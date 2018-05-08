<?php

/**
 * @file 
 * Contains \Drupal\product\ProductBusinessPriceListBuilder
 */

namespace Drupal\product;

use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of part entities.
 *
 * @see \Drupal\product\Entity\ProductBusiness
 */
class ProductBusinessPriceListBuilder extends EntityListBuilder {
  /**
   * {@inheritdoc}
   */
  public function load() {
    $request = \Drupal::request()->attributes->all();
    $productId = $request['product'];
    return $this->storage->loadByProperties(array('productId' => $productId));
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['product_name'] = $this->t('Product name');
    $header['business'] = $this->t('Business content');
    $header['price'] = $this->t('Price');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $options = get_payment_mode_options();
    $row['product_name'] = $entity->getObject('productId')->label();
    $business_content = '';
    $business = $entity->getObject('businessId');
    $operate = $business->getSimpleValue('operate');
    if($operate == 'edit_number') {
      $business_content = $business->label();
    } else if ($operate == 'select_and_number') {
      $business_content = $business->label() . ' ' . product_business_value_text($business, $entity->getSimpleValue('business_content') . ':1');
    } else {
      $business_content = $business->label() . ' ' .  product_business_value_text($business, $entity->getSimpleValue('business_content'));
    }
    $row['business_content'] = $business_content; 
    $row['price'] = $entity->label();
    return $row + parent::buildRow($entity);
  }
}
