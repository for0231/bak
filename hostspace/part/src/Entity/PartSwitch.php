<?php
/*
 * @file
 * \Drupal\part\Entity\PartSwitch
 */

namespace Drupal\part\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\part\PartEntityBase;

/**
 * defines the Switch entity class
 *
 * @ContentEntityType(
 *   id = "part_switch",
 *   label = @Translation("Switch"),
 *   handlers = {
 *     "form" = {
 *       "default" = "Drupal\part\Form\SwitchForm"
 *     }
 *   },
 *   base_table = "part_switch",
 *   data_table = "part_switch_field_data",
 *   revision_table = "part_switch_revision",
 *   revision_data_table = "part_switch_field_revision",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "sid",
 *     "revision" = "vid",
 *     "label" = "standard",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid"
 *   }
 * )
 *
 */
class PartSwitch extends PartEntityBase {
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

    $fields['sid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the chassis entity.'))
      ->setReadOnly(TRUE);

    $fields['port_number'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Port number'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setDisplayOptions('form', array(
      	'type' => 'number',
        'weight' => 1
      ))
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }
}
