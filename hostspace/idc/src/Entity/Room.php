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
 *   id = "room",
 *   label = @Translation("IDC Room"),
 *   handlers = {
 *     "access" = "Drupal\idc\RoomAccessControlHandler",
 *     "list_builder" = "Drupal\idc\RoomListBuilder",
 *     "form" = {
 *       "default" = "Drupal\idc\Form\RoomForm",
 *       "delete" = "Drupal\idc\Form\RoomDeleteForm"
 *     }
 *   },
 *   base_table = "idc_room",
 *   data_table = "idc_room_field_data",
 *   revision_table = "idc_room_revision",
 *   revision_data_table = "idc_room_field_revision",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "rid",
 *     "revision" = "vid",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "label" = "name"
 *   },
 *   links = {
 *     "edit-form" = "/admin/idc/room/{room}/edit",
 *     "delete-form" = "/admin/idc/room/{room}/delete",
 *     "loke-over" = "/admin/idc/room/{room}/cabinet"
 *   }
 * )
 *
 */
class Room extends ContentEntityBase implements IdcEntityInterface  {

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    if($this->isNew()) {
      $this->set('uid', \Drupal::currentUser()->id());
    }
  }

  /*
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $config = \Drupal::config('idc.settings');

    $fields['rid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the IDC Room entity.'))
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
      ->setDescription(t('The Room language code.'))
      ->setRevisionable(TRUE);

   $fields['name'] = BaseFieldDefinition::create('string')
     ->setLabel(t('Room name'))
     ->setRequired(TRUE)
     ->setRevisionable(TRUE)
     ->setDisplayOptions('form', array(
       'type' => 'string',
       'weight' => 1
     ))
     ->setDisplayConfigurable('form', TRUE);

    $fields['area'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Area'))
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'handler' => 'sub_term',
      	'handler_settings' => array(
      	  'target_bundles' =>array(
        	  'idc' => array('parent_id' => $config->get('idc.room.area')),
      	  )
        )
      ))
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' => 5
      ))
      ->setDisplayConfigurable('form', True);

    $fields['circuit'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Circuit'))
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'handler' => 'sub_term',
      	'handler_settings' => array(
      	  'target_bundles' =>array(
        	 'idc' => array('parent_id' => $config->get('idc.room.circuit')),
      	  )
        )
      ))
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' => 10
      ))
      ->setDisplayConfigurable('form', True);

    $fields['image'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Image'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
          'type' => 'image_image',
          'weight' => 15
      ))
      ->setDisplayConfigurable('form', True);

    $fields['introduction'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Introduction'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
          'type' => 'text_textarea',
          'weight' => 20
      ))
      ->setDisplayConfigurable('form', True);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
          'type' => 'text_textarea',
          'weight' => 25
      ))
      ->setDisplayConfigurable('form', True);

    $fields['front_visible'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Front visible'))
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'handler' => 'sub_term',
      	'handler_settings' => array(
      	  'target_bundles' => array(
        	 'idc' => array('parent_id' => $config->get('idc.room.front_visible')),
      	  )
        )
      ))
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' => 35
      ))
      ->setDisplayConfigurable('form', True);

    $fields['speed_test_address'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Speed test address'))
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => 40
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['cabinet_number'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Cabinet number'))
      ->setDescription(t('The total number of statistical cabinet'))
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
   * 删除图片字段的值
   */
  public function removeImage() {
    unset($this->values['image']);
    unset($this->fields['image']);
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
