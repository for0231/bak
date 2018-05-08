<?php
/*
 * @file
 * \Drupal\idc\Entity\Room
 */

namespace Drupal\idc\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\idc\IdcEntityInterface;

/**
 * defines the IDC Room entity class
 *
 * @ContentEntityType(
 *   id = "cabinet_server",
 *   label = @Translation("Cabinet server"),
 *   handlers = {
 *     "form" = {
 *       "default" = "Drupal\idc\Form\CabinetServerForm",
 *       "group" = "Drupal\idc\Form\CabinetServerGroupForm",
 *       "groupserver" = "Drupal\idc\Form\CabinetGroupServerForm",
 *       "delete" = "Drupal\idc\Form\CabinetServerDeleteForm",
 *     }
 *   },
 *   base_table = "idc_cabinet_server",
 *   data_table = "idc_cabinet_server_field_data",
 *   revision_table = "idc_cabinet_server_revision",
 *   revision_data_table = "idc_cabinet_server_field_revision",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "sid",
 *     "revision" = "vid",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "label" = "ipm_id"
 *   }
 * )
 *
 */
class CabinetServer extends ContentEntityBase implements IdcEntityInterface {

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    if($this->isNew()) {
      $this->set('uid', \Drupal::currentUser()->id());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    $serverService = \Drupal::service('idc.cabinet.server');
    if($update) {
      if(isset($this->move_cabinet_before)) {
        $serverService->updateCabinetSeatInfo($this, 'move');
      }
    } else {
      if($this->getObjectId('ipm_id') == 0) {
        $serverService->updateCabinetSeatInfo($this, 'add');
      } else if ($this->getObjectId('ipm_id') > 0 && $this->getSimpleValue('parent_id') > 0) {
        $serverService->updateEquipmentStatus($this, 'add');
      } else {
        $serverService->updateCabinetSeatInfo($this, 'add');
        $serverService->updateEquipmentStatus($this, 'add');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);
    $serverService = \Drupal::service('idc.cabinet.server');
    foreach($entities as $entity) {
      if($entity->getObjectId('ipm_id') == 0) {
        $serverService->updateCabinetSeatInfo($entity, 'delete');
      } else if ($entity->getObjectId('ipm_id') > 0 && $entity->getSimpleValue('parent_id') > 0) {
        $serverService->updateEquipmentStatus($entity, 'delete');
      } else {
        $serverService->updateCabinetSeatInfo($entity, 'delete');
        $serverService->updateEquipmentStatus($entity, 'delete');
      }
    }
  }

  /*
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields['sid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the cabinet entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID.'))
      ->setReadOnly(TRUE);

    $fields['vid'] = BaseFieldDefinition::create('integer')
      ->setDescription(t('The revision ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The cabinet language code.'))
      ->setRevisionable(TRUE);

    $fields['cabinet_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Cabinet'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'room_cabinet');

    $fields['ipm_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Manage ip'))
      ->setDescription(t('The cabinet server management IP.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'ipm')
      ->setSetting('handler', 'idc')
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 1
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['server_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Server'))
      ->setDescription(t('The management ip corresponding server .'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'server')
      ->setSetting('handler', 'idc')
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete_server',
        'weight' => 5
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['switch_p'] = BaseFieldDefinition::create('multi_part')
      ->setLabel(t('Switch P'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'ips')
      ->setDisplayOptions('form', array(
        'type' => 'multi_part_default',
        'weight' => 10
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['switch_m'] = BaseFieldDefinition::create('multi_part')
      ->setLabel(t('Switch M'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'ips')
      ->setDisplayOptions('form', array(
        'type' => 'multi_part_default',
        'weight' => 15
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['start_seat'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Start seat'))
      ->setDescription(t('The location of the server'))
      ->setSetting('unsigned', TRUE)
      ->setRevisionable(TRUE);

    $fields['seat_size'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Model'))
      ->setDescription(t('Server occupied seat digit.'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'radios_number',
        'weight' => 20,
        'settings' => array(
          'options' => array(1 => '1U', 2 => '2U', 3 => '3U', '4' => '4U')
        )
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['group_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Group name'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
       'type' => 'string',
       'weight' => 1
     ))
     ->setDisplayConfigurable('form', TRUE);

    $fields['parent_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Parent'))
      ->setRevisionable(TRUE)
      ->setDescription('所属父级节点');

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Create user'))
      ->setDescription(t('The user ID of the author.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user');

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('Create time'))
      ->setRevisionable(TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('Change time'))
      ->setRevisionable(TRUE);

    return $fields;
  }

  /**
   * 获取简单的实体字段的值
   */
  public function getSimpleValue($name) {
    return $this->get($name)->value;
  }

  /**
   * 获取对象字段的实体
   */
  public function getObject($name) {
    return $this->get($name)->entity;
  }

  /**
   * 获取对象字段的实体Id
   */
  public function getObjectId($name) {
    return $this->get($name)->target_id;
  }

  /**
   * 获取交换p的端口
   */
  public function switch_p_port() {
    return $this->get('switch_p')->value;
  }

  /**
   * 获取交换m的端口
   */
  public function switch_m_port() {
    return $this->get('switch_m')->value;
  }

}
