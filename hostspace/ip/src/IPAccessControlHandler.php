<?php

/**
 * @file
 * Contains \Drupal\ip\IPAccessControlHandler.
 */

namespace Drupal\ip;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the part entity.
 *
 * @see \Drupal\use\Entity\Role
 */


class IPAccessControlHandler extends EntityAccessControlHandler {
  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** 
     * @var \Drupal\Core\Entity\EntityInterface|
     *      \Drupal\user\EntityOwnerInterface $entity 
     */
    switch ($operation) {
      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'administer ip delete');
        break;
      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'administer ip edit');
        break;
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'administer ip list'); 
      default:
        return parent::checkAccess($entity, $operation, $account);
    }
  }
}
