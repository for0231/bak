<?php

/**
 * @file
 * Contains \Drupal\idc\RoomAccessControlHandler.
 */

namespace Drupal\idc;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

class RoomAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'Administer idc room edit');
      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'administer idc room delete');
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'administer idc room view');
      default:
        return parent::checkAccess($entity, $operation, $account);
    }
  }
}
