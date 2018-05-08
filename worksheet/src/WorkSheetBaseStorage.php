<?php

/**
 * @file
 * Definition of Drupal\worksheet\WorkSheetBaseStorage.
 */

namespace Drupal\worksheet;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Entity\Query\QueryInterface;

/**
 * Defines a Controller class for taxonomy terms.
 */
class WorkSheetBaseStorage extends SqlContentEntityStorage  {
  /**
   * 获取基础表查询
   */
  public function getBaseQuery() {
    return $this->database->select($this->entityType->getBaseTable(), 'base')
      ->fields('base', array('id'));
  }
}
