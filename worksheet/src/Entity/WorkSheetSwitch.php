<?php

/**
 * @file
 * 开关机工单实体
 * \Drupal\worksheet\Entity\WorkSheetSwitch;.
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
 *   id = "work_sheet_switch",
 *   label = "开关机类工单",
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\worksheet\Form\WorkSheetSwitchAddForm",
 *       "operation" = "Drupal\worksheet\Form\WorkSheetSwitchOperateForm"
 *     }
 *   },
 *   base_table = "work_sheet_switch",
 *   entity_keys = {
 *     "id" = "wid",
 *     "uuid" = "uuid",
 *     "label" = "code"
 *   }
 * )
 */
class WorkSheetSwitch extends WorkSheetEntityBase {
  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    if($this->isNew()) {
      $account = \Drupal::currentUser();
      $this->set('uid', $account->id());
      $completed = \Drupal::service('worksheet.type')->getCompleteTime($this->get('tid')->value);
      $this->set('completed', $completed);
      if(!$this->get('status')->value) {
        $roles = $account->getRoles();
        if(in_array('worksheet_operation',$roles)) {
          $this->set('status', 15);
          $this->set('handle_uid', $account->id());
          $this->set('begin_time', REQUEST_TIME);
        } else {
          $this->set('status', 1);
        }
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
      parent::deleteWorkSheetBase('work_sheet_switch', $entity->id());
    }
  }
  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $type_options = \Drupal::service('worksheet.type')->getSwitchType();
    $fields['tid']->setSetting('allowed_values', $type_options);
    
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID.'))
      ->setReadOnly(TRUE);
    
    $fields['wid'] = BaseFieldDefinition::create('integer')
      ->setLabel('工单id')
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);
      
    $fields['manage_ip'] = BaseFieldDefinition::create('string')
      ->setLabel('管理IP')
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 11
      ))
      ->setDisplayConfigurable('form', TRUE);
      
    $fields['handle_info'] = BaseFieldDefinition::create('string_long')
      ->setLabel('处理过程、结果')
      ->setDefaultValue('')
      ->setDisplayOptions('form', array(
        'type' => 'string_textarea',
        'weight' => 18
      ))
      ->setDisplayConfigurable('form', TRUE);
    return $fields;
  }
}
