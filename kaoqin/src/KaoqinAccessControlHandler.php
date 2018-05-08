<?php

namespace Drupal\kaoqin;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 *
 */
class KaoqinAccessControlHandler extends EntityAccessControlHandler {

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
        return AccessResult::allowedIfHasPermission($account, 'administer kaoqin edit');

      break;
      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'administer kaoqin delete');

      break;
      default:
        return parent::checkAccess($entity, $operation, $account);
    }
  }

}
