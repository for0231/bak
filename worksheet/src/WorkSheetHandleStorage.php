<?php

/**
 * @file
 * Definition of Drupal\worksheet\WorkSheetHandleStorage.
 */

namespace Drupal\worksheet;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Query\QueryInterface;

/**
 * Defines a Controller class for taxonomy terms.
 */
class WorkSheetHandleStorage extends SqlContentEntityStorage  {
  /**
   * 获取基础表查询
   */
  public function getBaseQuery() {
    return $this->database->select($this->entityType->getBaseTable(), 'base')
      ->fields('base', array('id'));
  }

  /**
   * 获取最后一次处理
   */
  public function getLastHandle($wid, $entity_name) {
    $query = $this->getBaseQuery();
    $query->condition('wid', $wid);
    $query->condition('entity_name', $entity_name);
    $query->orderBy('id', 'DESC');
    $query->range(0,1);
    $result = $query->execute()->fetchCol();
    if($result) {
      $entitys = $this->loadMultiple($result);
      return reset($entitys);
    }
    return array();
  }
}
