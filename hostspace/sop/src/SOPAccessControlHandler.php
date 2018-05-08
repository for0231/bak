<?php

/**
 * @file
 * Contains \Drupal\sop\SOPAccessControlHandler.
 */

namespace Drupal\sop;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the sop entity.
 *
 * @see \Drupal\user\Entity\Role
 */
class SOPAccessControlHandler extends EntityAccessControlHandler {
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
        return AccessResult::allowedIfHasPermission($account, 'administrator bus and tech sop permission');

      break;
      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'administrator bus and tech sop permission');

      break;
    }
  }

}
