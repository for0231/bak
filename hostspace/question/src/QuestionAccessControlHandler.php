<?php

/**
 * @file
 * Contains \Drupal\question\QuestionAccessControlHandler.
 */

namespace Drupal\question;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the part entity.
 *
 * @see \Drupal\use\Entity\Role
 */


class QuestionAccessControlHandler extends EntityAccessControlHandler {
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
        return AccessResult::allowedIfHasPermission($account, 'administer question detail');
        break;
      case 'delete':
			 return AccessResult::allowedIfHasPermission($account, '');
        break;
    }
  }

  /**
   * 抛出申报问题的钩子
   */
/*  protected function invokeQuestionHook($hook, EntityInterface $entity) {
    // Invoke the hook.
    $this->moduleHandler->invokeAll($this->entityTypeId . '_' . $hook, array($entity));
    // Invoke the respective entity-level hook.
    $this->moduleHandler->invokeAll('entity_' . $hook, array($entity, $this->entityTypeId));
  }*/

}
