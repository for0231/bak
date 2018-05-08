<?php

namespace Drupal\paypre;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 *
 */
class PaypreAccessControlHandler extends EntityAccessControlHandler {

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
        return AccessResult::allowedIfHasPermission($account, 'administer paypre edit');

      break;
      case 'detail':
        return AccessResult::allowedIfHasPermission($account, 'administer paypre detail');

      break;
      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'administer paypre delete');

      break;
      default:
        return parent::checkAccess($entity, $operation, $account);
    }
  }

}
