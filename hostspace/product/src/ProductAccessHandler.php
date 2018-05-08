<?php

/**
 * @file
 * Contains \Drupal\idc\RoomAccessControlHandler.
 */

namespace Drupal\product;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

class ProductAccessHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'administer product edit');
      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'administer product delete');
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'administer product view');
      case 'price_view':
        return AccessResult::allowedIfHasPermission($account, 'administer product price view');
      case 'product_clone':
        return AccessResult::allowedIfHasPermission($account, 'administer product edit');
      default:
        return parent::checkAccess($entity, $operation, $account);
    }
  }
}
