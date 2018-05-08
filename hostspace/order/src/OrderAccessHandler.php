<?php

/**
 * @file
 * Contains \Drupal\order\orderAccessHandler.
 */

namespace Drupal\order;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

class OrderAccessHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'accept':
        if($entity->getSimpleValue('status') == 3 && $entity->getSimpleValue('accept') == 0) {
			    return AccessResult::allowedIfHasPermission($account, 'administer order accept');
        }
        else {
          return AccessResult::neutral();
        }
        break;
      case 'detail':
			  return AccessResult::allowedIfHasPermission($account, 'administer order view');
        break;
      case 'change':
        if($entity->getSimpleValue('status') == 0) {
          return AccessResult::allowedIfHasPermission($account, 'administer price change apply');
        } else {
          return AccessResult::neutral();
        }
        break;
      case 'trial':
        $order_server = \Drupal::service('order.orderservice'); 
        if($entity->getSimpleValue('status') == 0 && !$order_server->checkTrialHostclient($entity->id())) {
          return AccessResult::allowedIfHasPermission($account, 'administer order trial apply');
        } else {
          return AccessResult::neutral();
        }
        break;
    }
  }
}


