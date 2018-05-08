<?php

/**
 * @file
 * 故障类工单实体
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
 *   id = "work_sheet_fault",
 *   label = "故障工单",
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\worksheet\Form\WorkSheetFaultAddForm",
 *       "operation" = "Drupal\worksheet\Form\WorkSheetFaultOperateForm"
 *     }
 *   },
 *   base_table = "work_sheet_fault",
 *   entity_keys = {
 *     "id" = "wid",
 *     "uuid" = "uuid",
 *     "label" = "code"
 *   }
 * )
 */
class WorkSheetFault extends WorkSheetEntityBase {
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
      parent::deleteWorkSheetBase('work_sheet_fault', $entity->id());
    }
  }
  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $type_options = \Drupal::service('worksheet.type')->getFaultType();
    $fields['tid']->setSetting('allowed_values', $type_options);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID.'))
      ->setReadOnly(TRUE);

    $fields['wid'] = BaseFieldDefinition::create('integer')
      ->setLabel('工单id')
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['fault_time'] = BaseFieldDefinition::create('timestamp')
      ->setLabel('故障时间')
      ->setDisplayOptions('form', array(
        'type' => 'datetime_timestamp_null',
        'weight' => 11
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['report_time'] = BaseFieldDefinition::create('timestamp')
      ->setLabel('上报时间')
      ->setDisplayOptions('form', array(
        'type' => 'datetime_timestamp_null',
        'weight' => 12
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['report_user'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel('上报给谁')
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'username')
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 13
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['ip'] = BaseFieldDefinition::create('string')
      ->setLabel('故障IP')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 14
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['buss_recover_time'] = BaseFieldDefinition::create('timestamp')
      ->setLabel('业务恢复时间')
      ->setDisplayOptions('form', array(
        'type' => 'datetime_timestamp_null',
        'weight' => 16
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['fault_recover_time'] = BaseFieldDefinition::create('timestamp')
      ->setLabel('故障恢复时间')
      ->setDisplayOptions('form', array(
        'type' => 'datetime_timestamp_null',
        'weight' => 17
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['phenomenon'] = BaseFieldDefinition::create('string_long')
      ->setLabel('聊天记录')
      ->setDisplayOptions('form', array(
        'type' => 'string_textarea',
        'weight' => 20
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['handle_info'] = BaseFieldDefinition::create('string_long')
      ->setLabel('故障现象')
      ->setDisplayOptions('form', array(
        'type' => 'string_textarea',
        'weight' => 21
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['reason'] = BaseFieldDefinition::create('string_long')
      ->setLabel('故障原因、处理方法')
      ->setDisplayOptions('form', array(
        'type' => 'string_textarea',
        'weight' => 22
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['problem_types'] = BaseFieldDefinition::create('list_integer')
      ->setLabel('问题类型')
      ->setSetting('allowed_values_function', 'worksheet_options')
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => 23
      ))
      ->setDisplayConfigurable('form', True);

    $fields['problem_types_child'] = BaseFieldDefinition::create('integer')
      ->setLabel('问题类型')
      ->setDefaultValue(0)
      ->setSetting('unsigned', TRUE);

    $fields['problem_difficulty'] = BaseFieldDefinition::create('list_integer')
      ->setLabel('问题难度')
      ->setSetting('allowed_values_function', 'worksheet_options')
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => 24
      ))
      ->setDisplayConfigurable('form', True);
      

    $fields['if_question'] = BaseFieldDefinition::create('integer')
      ->setLabel('是否有问题')
      ->setDescription('工单是否有问题')
      ->setDefaultValue(0)
      ->setSetting('unsigned', TRUE);  
      
    $fields['if_right'] = BaseFieldDefinition::create('integer')
      ->setLabel('定位分类是否正确')
      ->setDescription('工单定位分类是否正确')
      ->setDefaultValue(0)
      ->setSetting('unsigned', TRUE);
      
    $fields['if_deal'] = BaseFieldDefinition::create('integer')
      ->setLabel('是否正确处理')
      ->setDescription('工单是否正确')
      ->setDefaultValue(0)
      ->setSetting('unsigned', TRUE);
      
    $fields['if_quality'] = BaseFieldDefinition::create('integer')
      ->setLabel('是否是优质工单')
      ->setDescription('工单是否是优质工单')
      ->setDefaultValue(0)
      ->setSetting('unsigned', TRUE);

    $fields['comment_note'] = BaseFieldDefinition::create('string_long')
      ->setLabel('评论备注')
      ->setDisplayOptions('form', array(
        'type' => 'string_textarea',
        'weight' => 22
      ));
    $fields['performance'] = BaseFieldDefinition::create('integer')
      ->setLabel('绩效加分')
      ->setDescription('绩效加分');

    $fields['isno_comment'] = BaseFieldDefinition::create('integer')
      ->setLabel('是否评论')
      ->setDescription('是否评论')
      ->setDefaultValue(0)
      ->setSetting('unsigned', TRUE);
      
    $fields['comment_uid'] = BaseFieldDefinition::create('integer')
      ->setLabel('评论对象')
      ->setDescription('评论对象')
      ->setSetting('unsigned', TRUE);

    return $fields;
  }
}
