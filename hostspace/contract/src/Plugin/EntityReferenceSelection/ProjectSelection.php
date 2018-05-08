<?php

/**
 * @file
 * Contains \Drupal\contract\Plugin\EntityReferenceSelection\ProjectSelection.
 */

namespace Drupal\contract\Plugin\EntityReferenceSelection;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\SelectionBase;


/**
 * Provides specific access control for the host_project entity type.
 *
 * @EntityReferenceSelection(
 *   id = "project_selection",
 *   label = @Translation("Project selection"),
 *   entity_types = {"host_project"},
 *   group = "project_group",
 *   weight = 1
 * )
 */

class ProjectSelection extends SelectionBase {
  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $target_type = $this->configuration['target_type'];
    
    $query = $this->buildEntityQuery(null, null);
    $query->condition('status', 1);
    $query->condition('name', $match, $match_operator);
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
      $label = $entity->label() . '_' . $entity->getProjectproperty('name');
      $options[$bundle][$entity_id] =  SafeMarkup::checkPlain($label);
    }

    return $options;
  }


}
