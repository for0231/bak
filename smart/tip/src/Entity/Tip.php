<?php

namespace Drupal\tip\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

use Drupal\tip\TipEntityBase;

/**
 * Defines the tip entity class.
 *
 * @ContentEntityType(
 *   id = "tip",
 *   label = @Translation("Tip"),
 *   base_table = "smart_tip",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid"  = "uuid",
 *   },
 * )
 */
class Tip extends TipEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['ttid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Child tip no.'))
      ->setDescription(t('The child tip no.'))
      ->setSetting('unsigned', TRUE);

    $fields['type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Type'))
      ->setDescription(t('Tip Type'))
      ->setTranslatable(TRUE);

    return $fields;
  }

}
