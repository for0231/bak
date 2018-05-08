<?php

/**
 * @file
 * Contains \Drupal\knowledge\KnowledgeContentAccessHandler.
 */

namespace Drupal\knowledge;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

class KnowledgeContentAccessHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'administer knowledge content edit');
      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'administer knowledge content delete');
      default:
        return parent::checkAccess($entity, $operation, $account);
    }
  }
}
