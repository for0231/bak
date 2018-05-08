<?php

/**
 * @file
 * Construct Parent class for cpu, mem, board.
 * \Drupal\part\Entity\Part;
 */

namespace Drupal\part\Entity;

use Drupal\Component\Utility\Number;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\taxonomy;

/**
 * Defines the part entity class.
 * @ContentEntityType(
 *   id = "part",
 *   label = @Translation("Part"),
 *   handlers = {
 *     "access" = "Drupal\part\PartAccessControlHandler",
 *     "list_builder" = "Drupal\part\PartListBuilder",
 *     "form" = {
 *       "delete" = "Drupal\part\Form\PartDeleteForm",
 *     },
 *   },
 *   base_table = "part",
 *   entity_keys = {
 *     "id" = "pid",
 *     "label" = "standard",
 *     "uuid"  = "uuid",
 *   },
 *   links = {
 *     "edit-form" = "/admin/part/{part}/edit",
 *     "delete-form" = "/admin/part/{part}/delete"
 *   }
 * )
 */
class Part extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields['pid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Part ID'))
      ->setDescription(t('The part ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['ppid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Part no.'))
      ->setDescription(t('The part no.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The cpu UUID.'))
      ->setReadOnly(TRUE);

    $fields['type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Type'))
      ->setDescription(t('Part Type'))
      ->setTranslatable(TRUE);

    $fields['brand'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Brand'))
      ->setDescription(t('Part Brand'))
      ->setTranslatable(TRUE);

    $fields['model'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Model'))
      ->setDescription(t('Part Model'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE);

    $fields['standard'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Standard'))
      ->setDescription(t('Part Standard'))
      ->setTranslatable(TRUE);

    $fields['stock'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Storage number'))
      ->setDescription(t('Storage number'))
      ->setTranslatable(TRUE);

    $fields['stock_used_rent'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Used storage rent number'))
      ->setDescription(t('Used storage rent number'))
      ->setTranslatable(TRUE);

    $fields['stock_used_free'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Used storage free number'))
      ->setDescription(t('Used storage free number'))
      ->setTranslatable(TRUE);

    $fields['stock_fault'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Used storage fault'))
      ->setDescription(t('Used storage fault'))
      ->setTranslatable(TRUE);

    return $fields;
  }
}
