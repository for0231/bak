<?php

namespace Drupal\paypro\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;

/**
 * Provides specific access control for the user entity type.
 *
 * @EntityReferenceSelection(
 *   id = "user_name_selection",
 *   label = @Translation("User selection"),
 *   entity_types = {"user"},
 *   group = "username",
 *   weight = 1
 * )
 */
class UserNameSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);
    if (isset($match)) {
      $query->condition('realname', $match, $match_operator);
    }
    return $query;
  }

}
