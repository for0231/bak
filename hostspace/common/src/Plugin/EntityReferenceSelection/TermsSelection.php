<?php

/**
 * @file
 * Contains \Drupal\common\Plugin\EntityReferenceSelection\TermsSelection.
 */

namespace Drupal\common\Plugin\EntityReferenceSelection;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\SelectionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Provides specific access control for the taxonomy_term entity type.
 *
 * @EntityReferenceSelection(
 *   id = "sub_term_selection",
 *   label = @Translation("Taxonomy Term selection"),
 *   entity_types = {"taxonomy_term"},
 *   group = "sub_term",
 *   weight = 1
 * )
 */
class TermsSelection extends SelectionBase {

  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    if ($match || $limit) {
      return parent::getReferenceableEntities($match , $match_operator, $limit);
    }

    $options = array();
    $bundles = $this->entityManager->getBundleInfo('taxonomy_term');
    $handler_settings = $this->configuration['handler_settings'];
    $bundle_names = !empty($handler_settings['target_bundles']) ? $handler_settings['target_bundles'] : array_keys($bundles);
    foreach ($bundle_names as $key => $bundle) {
      if ($vocabulary = Vocabulary::load($key)) {
        if ($terms = $this->entityManager->getStorage('taxonomy_term')->loadTree($vocabulary->id(), $bundle['parent_id'], NULL, TRUE)) {
          foreach ($terms as $term) {
            $options[$vocabulary->id()][$term->id()] = str_repeat('-', $term->depth) . SafeMarkup::checkPlain($term->getName());
          }
        }
      }
    }

    return $options;
  }

  /**
   * Builds an EntityQuery to get referenceable entities.
   *
   * @param string|null $match
   *   (Optional) Text to match the label against. Defaults to NULL.
   * @param string $match_operator
   *   (Optional) The operation the matching should be done with. Defaults
   *   to "CONTAINS".
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The EntityQuery object with the basic conditions and sorting applied to
   *   it.
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $target_type = $this->configuration['target_type'];
    $handler_settings = $this->configuration['handler_settings'];
    $entity_type = $this->entityManager->getDefinition($target_type);

    $query = $this->entityManager->getStorage($target_type)->getQuery();

    // If 'target_bundles' is NULL, all bundles are referenceable, no further
    // conditions are needed.
    if (isset($handler_settings['target_bundles']) && is_array($handler_settings['target_bundles'])) {
      // If 'target_bundles' is an empty array, no bundle is referenceable,
      // force the query to never return anything and bail out early.
      if ($handler_settings['target_bundles'] === []) {
        $query->condition($entity_type->getKey('id'), NULL, '=');
        return $query;
      }
      else {
        $query->condition($entity_type->getKey('bundle'), array_keys($handler_settings['target_bundles']), 'IN');
      }
    }

    if (isset($match) && $label_key = $entity_type->getKey('label')) {
      $query->condition($label_key, $match, $match_operator);
    }

    // Add entity-access tag.
    $query->addTag($target_type . '_access');

    // Add the Selection handler for system_query_entity_reference_alter().
    $query->addTag('entity_reference');
    $query->addMetaData('entity_reference_selection_handler', $this);

    // Add the sort option.
    if (!empty($handler_settings['sort'])) {
      $sort_settings = $handler_settings['sort'];
      if ($sort_settings['field'] != '_none') {
        $query->sort($sort_settings['field'], $sort_settings['direction']);
      }
    }
    return $query;
  }
}
