<?php
/*
 * @file
 * \Drupal\part\Entity\Chassis 
 */

namespace Drupal\part\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\part\PartEntityBase;

/**
 * defines the Chassis entity class
 * 
 * @ContentEntityType(
 *   id = "part_chassis",
 *   label = @Translation("Chassis"),
 *   handlers = {
 *     "form" = {
 *       "default" = "Drupal\part\Form\ChassisForm"
 *     }
 *   },
 *   base_table = "part_chassis",
 *   data_table = "part_chassis_field_data",
 *   revision_table = "part_chassis_revision",
 *   revision_data_table = "part_chassis_field_revision",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "cid",
 *     "revision" = "vid",
 *     "label" = "standard",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid"
 *   }
 * )
 *
 */
class Chassis extends PartEntityBase {
  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    $this->set('uid', \Drupal::currentUser()->id());
  }
  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    \Drupal::service('part.partservice')->save($this, $update);
  }

  /*
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['cid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the chassis entity.'))
      ->setReadOnly(TRUE);
 
    $fields['disk_number'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Disk Number'))
      ->setDescription(t('The number of hard disk'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setDisplayOptions('form', array(
      	'type' => 'number',
        'weight' => 1
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['support_raid'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Support Raid'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'boolean_checkbox',
        'settings' => array('display_label' => 'true'),
        'weight' => 5
      ))
      ->setDisplayConfigurable('form', True);
 
    return $fields;
  }
} 
