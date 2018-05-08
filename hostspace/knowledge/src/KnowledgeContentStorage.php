<?php

/**
 * @file
 * Definition of Drupal\knowledge\KnowledgeContentStorage.
 */

namespace Drupal\knowledge;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Query\QueryInterface;

/**
 * Defines a Controller class for taxonomy terms.
 */
class KnowledgeContentStorage extends SqlContentEntityStorage  {
  /**
   * 搜索关键字
   * @param string $key
   *   要搜索的关键字
   * @param int $page_size
   *   每页显示几条
   *
   * @return array
   *   返回查询出来的实体数组
   */
  public function loadBySearch($key, $page_size = 5) {
    $entity_query = $this->getQuery();
    if(!empty($key)) {
      $entity_query->condition('title', $key, 'CONTAINS');
    }
    $entity_query->pager($page_size);
    $entity_ids = $entity_query->execute();
    return $this->loadMultiple($entity_ids);
  }
}
