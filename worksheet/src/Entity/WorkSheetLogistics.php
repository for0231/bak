<?php

/**
 * @file
 * 物流类工单实体
 * \Drupal\worksheet\Entity\WorkSheetLogistics.
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
 *   id = "work_sheet_logistics",
 *   label = "物流工单",
 *   handlers = {
 *     "storage" = "Drupal\worksheet\WorkSheetLogisticsStorage",
 *     "form" = {
 *       "add" = "Drupal\worksheet\Form\WorkSheetLogisticsAddForm",
 *       "operation" = "Drupal\worksheet\Form\WorkSheetLogisticsOperateForm"
 *     }
 *   },
 *   base_table = "work_sheet_logistics",
 *   entity_keys = {
 *     "id" = "wid",
 *     "uuid" = "uuid",
 *     "label" = "code"
 *   }
 * )
 */
class WorkSheetLogistics extends WorkSheetEntityBase {
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
      parent::deleteWorkSheetBase('work_sheet_Logistics', $entity->id());
    }
  }
  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $type_options = \Drupal::service('worksheet.type')->getLogisticsType();
    $fields['tid']->setSetting('allowed_values', $type_options);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID.'))
      ->setReadOnly(TRUE);

    $fields['wid'] = BaseFieldDefinition::create('integer')
      ->setLabel('工单id')
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['order_code'] = BaseFieldDefinition::create('string')
      ->setLabel('物流单号')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 11
      ))
      ->setDisplayConfigurable('form', TRUE);
    $fields['logistics_company'] = BaseFieldDefinition::create('string')
      ->setLabel('物流公司')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 12
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['send_time'] = BaseFieldDefinition::create('timestamp')
      ->setLabel('发件时间')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'datetime_timestamp',
        'weight' => 13
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['estimate_time'] = BaseFieldDefinition::create('timestamp')
      ->setLabel('预计到达时间')
      ->setDisplayOptions('form', array(
        'type' => 'datetime_timestamp_null',
        'weight' => 14
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['op_dept'] = BaseFieldDefinition::create('list_integer')
      ->setLabel('操作部门')
      ->setSetting('allowed_values_function', 'worksheet_options')
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => 15
      ))
      ->setDisplayConfigurable('form', True);

    $fields['requirement'] = BaseFieldDefinition::create('string_long')
      ->setLabel('需求')
      ->setDefaultValue('')
      ->setDisplayOptions('form', array(
        'type' => 'string_textarea',
        'weight' => 17
      ))
      ->setDisplayConfigurable('form', TRUE);
      
    $fields['next_step'] = BaseFieldDefinition::create('string_long')
      ->setLabel('下一步操作')
      ->setDefaultValue('')
      ->setDisplayOptions('form', array(
        'type' => 'string_textarea',
        'weight' => 18
      ))
      ->setDisplayConfigurable('form', TRUE);
    $fields['remember'] = BaseFieldDefinition::create('string_long')
      ->setLabel('备注')
      ->setDefaultValue('')
      ->setDisplayOptions('form', array(
        'type' => 'string_textarea',
        'weight' => 19
      ))
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }
}
