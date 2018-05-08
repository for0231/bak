<?php

/**
 * @file
 * Definition of Drupal\order\OrderStorage.
 */

namespace Drupal\order;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Query\QueryInterface;

/**
 * Defines a Controller class for taxonomy terms.
 */
class OrderStorage extends SqlContentEntityStorage  {
   /**
   * {@inheritdoc}
   */
  protected function buildPropertyQuery(QueryInterface $entity_query, array $values) {
    foreach($values as $name=>$value) {
      if(is_array($value)) {
        $entity_query->condition($value['field'], $value['value'], $value['op']);
      } else {
        $entity_query->condition($name, $value);
      }
    }
  }
}
