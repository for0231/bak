<?php

/**
 * @file
 * Definition of Drupal\knowledge\KnowledgeTypeStorage.
 */

namespace Drupal\knowledge;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Query\QueryInterface;

/**
 * Defines a Controller class for taxonomy terms.
 */
class KnowledgeTypeStorage extends SqlContentEntityStorage  {
  /**
   * {@inheritdoc}
   */
  public function loadByProperties(array $values = array()) {
    $entity_query = $this->getQuery()
      ->sort('id');
    $this->buildPropertyQuery($entity_query, $values);
    $result = $entity_query->execute();
    return $result ? $this->loadMultiple($result) : array();
  }
}
