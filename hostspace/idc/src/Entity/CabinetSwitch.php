<?php
/*
 * @file
 * \Drupal\idc\Entity\CabinetSwitch
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
 *   id = "cabinet_switch",
 *   label = @Translation("Cabinet switch"),
 *   handlers = {
 *     "form" = {
 *       "default" = "Drupal\idc\Form\CabinetSwitchForm",
 *       "delete" = "Drupal\idc\Form\CabinetSwitchDeleteForm"
 *     }
 *   },
 *   base_table = "idc_cabinet_switch",
 *   data_table = "idc_cabinet_switch_field_data",
 *   revision_table = "idc_cabinet_switch_revision",
 *   revision_data_table = "idc_cabinet_switch_field_revision",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "sid",
 *     "revision" = "vid",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "label" = "ips_id"
 *   }
 * )
 *
 */
class CabinetSwitch extends ContentEntityBase implements IdcEntityInterface {
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
    if(!$update) {
      $switchService = \Drupal::service('idc.cabinet.switch');
      $switchService->updateCabinetSeatInfo($this, 'add');
      $switchService->updateEquipmentStatus($this, 'add');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);
    foreach($entities as $entity) {
      $switchService = \Drupal::service('idc.cabinet.switch');
      $switchService->updateCabinetSeatInfo($entity, 'delete');
      $switchService->updateEquipmentStatus($entity, 'delete');
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

    $fields['ips_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Switch ip'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'ips')
      ->setSetting('handler', 'idc')
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 1
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['start_seat'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Start seat'))
      ->setDescription(t('The location of the server'))
      ->setSetting('unsigned', TRUE)
      ->setRevisionable(TRUE);

    $fields['seat_size'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Model seat'))
      ->setDescription(t('Switch occupied seat digit.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'radios_number',
        'weight' => 10,
        'settings' => array(
          'options' => array(1 => '1U', 2 => '2U', 3 => '3U', '4' => '4U')
        )
      ))
      ->setDisplayConfigurable('form', TRUE);

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
