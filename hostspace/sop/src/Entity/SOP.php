<?php

/**
 * @file
 * Construct Parent class for sop.
 * \Drupal\sop\Entity\SOP;.
 */

namespace Drupal\sop\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\sop\SOPEntityBase;
/**
 * Defines the sop entity class.
 *
 * @ContentEntityType(
 *   id = "sop",
 *   label = @Translation("工单"),
 *   handlers = {
 *     "access" = "Drupal\sop\SOPAccessControlHandler",
 *     "list_builder" = "Drupal\sop\SOPTaskListBuilder",
 *     "form" = {
 *       "delete" = "Drupal\sop\Form\SopDeleteForm",
 *     },
 *   },
 *   base_table = "sop_task",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "edit-form" = "/admin/sop/{sop}/edit",
 *     "delete-form" = "/admin/sop/{sop}/delete"
 *   }
 * )
 */
class SOP extends SOPEntityBase {
  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['sid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('工单类型模块ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['module'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Type'))
      ->setDescription(t('实体类型'))
      ->setTranslatable(TRUE);

    return $fields;
  }

}
