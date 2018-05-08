<?php

namespace Drupal\paypro;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 *
 */
class PayproAccessControlHandler extends EntityAccessControlHandler {

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
        return AccessResult::allowedIfHasPermission($account, 'administer paypro edit');

      break;
      case 'detail':
        return AccessResult::allowedIfHasPermission($account, 'administer paypro detail');

      break;
      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'administer paypro delete');

      break;
      default:
        return parent::checkAccess($entity, $operation, $account);
    }
  }

}
