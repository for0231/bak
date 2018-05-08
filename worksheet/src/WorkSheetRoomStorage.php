<?php

/**
 * @file
 * Definition of Drupal\worksheet\WorkSheetRoomStorage.
 */

namespace Drupal\worksheet;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Entity\Query\QueryInterface;

/**
 * Defines a Controller class for taxonomy terms.
 */
class WorkSheetRoomStorage extends SqlContentEntityStorage  {
  /**
   * 获取基础表查询
   */
  public function getBaseQuery() {
    return $this->database->select($this->entityType->getBaseTable(), 'base')
      ->fields('base', array('wid'));
  }
}
