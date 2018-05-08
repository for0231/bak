<?php

/**
 * @file
 * SOP之机房事务.
 * \Drupal\sop\Entity\Room;.
 */

namespace Drupal\sop\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\sop\SOPEntityBase;
/**
 * Defines the sop entity class.
 *
 * @ContentEntityType(
 *   id = "sop_task_room",
 *   label = @Translation("机房事务"),
 *   handlers = {
 *     "form" = {
 *       "default" = "Drupal\sop\Form\SopTaskRoomForm",
 *     },
 *   },
 *   base_table = "sop_task_room",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class Room extends SOPEntityBase {
  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    if ($this->isNew()) {
      $this->set('created', REQUEST_TIME);
      $this->set('uid', \Drupal::currentUser()->id());
      $this->set('sop_op_type', 21);
      $this->set('sop_status', 0);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // \Drupal::service('sop.soptaskservice')->save($this, $update);.
  }


  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('需求、故障现象'));

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Create user'))
      ->setDescription(t('The user ID of the author.'))
      ->setSetting('target_type', 'user')
      ->setTranslatable(TRUE);

    $fields['mip'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('管理IPID'))
      ->setDescription(t('管理IP序号.'))
      ->setSetting('target_type', 'ipm')
      ->setTranslatable(TRUE);

    $fields['base_description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('下一步操作'));

    $fields['result_description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('处理过程、结果'));
    return $fields;
  }

}
