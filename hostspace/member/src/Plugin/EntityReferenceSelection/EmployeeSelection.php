<?php

/**
 * @file
 * Contains \Drupal\member\Plugin\EntityReferenceSelection\EmployeeSelection.
 */

namespace Drupal\member\Plugin\EntityReferenceSelection;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\SelectionBase;


/**
 * Provides specific access control for the host_project entity type.
 *
 * @EntityReferenceSelection(
 *   id = "employee_selection",
 *   label = @Translation("Employee selection"),
 *   entity_types = {"user"},
 *   group = "employee_group",
 *   weight = 1
 * )
 */

class EmployeeSelection extends SelectionBase {
  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $target_type = $this->configuration['target_type'];
    
    $query = $this->buildEntityQuery($match, $match_operator);
    $query->condition('user_type', 'employee');
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
      $emp = \Drupal::service('member.memberservice')->queryDataFromDB('employee', $entity->id());
      $label = $entity->label() . '_' . $emp->employee_name;
      $options[$bundle][$entity_id] =  SafeMarkup::checkPlain($label);
    }

    return $options;
  }
}
