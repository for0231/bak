<?php

/**
 * @file
 * Contains \Drupal\server\ServerTypeListBuilder.
 */

namespace Drupal\server;

use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a class to build a listing of server entities.
 *
 * @see \Drupal\server\Entity\Part
 */
class ServerTypeListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Catalog name');
    $header['server_number'] = $this->t('Server number');
    $header['uid'] = $this->t('Create user');
    $header['created'] = $this->t('Created');
    $header['room'] = $this->t('最近机房信息');
    return $header  + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['name'] = $entity->label();
    $row['server_number'] = $entity->get('server_number')->value;
    $row['uid'] = $entity->get('uid')->entity->label();
    $row['created'] = date('Y-m-d H:i', $entity->get('created')->value);
    $row['room'] = !empty($entity->get('rid')->value) ? entity_load('room', $entity->get('rid')->value)->label() : '';
    return $row + parent::buildRow($entity);
  }

  /**
   * Gets this list's default operations.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the operations are for.
   *
   * @return array
   *   The array structure is identical to the return value of
   *   self::getOperations().
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = array();
    if ($entity->access('update') && $entity->hasLinkTemplate('add_server-form')) {
      $operations['add_server'] = array(
        'title' => $this->t('Add server'),
        'weight' => 1,
        'url' => $entity->urlInfo('add_server-form')
      );
    }
    if ($entity->access('update') && $entity->hasLinkTemplate('edit-form')) {
      $operations['edit'] = array(
        'title' => $this->t('Edit'),
        'weight' => 2,
        'url' => $entity->urlInfo('edit-form'),
      );
    }
    if ($entity->access('delete') && $entity->hasLinkTemplate('delete-form')) {
      $operations['delete'] = array(
        'title' => $this->t('Delete'),
        'weight' => 3,
        'url' => $entity->urlInfo('delete-form'),
      );
    }
    return $operations;
  }

}
