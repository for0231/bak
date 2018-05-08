<?php

/**
 * @file
 * Contains \Drupal\idc\RoomListBuilder.
 */

namespace Drupal\idc;

use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines a class to build a listing of part entities.
 *
 * @see \Drupal\idc\Entity\Room
 */
class RoomListBuilder extends EntityListBuilder {
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Room name');
    $header['area'] = $this->t('Area');
    $header['cabinet_number'] = $this->t('Cabinet number');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['name'] = $entity->label();
    if($area = $entity->getObject('area')) {
      $row['area'] = $area->label();
    } else {
      $row['area'] = '';
    }
    $row['cabinet_number'] = $entity->getSimpleValue('cabinet_number');
    return $row + parent::buildRow($entity);
  }

  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = array();
    if ($entity->access('view') && $entity->hasLinkTemplate('loke-over')) {
      $operations['look_over'] = array(
        'title' => $this->t('look over'),
        'weight' => 1,
        'url' => $entity->urlInfo('loke-over')
      );
    }
    return $operations + parent::getDefaultOperations($entity);
  }
}
