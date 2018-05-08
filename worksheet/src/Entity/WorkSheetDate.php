<?php

/**
 * @file
 * 工单处理实体
 * \Drupal\worksheet\Entity\WorkSheetHandle.
 */

namespace Drupal\worksheet\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the sop entity class.
 *
 * @ContentEntityType(
 *   id = "work_sheet_date",
 *   label = "作息时间实体",
 *   base_table = "work_sheet_date",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "operation"
 *   }
 * )
 */
class WorkSheetDate extends ContentEntityBase {
  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel('处理id')
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID.'))
      ->setReadOnly(TRUE);

    $fields['month'] = BaseFieldDefinition::create('integer')
      ->setLabel('月份')
      ->setDescription('月份');

    $fields['years'] = BaseFieldDefinition::create('integer')
      ->setLabel('年份')
      ->setDescription('年份');

    $fields['dates_info'] = BaseFieldDefinition::create('string_long')
      ->setLabel('当月信息')
      ->setDescription('当月信息');

    return $fields;
  }

}
