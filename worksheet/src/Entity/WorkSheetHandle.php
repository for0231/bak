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
 *   id = "work_sheet_handle",
 *   label = "工单处理实体",
 *   handlers = {
 *     "storage" = "Drupal\worksheet\WorkSheetHandleStorage"
 *   },
 *   base_table = "work_sheet_handle",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "operation"
 *   }
 * )
 */
class WorkSheetHandle extends ContentEntityBase {
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

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel('处理人')
      ->setDescription('处理人')
      ->setSetting('target_type', 'user');

    $fields['time'] = BaseFieldDefinition::create('integer')
      ->setLabel('处理时间')
      ->setDescription('处理时间')
      ->setSetting('unsigned', TRUE);

    $fields['operation_id'] = BaseFieldDefinition::create('integer')
      ->setLabel('操作类型ID')
      ->setDescription('操作类型ID')
      ->setSetting('unsigned', TRUE);

    $fields['operation'] = BaseFieldDefinition::create('string')
      ->setLabel('操作类型名称')
      ->setDescription('操作类型名称');

    $fields['work_sheet'] = BaseFieldDefinition::create('string_long')
      ->setLabel('工单对象')
      ->setDescription('工单对象')
      ->setSetting('case_sensitive', TRUE);

    $fields['is_abnormal'] = BaseFieldDefinition::create('integer')
      ->setLabel('是否异常')
      ->setDescription('是否异常')
      ->setSetting('unsigned', TRUE);

    $fields['person_liable'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel('异常责任人')
      ->setDescription('异常责任人')
      ->setSetting('target_type', 'user');

    $fields['reason'] = BaseFieldDefinition::create('string')
      ->setLabel('异常原因')
      ->setDescription('异常原因');

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

  /**
   *获取异常责任人
   */
  public function liableUser() {
    if($this->get('person_liable')->target_id) {
      $user = $this->get('person_liable')->entity;
      if($user->hasField('real_name') && !empty($user->get('real_name')->value)) {
        return $user->get('real_name')->value;
      }
      return $user->label();
    }
    return '';
  }
}
