<?php

/**
 * @file
 * Contains \Drupal\ip\Plugin\entity_reference\selection\CabinetServerSelection.
 */

namespace Drupal\ip\Plugin\EntityReferenceSelection;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\SelectionBase;
use Drupal\Component\Utility\SafeMarkup;
/**
 * Provides specific access control for the idc entity type.
 *
 * @EntityReferenceSelection(
 *   id = "ips_cabinet_server",
 *   label = @Translation("ips selection"),
 *   entity_types = {"ips"},
 *   group = "idc",
 *   weight = 1
 * )
 */
class IPSSelection extends SelectionBase {

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $target_type = $this->configuration['target_type'];;
    
    $query = $this->buildEntityQuery($match, $match_operator);
    $query->condition('status_equipment', 'off');
    if ($limit > 0) {
      $query->range(0, $limit);
    }

    $result = $query->execute();

    if (empty($result)) {
      return array();
    }

    $options = array();
    $entities = entity_load_multiple($target_type, $result);
    foreach ($entities as $entity_id => $entity) {
      $bundle = $entity->bundle();
      $label = $entity->label();
      $options[$bundle][$entity_id] =  SafeMarkup::checkPlain($label);
    }
    return $options;
  }

}
