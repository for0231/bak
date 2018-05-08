<?php

/**
 * @file
 * IP类工单实体
 * \Drupal\worksheet\Entity\WorkSheetIp;.
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
 *   id = "work_sheet_ip",
 *   label = "IP类工单",
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\worksheet\Form\WorkSheetIpAddForm",
 *       "operation" = "Drupal\worksheet\Form\WorkSheetIpOperateForm"
 *     }
 *   },
 *   base_table = "work_sheet_ip",
 *   entity_keys = {
 *     "id" = "wid",
 *     "uuid" = "uuid",
 *     "label" = "code"
 *   }
 * )
 */
class WorkSheetIp extends WorkSheetEntityBase {
  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    //确定分类
    if(!$this->get('tid')->value) {
      $add_ip = $this->get('add_ip')->value;
      $rm_ip = $this->get('rm_ip')->value;
      $broadband = $this->get('broadband')->value;
      $tid = 130;
      if(!empty($rm_ip)) {
        $tid = 131;
      }
      if(!empty($add_ip) && !empty($rm_ip)) {
        $tid = 132;
      }
      if(empty($add_ip) && empty($rm_ip) && !empty($broadband)) {
        $tid = 133;
      }
      $this->set('tid', $tid);
    }
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
      parent::deleteWorkSheetBase('work_sheet_ip', $entity->id());
    }
  }
  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $type_options = \Drupal::service('worksheet.type')->getIPType();
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
    
    $fields['property'] = BaseFieldDefinition::create('list_integer')
      ->setLabel('属性')
      ->setSetting('allowed_values_function', 'worksheet_options')
      ->setDefaultValue(1)
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => 12
      ))
      ->setDisplayConfigurable('form', True);
    
    $fields['add_ip'] = BaseFieldDefinition::create('string_long')
      ->setLabel('增加IP')
      ->setDisplayOptions('form', array(
        'type' => 'string_textarea',
        'weight' => 13
      ))
      ->setDisplayConfigurable('form', TRUE);
      
    $fields['rm_ip'] = BaseFieldDefinition::create('string_long')
      ->setLabel('停用IP')
      ->setDisplayOptions('form', array(
        'type' => 'string_textarea',
        'weight' => 14
      ))
      ->setDisplayConfigurable('form', TRUE);
    $fields['broadband'] = BaseFieldDefinition::create('string')
      ->setLabel('带宽')
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 15,
      ))
      ->setDisplayConfigurable('form', TRUE);
    
    $fields['requirement'] = BaseFieldDefinition::create('string_long')
      ->setLabel('需求')
      ->setDefaultValue('')
      ->setDisplayOptions('form', array(
        'type' => 'string_textarea',
        'weight' => 18
      ))
      ->setDisplayConfigurable('form', TRUE);
      
    $fields['handle_info'] = BaseFieldDefinition::create('string_long')
      ->setLabel('处理过程、结果')
      ->setDefaultValue('')
      ->setDisplayOptions('form', array(
        'type' => 'string_textarea',
        'weight' => 20
      ))
      ->setDisplayConfigurable('form', TRUE);
    return $fields;
  }
}
