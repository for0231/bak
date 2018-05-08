<?php

namespace Drupal\online;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

class OnlineContentAccessHandler  extends EntityAccessControlHandler {
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case '屏蔽':
        return AccessResult::allowedIfHasPermission($account, 'administer online content delete');
      case '接受':
        return AccessResult::allowedIfHasPermission($account, 'administer online content reply');
      default:
        return parent::checkAccess($entity, $operation, $account);
    }
  }
}
