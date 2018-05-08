<?php

/**
 * @file
 * Contains \Drupal\order\orderAccessHandler.
 */

namespace Drupal\order;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

class HostclientAccessHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'stop':
        return AccessResult::allowedIfHasPermission($account, 'administer hostclient stop handle');
      case 'view':
       return AccessResult::allowedIfHasPermission($account, 'administer hostclient view');  
      case 'remove_ip':
       return AccessResult::allowedIfHasPermission($account, 'administer hostclient remove ip');  
      default:
        return parent::checkAccess($entity, $operation, $account);
    }
  }
}


