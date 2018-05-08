<?php

/**
 * @file
 * 工单实体公共信息
 * \Drupal\worksheet\Entity\WorkSheetBase;.
 */

namespace Drupal\worksheet\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\worksheet\WorkSheetEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the sop entity class.
 *
 * @ContentEntityType(
 *   id = "work_sheet_base",
 *   label = "工单公共信息",
 *   handlers = {
 *     "storage" = "Drupal\worksheet\WorkSheetBaseStorage"
 *   },
 *   base_table = "work_sheet_base",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "code"
 *   }
 * )
 */
class WorkSheetBase extends WorkSheetEntityBase {
  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel('处理id')
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);
    
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID.'))
      ->setReadOnly(TRUE);

    $fields['ip'] = BaseFieldDefinition::create('string')
      ->setLabel('ip')
      ->setDescription('ip');
    
    $fields['wid'] = BaseFieldDefinition::create('integer')
      ->setLabel('工单Id')
      ->setDescription('所属id')
      ->setSetting('unsigned', TRUE);
    
    $fields['entity_name'] = BaseFieldDefinition::create('string')
      ->setLabel('实体名')
      ->setSetting('max_length', 50)
      ->setDescription('实体名');
    return $fields;
  }
}
