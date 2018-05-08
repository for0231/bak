<?php

/**
 * @file
 * 周期类工单实体
 * \Drupal\worksheet\Entity\WorkSheetFault;.
 */

namespace Drupal\worksheet\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\worksheet\WorkSheetEntityBase;
/**
 * Defines the sop entity class.
 *
 * @ContentEntityType(
 *   id = "work_sheet_cycle",
 *   label = "周期工单实体",
 *   base_table = "work_sheet_cycle",
 *   handlers = {
 *     "form" = {
 *       "operation" = "Drupal\worksheet\Form\WorkSheetCycleOperateForm"
 *     }
 *   },
 *   entity_keys = {
 *     "id" = "wid",
 *     "uuid" = "uuid",
 *     "label" = "code"
 *   }
 * )
 */
class WorkSheetCycle extends WorkSheetEntityBase {

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    if($this->isNew()) {
      $this->set('uid', 1);
      $completed = \Drupal::service('worksheet.type')->getCompleteTime($this->get('tid')->value);
      $this->set('completed', $completed);
      $this->set('contacts','admin');
      if(!$this->get('status')->value) {
          $this->set('status', 1);
      }
    }
  }
  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    $this->postSaveCommon($storage, $update);
  }
  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);
    foreach($entities as $entity) {
      parent::deleteWorkSheetBase('work_sheet_cycle', $entity->id());
    }
  }
  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $type_options = \Drupal::service('worksheet.type')->getCycleType();
    $fields['tid']->setSetting('allowed_values', $type_options);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID.'))
      ->setReadOnly(TRUE);

    $fields['wid'] = BaseFieldDefinition::create('integer')
      ->setLabel('工单id')
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);
      
    $fields['ip'] = BaseFieldDefinition::create('string')
      ->setLabel('名称')
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 11
      ));
      
    $fields['phenomenon'] = BaseFieldDefinition::create('string_long')
      ->setLabel('内容')
      ->setDisplayOptions('form', array(
        'type' => 'string_textarea',
        'weight' => 11
      ));

    $fields['unique_cycle_key'] = BaseFieldDefinition::create('string')
      ->setLabel('唯一标识');

    $fields['reason'] = BaseFieldDefinition::create('string_long')
      ->setLabel('处理过程、结果')
      ->setDisplayOptions('form', array(
        'type' => 'string_textarea',
        'weight' => 11
    ));

    $fields['exception'] = BaseFieldDefinition::create('list_integer')
      ->setLabel('是否异常')
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => 11
      ))
      ->setSetting('allowed_values', array(1=>'正常',2=>'异常'))
      ->setDisplayConfigurable('form', True);

    return $fields;
  }
}
