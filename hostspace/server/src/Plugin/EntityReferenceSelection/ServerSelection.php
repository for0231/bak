<?php

/**
 * @file
 * Contains \Drupal\server\Plugin\entity_reference\selection\ServerCabinetSelection.
 */

namespace Drupal\server\Plugin\EntityReferenceSelection;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\SelectionBase;

/**
 * Provides specific access control for the idc entity type.
 *
 * @EntityReferenceSelection(
 *   id = "server_cabinet_server",
 *   label = @Translation("server selection"),
 *   entity_types = {"server"},
 *   group = "idc",
 *   weight = 1
 * )
 */
class ServerSelection extends SelectionBase {

  /**
   * {@inheritdoc}
   */
  public function validateAutocompleteInput($input, &$element, FormStateInterface $form_state, $form, $strict = TRUE) {
    $server_type = $form_state->getValue('server_id')[0]['server_type'];

    $text = SafeMarkup::checkPlain($input . '$' . $server_type);
    $bundled_entities = $this->getReferenceableEntities($text, '=', 6);
    $entities = array();
    foreach ($bundled_entities as $entities_list) {
      $entities += $entities_list;
    }
    $params = array(
      '%value' => $input,
      '@value' => $input,
    );
    if (empty($entities)) {
      if ($strict) {
        // Error if there are no entities available for a required field.
        $form_state->setError($element, t('There are no entities matching "%value".', $params));
      }
    }
    elseif (count($entities) > 5) {
      $params['@id'] = key($entities);
      // Error if there are more than 5 matching entities.
      $form_state->setError($element, t('Many entities are called %value. Specify the one you want by appending the id in parentheses, like "@value (@id)".', $params));
    }
    elseif (count($entities) > 1) {
      // More helpful error if there are only a few matching entities.
      $multiples = array();
      foreach ($entities as $id => $name) {
        $multiples[] = $name . ' (' . $id . ')';
      }
      $params['@id'] = $id;
      $form_state->setError($element, t('Multiple entities match this reference; "%multiple". Specify the one you want by appending the id in parentheses, like "@value (@id)".', array('%multiple' => implode('", "', $multiples))));
    }
    else {
      // Take the one and only matching entity.
      return key($entities);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $values = explode('$', $match);
    //$type = end($values);
    $text = $values[0];
    $type = $values[1];
    $room = $values[2];
    //$text = substr($match, 0, strlen($match) - strlen($type)-1);
    $query = $this->buildEntityQuery($text, $match_operator);
    $query->condition('status_equipment', 'off'); //只读以未上柜的。
    if (\Drupal::moduleHandler()->moduleExists('common')) {
      $config = \Drupal::config('common.global');
      $target_type = $this->configuration['target_type'];
      if ($config->get('is_district_room_id')) {
        $query->condition('rid', $room, '='); //验证服务器所属机房
      }
    }
    if(!empty($type)) {
      $query->condition('type', $type, '=');
    }
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
