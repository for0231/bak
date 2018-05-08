<?php

/**
 * @file
 * Contains \Drupal\knowledge\KnowledgeTypeAccessHandler.
 */

namespace Drupal\knowledge;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

class KnowledgeTypeAccessHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'administer knowledge type edit');
      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'administer knowledge type delete');
      default:
        return parent::checkAccess($entity, $operation, $account);
    }
  }
}
