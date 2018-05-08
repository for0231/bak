<?php

/**
 * @file
 * Contains \Drupal\member\Plugin\EntityReferenceSelection\ClientSelection.
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
 *   id = "client_selection",
 *   label = @Translation("Client selection"),
 *   entity_types = {"user"},
 *   group = "client_group",
 *   weight = 1
 * )
 */

class ClientSelection extends SelectionBase {
  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $target_type = $this->configuration['target_type'];
    
    $query = $this->buildEntityQuery($match, $match_operator);
    $query->condition('user_type', 'client');
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
      $client = \Drupal::service('member.memberservice')->queryDataFromDB('client', $entity->id());
      $label = $entity->label() . '_' . $client->client_name . '['. $client->corporate_name . ']';
      $options[$bundle][$entity_id] =  SafeMarkup::checkPlain($label);
    }

    return $options;
  }


}
