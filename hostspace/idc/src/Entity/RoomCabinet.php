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
 *   id = "room_cabinet",
 *   label = @Translation("Cabinet"),
 *   handlers = {
 *     "form" = {
 *       "default" = "Drupal\idc\Form\RoomCabinetForm",
 *       "delete" = "Drupal\idc\Form\RoomCabinetDeleteForm"
 *     }
 *   },
 *   base_table = "idc_cabinet",
 *   data_table = "idc_cabinet_field_data",
 *   revision_table = "idc_cabinet_revision",
 *   revision_data_table = "idc_cabinet_field_revision",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "cid",
 *     "revision" = "vid",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "label" = "code"
 *   },
 *   links = {
 *     "edit-form" = "/admin/idc/cabinet/{room_cabinet}/edit",
 *     "delete-form" = "/admin/idc/cabinet/{room_cabinet}/delete"
 *   }
 * )
 *
 */
class RoomCabinet extends ContentEntityBase implements IdcEntityInterface  {

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    if($this->isNew()) {
      $this->set('uid', \Drupal::currentUser()->id());
      $this->set('unused_seat', $this->getSimpleValue('seat'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    if(!$update) {
      \Drupal::service('idc.cabinet')->updateRoomCabinetInfo($this, 'add');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);
    foreach($entities as $entity) {
       \Drupal::service('idc.cabinet')->updateRoomCabinetInfo($entity, 'delete');
    }
  }

  /*
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields['cid'] = BaseFieldDefinition::create('integer')
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

   $fields['rid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Room'))
      ->setDescription(t('Cabinet belongs to the room'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'room')
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => 1
      ))
      ->setDisplayConfigurable('form', TRUE);

   $fields['code'] = BaseFieldDefinition::create('string')
     ->setLabel(t('Cabinet code'))
     ->setRequired(TRUE)
     ->setRevisionable(TRUE)
     ->setDisplayOptions('form', array(
       'type' => 'string',
       'weight' => 5
     ))
     ->setDisplayConfigurable('form', TRUE);

    $fields['seat'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Seat number'))
      ->setDescription(t('The total number of the cabinet'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
       'type' => 'number',
       'weight' => 10
     ))
     ->setDisplayConfigurable('form', TRUE);

    $fields['used_seat'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Usen seat'))
      ->setDescription(t('Has been the use of seat'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(0);

    $fields['unused_seat'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Unused seats'))
      ->setDescription(t('Unused seats'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(0);

    $fields['switch_seat'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Switch seat'))
      ->setDescription(t('The use of seat digit switch'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(0);

    $fields['fault_seat'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Fault seat'))
      ->setDescription(t('The use of seat digit fault'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(0);

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
}
