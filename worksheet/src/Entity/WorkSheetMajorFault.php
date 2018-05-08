<?php

/**
 * @file
 * 重大故障类工单实体
 * \Drupal\worksheet\Entity\WorkSheetMajorFault;.
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
 *   id = "work_sheet_major_fault",
 *   label = "故障工单",
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\worksheet\Form\WorkSheetMajorFaultAddForm",
 *       "operation" = "Drupal\worksheet\Form\WorkSheetMajorFaultOperateForm"
 *     }
 *   },
 *   base_table = "work_sheet_major_fault",
 *   entity_keys = {
 *     "id" = "wid",
 *     "uuid" = "uuid",
 *     "label" = "code"
 *   }
 * )
 */
class WorkSheetMajorFault extends WorkSheetEntityBase {
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
    //保存公共实体的信息
    $this->postSaveCommon($storage, $update);
  }
  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);
    foreach($entities as $entity) {
      parent::deleteWorkSheetBase('work_sheet_major_fault', $entity->id());
    }
  }
  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $type_options = \Drupal::service('worksheet.type')->getMajorFaultType();
    $fields['tid']->setSetting('allowed_values', $type_options);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID.'))
      ->setReadOnly(TRUE);

    $fields['wid'] = BaseFieldDefinition::create('integer')
      ->setLabel('工单id')
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['room'] = BaseFieldDefinition::create('list_integer')
      ->setLabel('机房')
      ->setSetting('allowed_values_function', 'worksheet_options')
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => 12
      ))
      ->setDisplayConfigurable('form', True);

    $fields['affect_direction'] = BaseFieldDefinition::create('list_integer')
      ->setLabel('影响方向')
      ->setSetting('allowed_values_function', 'worksheet_options')
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => 12
      ))
      ->setDisplayConfigurable('form', True);

    $fields['affect_range'] = BaseFieldDefinition::create('list_integer')
      ->setLabel('影响范围')
      ->setSetting('allowed_values_function', 'worksheet_options')
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => 12
      ))
      ->setDisplayConfigurable('form', True);

    $fields['affect_level'] = BaseFieldDefinition::create('list_integer')
      ->setLabel('影响程度')
      ->setSetting('allowed_values_function', 'worksheet_options')
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => 12
      ))
      ->setDisplayConfigurable('form', True);

    $fields['fault_location'] = BaseFieldDefinition::create('list_integer')
      ->setLabel('故障定位')
      ->setSetting('allowed_values_function', 'worksheet_options')
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => 12
      ))
      ->setDisplayConfigurable('form', True);

    $fields['time_consuming'] = BaseFieldDefinition::create('string')
      ->setLabel('值班人员发现耗时')
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 12
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['affect_time1'] = BaseFieldDefinition::create('string')
      ->setLabel('业务影响时间')
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 16,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['affect_time2'] = BaseFieldDefinition::create('string')
      ->setLabel('业务影响时间2')
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 16,
      ))
      ->setDisplayConfigurable('form', TRUE);
      
    $fields['fault_time'] = BaseFieldDefinition::create('timestamp')
      ->setLabel('故障时间')
      ->setDisplayOptions('form', array(
        'type' => 'datetime_timestamp_null',
        'weight' => 11
      ))
      ->setDisplayConfigurable('form', TRUE);
   

    $fields['report_time'] = BaseFieldDefinition::create('timestamp')
      ->setLabel('值班人员上报时间')
      ->setDisplayOptions('form', array(
        'type' => 'datetime_timestamp_null',
        'weight' => 12
      ))
      ->setDisplayConfigurable('form', TRUE);
      
    $fields['sy_report_time'] = BaseFieldDefinition::create('timestamp')
      ->setLabel('系统上报时间')
      ->setDisplayOptions('form', array(
        'type' => 'datetime_timestamp_null',
        'weight' => 12
      ))
      ->setDisplayConfigurable('form', TRUE);
      
    $fields['alarm_action'] = BaseFieldDefinition::create('string')
      ->setLabel('系统的告警方式')
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 16,
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
      
    $fields['reason'] = BaseFieldDefinition::create('string_long')
      ->setLabel('故障原因')
      ->setDisplayOptions('form', array(
        'type' => 'string_textarea',
        'weight' => 22
      ))
      ->setDisplayConfigurable('form', TRUE);
      
    $fields['recover_method'] = BaseFieldDefinition::create('string_long')
      ->setLabel('恢复业务方法')
      ->setDisplayOptions('form', array(
        'type' => 'string_textarea',
        'weight' => 22
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['deal_method'] = BaseFieldDefinition::create('string_long')
      ->setLabel('故障处理方法')
      ->setDisplayOptions('form', array(
        'type' => 'string_textarea',
        'weight' => 22
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['note'] = BaseFieldDefinition::create('string_long')
      ->setLabel('备注')
      ->setDisplayOptions('form', array(
        'type' => 'string_textarea',
        'weight' => 22
      ))
      ->setDisplayConfigurable('form', TRUE);
      
    $fields['sytime_consuming'] = BaseFieldDefinition::create('string')
    ->setLabel('监控系统发现耗时')
    ->setDisplayOptions('form', array(
      'type' => 'string_textfield',
      'weight' => 12
    ))
    ->setDisplayConfigurable('form', TRUE);
    
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
      ->setLabel('评论备注');
      
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
