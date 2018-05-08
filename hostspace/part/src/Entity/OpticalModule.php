<?php
/*
 * @file
 * \Drupal\part\Entity\OpticalModule
 */

namespace Drupal\part\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\part\PartEntityBase;

/**
 * defines the optical module entity class
 *
 * @ContentEntityType(
 *   id = "part_optical",
 *   label = @Translation("Optical module"),
 *   handlers = {
 *     "form" = {
 *       "default" = "Drupal\part\Form\OpticalModuleForm"
 *     }
 *   },
 *   base_table = "part_optical",
 *   data_table = "part_optical_field_data",
 *   revision_table = "part_optical_revision",
 *   revision_data_table = "part_optical_field_revision",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "oid",
 *     "revision" = "vid",
 *     "label" = "standard",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid"
 *   }
 * )
 *
 */
class OpticalModule extends PartEntityBase {
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

    $fields['oid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the chassis entity.'))
      ->setReadOnly(TRUE);

    return $fields;
  }
}
