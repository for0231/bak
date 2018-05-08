<?php
/**
 * \Drupal\hostlog\OperationEntityNotificationSubscriber
 */

namespace Drupal\hostlog;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;

class OperationEntityNotificationSubscriber {

  public function onCreate(EntityInterface $entity) {
    $this->onCallback('create', $entity);
  }

  public function onUpdate(EntityInterface $entity) {
    $this->onCallback('update', $entity);
  }

  public function onDelete(EntityInterface $entity) {
    $this->onCallback('delete', $entity);
  }

  protected function onCallback($operation, EntityInterface $entity) {
    $user = \Drupal::currentUser();
    $context_reminder = array(
      'uid' => $user->id(),
      'role' => implode(',', $user->getRoles()),
    );
    $context_common = array(
      'type' => $entity->getEntityTypeId().'__'.$operation,
      'object' => serialize($entity),
      'rank' => 5,
    );
    \Drupal::service('operation.reminder')->log($context_common + $context_reminder);
  }
}
