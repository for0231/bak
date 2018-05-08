<?php

/**
 * @file
 * Contains file
 * \Drupal\sop\SOPEntityBase.
 */

namespace Drupal\sop;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
/**
 *
 */
class SOPEntityBase extends ContentEntityBase {
  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID.'))
      ->setReadOnly(TRUE);

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The sop ID.'));

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The cpu language code.'))
      ->setRevisionable(TRUE);
    /**
     * $fields['mips'] = BaseFieldDefinition::create('entity_reference')
     * ->setLabel(t('管理IP'))
     * ->setSetting('target_type', 'ipm')
     * ->setRequired(TRUE)
     * ->setDescription(t('最终目的:自动获取与管理IP相关的客户,机房,机柜等数据'));
    */
    $fields['solving_uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('处理人'))
      ->setSetting('target_type', 'user')
      ->setQueryable(FALSE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    $fields['sop_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('类型'))->setDescription(t('I类,P类,E类'))
      ->setTranslatable(TRUE);

    $fields['sop_op_type'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('操作类型'))
      ->setDescription(t('如,服务器上下架,开关机等'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['sop_status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('工单状态'))
      ->setDescription(t('工单的状态变更,如新工单，运维处理等'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['hid'] = BaseFieldDefinition::create('entity_reference')
      ->setSetting('target_type', 'hostclient')
      ->setLabel(t('Hostclient Id.'))
      ->setQueryable(FALSE)
      ->setDefaultValue(0);

    $fields['handle_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Handle Info Id.'))
      ->setDefaultValue(0);

    $fields['presolving_uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('上次处理人'))
      ->setSetting('target_type', 'user')
      ->setQueryable(FALSE)
      ->setTranslatable(TRUE);
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the cpu was created.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);
    /**
     * 0 未完成
     * 1 业务完成
     * 2 技术完成
     */
    $fields['sop_complete'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('SOP是否完成'))
      ->setDefaultValue(0);
    return $fields;
  }

}
