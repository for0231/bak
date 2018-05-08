<?php

/**
 * @file 
 * Contains \Drupal\product\BusinessPriceListBuilder
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
class BusinessPriceListBuilder extends EntityListBuilder {
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['business_name'] = $this->t('Product name');
    $header['business_content'] = $this->t('Business content');
    $header['price'] = $this->t('Price');
    $header['change'] =  $this->t('Change date');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $business = $entity->getObject('businessId');
    $row['business_name'] = $business->label();
    $business_content = '';
    $operate = $business->getSimpleValue('operate');
    if($operate == 'edit_number') {
      $business_content = $business->label();
    } else if ($operate == 'select_and_number') {
      $business_content = $business->label() . ' ' . product_business_value_text($business, $entity->getSimpleValue('business_content') . ':1');
    } else {
      $business_content = $business->label() . ' ' . product_business_value_text($business, $entity->getSimpleValue('business_content'));
    }
    $row['business_content'] = $business_content; 
    $row['price'] = $entity->label();
    $row['change'] = format_date($entity->getSimpleValue('changed'), 'custom', 'Y-m-d H:i:s');
    return $row + parent::buildRow($entity);
  }
}
