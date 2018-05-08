<?php
/*
 * @file
 * \Drupal\knowledge\Entity\KnowledgeType
 */

namespace Drupal\knowledge\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * defines the entity class
 * @ContentEntityType(
 *   id = "knowledge_type",
 *   label = @Translation("Knowledge type"),
 *   handlers = {
 *     "storage" = "Drupal\knowledge\KnowledgeTypeStorage",
 *     "access" = "Drupal\knowledge\KnowledgeTypeAccessHandler",
 *     "list_builder" = "Drupal\knowledge\KnowledgeTypeListBuilder",
 *     "form" = {
 *       "default" = "Drupal\knowledge\Form\KnowledgeTypeForm",
 *       "delete" = "Drupal\knowledge\Form\KnowledgeTypeDeleteForm"
 *     }
 *   },
 *   base_table = "knowledge_type",
 *   data_table = "knowledge_type_field_data",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "label" = "name"
 *   },
 *   links = {
 *     "edit-form" = "/admin/knowledge/type/{knowledge_type}/edit",
 *     "delete-form" = "/admin/knowledge/type/{knowledge_type}/delete"
 *   }
 * )
 *
 */
class KnowledgeType extends ContentEntityBase {
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
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel('ID')
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID.'))
      ->setReadOnly(TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code.'))
      ->setRevisionable(TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => 1
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['parent_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Parent directory'))
      ->setDefaultValue(0)
      ->setSetting('unsigned', TRUE);

    $fields['problem_quantity'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Problem quantity'))
      ->setDefaultValue(0)
      ->setSetting('unsigned', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('Create time'))
      ->setTranslatable(TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Create user'))
      ->setDescription(t('The user ID of the author.'))
      ->setSetting('target_type', 'user')
      ->setTranslatable(TRUE);

    return $fields;
  }

  /**
   * 得到此分类的内容
   */
  public function getKnowledgeContent(EntityStorageInterface $storage, $limit = 5) {
    if($this->get('parent_id')->value) {
      $query = $storage->getQuery();
      $query->sort('id', 'DESC');
      $query->condition('type_id', $this->id());
      $entity_ids = $query->execute();
      return $storage->loadMultiple($entity_ids);
    }
    return array();
  }
}
