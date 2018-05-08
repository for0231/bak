<?php

/**
 * @file
 * Contains \Drupal\idc\ProductBusinessListBuilder
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
class ProductListBuilder extends EntityListBuilder {
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
  	$header['id'] = $this->t('Product ID');
    $header['name'] = $this->t('Product name');
    $header['server_type'] = $this->t('Server catalog');
    $header['room'] = $this->t('Room');
    $header['display'] = $this->t('Dispaly');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $rids = $entity->getSimplevalue('rids');
    $rids_arr = json_decode($rids);
    $option_room = array();
    if (!empty($rids_arr)) {
      foreach ($rids_arr as $k => $v) {
        if ($v) {
          $option_room[$k] = entity_load('room', $k)->label();
        }
      }
    }
  	$row['id'] = $entity->getSimpleValue('pid');
    $row['name'] = $entity->getSimpleValue('name');
    $row['server_type'] = $entity->getObject('server_type')->label();
    $row['room'] = implode(",", $option_room);
    $row['display'] = $entity->getSimpleValue('front_Dispaly') ? t('Yes') : t('No');
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = array();
    if ($entity->access('price_view') && $entity->hasLinkTemplate('price-view')) {
      $operations['price_view'] = array(
        'title' => $this->t('Price setting'),
        'weight' => 11,
        'url' => $entity->urlInfo('price-view'),
      );
    }

    if ($entity->access('price_view') && $entity->hasLinkTemplate('business-price-view')) {
      $operations['business_price_view'] = array(
        'title' => $this->t('Business setting'),
        'weight' => 12,
        'url' => $entity->urlInfo('business-price-view'),
      );
    }
    if ($entity->access('product_clone')) {
      $operations['product_clone'] = array(
        'title' => $this->t('Product clone'),
        'weight' => 13,
        'url' => $entity->urlInfo('edit-clone-form'),
      );
    }
    return $operations + parent::getDefaultOperations($entity);
  }


}
