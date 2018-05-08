<?php

/**
 * @file
 * Contains \Drupal\product\ProductPriceAccessHandler.
 */

namespace Drupal\product;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

class ProductPriceAccessHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'administer product price edit');
      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'administer product price delete');
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'administer product price view'); 
      default:
        return parent::checkAccess($entity, $operation, $account);
    }
  }
}
