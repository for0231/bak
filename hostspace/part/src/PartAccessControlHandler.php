<?php

/**
 * @file
 * Contains \Drupal\part\PartAccessControlHandler.
 */

namespace Drupal\part;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the part entity.
 *
 * @see \Drupal\user\Entity\Role
 */
class PartAccessControlHandler extends EntityAccessControlHandler {
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
        return AccessResult::allowedIfHasPermission($account, 'administer parts edit');
        break;
      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'administer parts delete');
        break;
    }
  }
}
