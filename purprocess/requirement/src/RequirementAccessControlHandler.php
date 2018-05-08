<?php

namespace Drupal\requirement;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 *
 */
class RequirementAccessControlHandler extends EntityAccessControlHandler {

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
        return AccessResult::allowedIfHasPermission($account, 'administer requirement edit');

      break;
      case 'detail':
        return AccessResult::allowedIfHasPermission($account, 'administer requirement detail');

      break;
      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'administer requirement delete');

      break;
      case 'create_audit_locale':
        return AccessResult::allowedIfHasPermission($account, 'administer audit_locale edit');

      break;
      case 'update_audit_locale':
        return AccessResult::allowedIfHasPermission($account, 'administer audit_locale edit');

      break;
      default:
        return parent::checkAccess($entity, $operation, $account);
    }
  }

}
