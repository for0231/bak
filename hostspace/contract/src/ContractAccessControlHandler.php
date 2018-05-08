<?php
/**
 * @file
 * Contains \Drupal\contract\ContractAccessControlHandler
 */

namespace Drupal\contract;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

class ContractAccessControlHandler extends EntityAccessControlHandler {
  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $type = $entity->getEntityTypeId(); 
    if($type == 'host_project') {
      switch($operation) {
         case 'delete':
		  	 return AccessResult::allowedIfHasPermission($account, 'administer peoject delete');
          break;
        case 'update':
          return AccessResult::allowedIfHasPermission($account, 'administer peoject add');
          break;
        case 'view':
          return AccessResult::allowedIfHasPermission($account, 'administer project list'); 
        default:
          return parent::checkAccess($entity, $operation, $account);
      }
    } elseif($type == 'host_contract') {
      switch($operation) {
         case 'delete':
		  	 return AccessResult::allowedIfHasPermission($account, 'administer peoject delete');
          break;
        case 'update':
          return AccessResult::allowedIfHasPermission($account, 'administer contract add');
          break;
        case 'view':
          return AccessResult::allowedIfHasPermission($account, 'administer contract list'); 
          break;
        case 'execute':
          return AccessResult::allowedIfHasPermission($account, 'administer contract execute');
          break;
        case 'stop':
          return AccessResult::allowedIfHasPermission($account, 'administer contract execute');
          break;
        case 'complete':
          return AccessResult::allowedIfHasPermission($account, 'administer contract execute');
          break;

        default:
          return parent::checkAccess($entity, $operation, $account);
      }

    } elseif($type == 'contract_user') {
      switch($operation) {
         case 'delete':
		  	 return AccessResult::allowedIfHasPermission($account, 'administer contratc client delete');
          break;
        case 'update':
          return AccessResult::allowedIfHasPermission($account, 'administer contratc client add');
          break;
        case 'view':
          return AccessResult::allowedIfHasPermission($account, 'administer contratc client list'); 
        default:
          return parent::checkAccess($entity, $operation, $account);
      }
    }
  } 
}
