<?php

/**
 * @file
 * Contains \Drupal\server\ServerAccessControlHandler.
 */

namespace Drupal\server;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the part entity.
 *
 * @see \Drupal\use\Entity\Role
 */
class ServerAccessControlHandler extends EntityAccessControlHandler {
  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** 
     * @var \Drupal\Core\Entity\EntityInterface|
     *      \Drupal\user\EntityOwnerInterface $entity 
     */
    switch ($operation) {
      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'administer server edit');
        break; 
      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'administer server delete');
        break;  
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'administer server overview');    
      default:
        return parent::checkAccess($entity, $operation, $account);    
    }
  }
}
