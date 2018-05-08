<?php
/*
 * @file
 * \Drupal\part\Entity\NetworkCard
 */

namespace Drupal\part\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\part\PartEntityBase;

/**
 * defines the network card entity class
 *
 * @ContentEntityType(
 *   id = "part_network",
 *   label = @Translation("Network card"),
 *   handlers = {
 *     "form" = {
 *       "default" = "Drupal\part\Form\NetworkCardForm"
 *     }
 *   },
 *   base_table = "part_network",
 *   data_table = "part_network_field_data",
 *   revision_table = "part_network_revision",
 *   revision_data_table = "part_network_field_revision",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "nid",
 *     "revision" = "vid",
 *     "label" = "standard",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid"
 *   }
 * )
 *
 */
class NetworkCard extends PartEntityBase {
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

    $fields['nid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the chassis entity.'))
      ->setReadOnly(TRUE);

    return $fields;
  }
}
