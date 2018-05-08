<?php

/**
 * @file 
 * Contains \Drupal\product\ProductPriceListBuilder
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
class ProductPriceListBuilder extends EntityListBuilder {
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
    $header['user_level'] = $this->t('Agent level');
    $header['payment_mode'] = $this->t('Payment model');
    $header['original_price'] = $this->t('Original price');
    $header['price'] = $this->t('Price');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $options = get_payment_mode_options();
    $row['product_name'] = $entity->getObject('productId')->label();
    if($user_level = $entity->getObject('user_level')) {
      $row['user_level'] = $user_level->label();
    } else {
      $row['user_level'] = '';
    }
    $row['payment_mode'] = $options[$entity->getSimpleValue('payment_mode')];
    $original_price = $entity->getSimpleValue('original_price');
    $row['original_price'] = $original_price > 0 ? $original_price : '';
    $row['price'] = $entity->label();
    return $row + parent::buildRow($entity);
  }
}
