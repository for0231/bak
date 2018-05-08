<?php

/**
 * @file
 * Contains file
 * \Drupal\worksheet\WorkSheetEntityBase.
 */

namespace Drupal\worksheet;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityStorageInterface;

class WorkSheetEntityBase extends ContentEntityBase {
  /**
   * 五种类型工单保存时共用的方法
   */
  protected function postSaveCommon(EntityStorageInterface $storage, $update = TRUE) {
    //修改公共表
    $this->saveWorkSheetBase($this, $update);
    //保存处理记录
    if(isset($this->handle_record_info)) {
      $handle_info =  $this->handle_record_info;
      $op_id = $handle_info['operation_id'];
      entity_create('work_sheet_handle', $handle_info + array(
        'operation' => operationOptions()[$op_id],
        'wid' => $this->id(),
        'entity_name' => $this->getEntityTypeId(),
        'work_sheet' => serialize($this)
      ))->save();
      unset($this->handle_record_info);
    }
    //保存统计信息
    if(isset($this->statistic_record)) {
      $statistics = $this->statistic_record;
      foreach($statistics as $statistic) {
        if(!isset($statistic['user_dept'])) {
          $user = entity_load('user', $statistic['uid']);
          $roles = $user->getRoles();
          if(in_array('worksheet_operation',$roles)) {
            $statistic['user_dept'] = 'worksheet_operation';
          } else {
            $statistic['user_dept'] = 'worksheet_business';
          }
        }
        \Drupal::service('worksheet.statistic')->add($statistic + array(
          'created' => REQUEST_TIME,
          'type_id' => $this->get('tid')->value,
          'wid' => $this->id(),
          'entity_name' => $this->getEntityTypeId()
        ));
      }
      unset($this->statistic_record);
    }
    //质量异常克隆
    if(isset($this->abnormal_quality_clone)) {
      unset($this->abnormal_quality_clone);
      $uid = \Drupal::currentUser()->id();
      $duplicate = $this->createDuplicate();
      $duplicate->set('status', 25);
      $duplicate->set('uid', $uid);
      $duplicate->set('handle_uid', 0);
      $duplicate->set('last_uid', 0);
      $duplicate->set('completed', 0);
      $duplicate->set('begin_time', 0);
      $duplicate->set('end_time', 0);
      $duplicate->set('com_time', 0);
      $duplicate->set('abnormal_exist', 0);
      $duplicate->handle_record_info = array(
        'uid' => $uid,
        'time' => REQUEST_TIME,
        'operation_id' => 1,
        'is_abnormal' => 0
      );
      $duplicate->statistic_record = array(
        array('uid' => $uid, 'event' => 1)
      );
      $duplicate->save();
    }
  }
  /**
   * 保存工单基础实体数据
   */
  protected function saveWorkSheetBase($entity, $update) {
    $entity_name = $entity->getEntityTypeId();
    $wid = $entity->id();
    $ip = '';
    if($entity_name == 'work_sheet_fault' || $entity_name == 'work_sheet_cycle') {
      $ip = $entity->get('ip')->value;
    } else if ($entity_name == 'work_sheet_logistics'){
      $ip = '快递物流';
    }else if ($entity_name == 'work_sheet_major_fault'){
      //判断为重大故障工单,ip为重大故障
      $ip = '重大故障';
    } else {
      $ip = $entity->get('manage_ip')->value;
    }
    if($update) {
      $entitys =  entity_load_multiple_by_properties('work_sheet_base', array(
        'wid' => $wid,
        'entity_name' => $entity_name
      ));
      if(count($entitys)) {
        $sheet_entity = reset($entitys);
        $sheet_entity->set('handle_uid', $entity->get('handle_uid')->target_id);
        $sheet_entity->set('last_uid', $entity->get('last_uid')->target_id);
        $sheet_entity->set('begin_time', $entity->get('begin_time')->value);
        $sheet_entity->set('end_time', $entity->get('end_time')->value);
        $sheet_entity->set('com_time', $entity->get('com_time')->value);
        $sheet_entity->set('status', $entity->get('status')->value);
        $sheet_entity->set('tid', $entity->get('tid')->value);
        $sheet_entity->set('client', $entity->get('client')->value);
        $sheet_entity->set('contacts', $entity->get('contacts')->value);
        $sheet_entity->set('ip', $ip);
        $sheet_entity->set('abnormal_exist', $entity->get('abnormal_exist')->value);
        $sheet_entity->save();
      }
    } else {
      entity_create('work_sheet_base', array(
        'uuid' => $entity->get('uuid')->value,
        'code' => $entity->get('code')->value,
        'tid' => $entity->get('tid')->value,
        'uid' => $entity->get('uid')->target_id,
        'client' => $entity->get('client')->value,
        'contacts' => $entity->get('contacts')->value,
        'handle_uid' => $entity->get('handle_uid')->target_id,
        'completed' => $entity->get('completed')->value,
        'begin_time' => $entity->get('begin_time')->value,
        'end_time' => $entity->get('end_time')->value,
        'status' => $entity->get('status')->value,
        'ip' => $ip,
        'wid' => $wid,
        'entity_name' => $entity_name
      ))->save();
      //记录未完成工单状态    
      $wid =  $wid;
      $status = $entity->get('status')->value;
      $type = substr($entity_name,11,2);
      /*
      工单编号：wid
      工单类型:entity_name
      开始时间：btime
      结束时间:etime
      开始状态：bstatus
      结束状态：estatus
      组合工单编号：group_wid
      */
      $list = array(
        'wid'=>$wid,
        'bstatus'=>$entity->get('status')->value,
        'btime'=>time(),
        'group_wid'=>$type.$wid,
        'entity_name'=>$entity_name
      );
      $rows = \Drupal::service('worksheet.dbservice')->add_status($list);
    }
  }
  /**
   * 删除工单
   */
  protected static function deleteWorkSheetBase($entity_type, $wid) {
    $bases = entity_load_multiple_by_properties('work_sheet_base', array('entity_name' => $entity_type, 'wid' => $wid));
    \Drupal::entityManager()->getStorage('work_sheet_base')->delete($bases);
    $statistic_service = \Drupal::service('worksheet.statistic');
    $statistic_service->delete($wid, $entity_type);
    //删除未完成工单状态
    $statistic_service->delete_status($wid, $entity_type);
    
    $handles = entity_load_multiple_by_properties('work_sheet_handle', array('entity_name' => $entity_type, 'wid' => $wid));
    \Drupal::entityManager()->getStorage('work_sheet_handle')->delete($handles);
  }
  
  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['code'] = BaseFieldDefinition::create('string')
      ->setLabel('工单编号')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 1
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['tid'] = BaseFieldDefinition::create('list_integer')
      ->setLabel('所属分类')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => 5
      ))
      ->setDisplayConfigurable('form', True);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel('创建时间')
      ->setDescription('创建时间');

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel('建单人')
      ->setDescription('建单人')
      ->setDefaultValue(0)
      ->setSetting('target_type', 'user');

    $fields['client'] = BaseFieldDefinition::create('string')
      ->setLabel('公司名称')
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 10
      ))
      ->setDisplayConfigurable('form', TRUE);
      
    $fields['contacts'] = BaseFieldDefinition::create('string')
      ->setLabel('联系人')
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 11
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['handle_uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel('当前处理人')
      ->setDescription('当前处理人')
      ->setDefaultValue(0)
      ->setSetting('target_type', 'user');

    $fields['last_uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel('交接人')
      ->setDescription('上次处理人')
      ->setDefaultValue(0)
      ->setSetting('target_type', 'user');

    $fields['completed'] = BaseFieldDefinition::create('integer')
      ->setLabel('完成所需求时间')
      ->setDescription('计划完成所需求时间(分)')
      ->setDefaultValue(0)
      ->setSetting('unsigned', TRUE);

    $fields['begin_time'] = BaseFieldDefinition::create('integer')
      ->setLabel('计时开始时间')
      ->setDescription('计时开始时间')
      ->setDefaultValue(0)
      ->setSetting('unsigned', TRUE);

    $fields['end_time'] = BaseFieldDefinition::create('integer')
      ->setLabel('交业务时间')
      ->setDescription('交业务时间')
      ->setDefaultValue(0)
      ->setSetting('unsigned', TRUE);
      
    $fields['com_time'] = BaseFieldDefinition::create('integer')
      ->setLabel('完成时间')
      ->setDescription('工单完成时间')
      ->setDefaultValue(0)
      ->setSetting('unsigned', TRUE);

    $fields['status'] = BaseFieldDefinition::create('integer')
      ->setLabel('状态')
      ->setDescription('状态')
      ->setSetting('unsigned', TRUE);

    $fields['abnormal_exist'] = BaseFieldDefinition::create('integer')
      ->setLabel('是否存在异常')
      ->setDefaultValue(0)
      ->setSetting('unsigned', TRUE);

    return $fields;
  }

  /**
   * 得到创建用户
   */
  public function createUser() {
    $user = $this->get('uid')->entity;
    if($user->hasField('real_name')) {
      if(!empty($user->get('real_name')->value)){
        return $user->get('real_name')->value;
      }
    }
    return $user->label();
  }
  /**
   * 得到当前处理人
   */
  public function handleUser() {
    if($this->get('handle_uid')->target_id) {
      $user = $this->get('handle_uid')->entity;
      if($user->hasField('real_name')) {
        return $user->get('real_name')->value;
      }
      return $user->label();
    }
    return '';
  }
  /**
   *得到上次处理人
   */
  public function lastUser() {
    if($this->get('last_uid')->target_id) {
      $user = $this->get('last_uid')->entity;
      if($user->hasField('real_name')) {
        return $user->get('real_name')->value;
      }
      return $user->label();
    }
    return '';
  }
}
