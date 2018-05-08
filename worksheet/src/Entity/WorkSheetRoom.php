<?php

/**
 * @file
 * 机房事务类工单实体
 * \Drupal\worksheet\Entity\WorkSheetRoom;.
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
 *   id = "work_sheet_room",
 *   label = "机房事务工单",
 *   handlers = {
 *     "storage" = "Drupal\worksheet\WorkSheetRoomStorage",
 *     "form" = {
 *       "add" = "Drupal\worksheet\Form\WorkSheetRoomAddForm",
 *       "operation" = "Drupal\worksheet\Form\WorkSheetRoomOperateForm"
 *     }
 *   },
 *   base_table = "work_sheet_room",
 *   entity_keys = {
 *     "id" = "wid",
 *     "uuid" = "uuid",
 *     "label" = "code"
 *   }
 * )
 */
class WorkSheetRoom extends WorkSheetEntityBase {
  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    if($this->isNew()) {
      $account = \Drupal::currentUser();
      $this->set('uid', $account->id());
      $end_time = worksheet_room_end_time(REQUEST_TIME);
      $s = $end_time - REQUEST_TIME;
      $completed =  ceil($s/60);
      $this->set('completed', $completed);
      $this->set('begin_time', REQUEST_TIME);
      if(!$this->get('status')->value) {
        $roles = $account->getRoles();
        if(in_array('worksheet_operation',$roles)) {
          $this->set('status', 15);
          $this->set('handle_uid', $account->id());
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
      parent::deleteWorkSheetBase('work_sheet_room', $entity->id());
    }
  }
  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $type_options = \Drupal::service('worksheet.type')->getFoomType();
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
      ->setRequired(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 11
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['product_name'] = BaseFieldDefinition::create('string')
      ->setLabel('配置')
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 13
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['cabinet'] = BaseFieldDefinition::create('string')
      ->setLabel('机柜')
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 13
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['port'] = BaseFieldDefinition::create('string')
      ->setLabel('位置(U位)')
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 13
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['op_dept'] = BaseFieldDefinition::create('list_integer')
      ->setLabel('操作部门')
      ->setRequired(TRUE)
      ->setSetting('allowed_values_function', 'worksheet_options')
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => 14
      ))
      ->setDisplayConfigurable('form', True);

    $fields['handle_date'] = BaseFieldDefinition::create('list_integer')
      ->setLabel('处理日期')
      ->setSetting('allowed_values', array(0 => '当天处理', 1 => '下一个工作日处理'))
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => 14
      ))
      ->setDisplayConfigurable('form', True);

    $fields['job_content'] = BaseFieldDefinition::create('list_integer')
      ->setLabel('工作内容')
      ->setRequired(TRUE)
      ->setSetting('allowed_values_function', 'worksheet_options')
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => 14
      ))
      ->setDisplayConfigurable('form', True);

    $fields['job_hours'] = BaseFieldDefinition::create('integer')
      ->setLabel('机房工时(分钟)')
      ->setDisplayOptions('form', array(
        'type' => 'number',
        'weight' => 14
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
      
    $fields['handle_info'] = BaseFieldDefinition::create('string_long')
      ->setLabel('处理过程、结果')
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
        'weight' => 17
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['remember'] = BaseFieldDefinition::create('string_long')
      ->setLabel('备注')
      ->setDefaultValue('')
      ->setDisplayOptions('form', array(
        'type' => 'string_textarea',
        'weight' => 18
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['relation_code'] = BaseFieldDefinition::create('string')
      ->setLabel('关联工单编号')
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 20
      ))
      ->setDisplayConfigurable('form', TRUE);
    return $fields;
  }
}
