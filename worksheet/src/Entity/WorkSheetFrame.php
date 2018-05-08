<?php

/**
 * @file
 * 上下架实体
 * \Drupal\worksheet\Entity\WorkSheetFrame;.
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
 *   id = "work_sheet_frame",
 *   label = "上下架工单",
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\worksheet\Form\WorkSheetFrameAddForm",
 *       "operation" = "Drupal\worksheet\Form\WorkSheetFrameOperateForm"
 *     }
 *   },
 *   base_table = "work_sheet_frame",
 *   entity_keys = {
 *     "id" = "wid",
 *     "uuid" = "uuid",
 *     "label" = "code"
 *   }
 * )
 */
class WorkSheetFrame extends WorkSheetEntityBase {
  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    if($this->isNew()) {
      $tid = $this->get('tid')->value;
      $account = \Drupal::currentUser();
      $this->set('uid', $account->id());
      if($tid == 111) {
        $end_time = worksheet_room_end_time(REQUEST_TIME);
        $s = $end_time - REQUEST_TIME;
        $completed =  ceil($s/60);
        $this->set('completed', $completed);
      }
      else {
        $completed = \Drupal::service('worksheet.type')->getCompleteTime($tid);
        $this->set('completed', $completed);
      }
      if(!$this->get('status')->value) {
        $roles = $account->getRoles();
        if(in_array('worksheet_operation',$roles)) {
          $this->set('status', 15);
          $this->set('handle_uid', $account->id());
          $this->set('begin_time', REQUEST_TIME);
        } else {
          $this->set('status', 1);
        }
        if($tid == 100 || $tid == 110 || $tid ==120) {
          $this->set('handle_info', "子网掩码：255.255.255.0\r\n网    关：\r\n系统账号：\r\n系统密码：\r\n以上内容必须填");
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
      parent::deleteWorkSheetBase('work_sheet_frame', $entity->id());
    }
  }
  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $type_options = \Drupal::service('worksheet.type')->getFrameType();
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
      
    $fields['business_ip'] = BaseFieldDefinition::create('string_long')
      ->setLabel('业务IP')
      ->setDisplayOptions('form', array(
        'type' => 'string_textarea',
        'weight' => 12
      ))
      ->setDisplayConfigurable('form', TRUE);
      
    $fields['product_name'] = BaseFieldDefinition::create('string')
      ->setLabel('配置')
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 13
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['ip_class'] = BaseFieldDefinition::create('list_integer')
      ->setLabel('IP类型')
      ->setSetting('allowed_values_function', 'worksheet_options')
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => 14
      ))
      ->setDisplayConfigurable('form', True);

    $fields['system'] = BaseFieldDefinition::create('list_integer')
      ->setLabel('系统')
      ->setSetting('allowed_values_function', 'worksheet_options')
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => 15
      ))
      ->setDisplayConfigurable('form', True);
      
    $fields['broadband'] = BaseFieldDefinition::create('string')
      ->setLabel('带宽')
      ->setDefaultValue('30M')
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 16,
      ))
      ->setDisplayConfigurable('form', TRUE);

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
        'weight' => 18
      ))
      ->setDisplayConfigurable('form', TRUE);
      
    $fields['problem_difficulty'] = BaseFieldDefinition::create('list_integer')
      ->setLabel('问题难度')
      ->setSetting('allowed_values_function', 'worksheet_options')
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => 20
      ))
      ->setDisplayConfigurable('form', True);

    $fields['add_card'] = BaseFieldDefinition::create('boolean')
      ->setLabel('已增加管理卡')
      ->setDisplayOptions('form', array(
        'type' => 'boolean_checkbox',
        'settings' => array('display_label' => 'true'),
        'weight' => 21
      ))
      ->setDisplayConfigurable('form', True);

    $fields['add_arp'] = BaseFieldDefinition::create('boolean')
      ->setLabel('已绑定ARP')
      ->setDisplayOptions('form', array(
        'type' => 'boolean_checkbox',
        'settings' => array('display_label' => 'true'),
        'weight' => 22
      ))
      ->setDisplayConfigurable('form', True);
    return $fields;
  }
}
