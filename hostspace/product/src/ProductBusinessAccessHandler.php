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

class ProductBusinessAccessHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'administer product business edit');
      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'administer product business delete');
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'administer product business view'); 
      default:
        return parent::checkAccess($entity, $operation, $account);
    }
  }
}
