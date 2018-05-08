<?php
/*
 * @file
 * \Drupal\knowledge\Entity\KnowledgeContent
 */

namespace Drupal\knowledge\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * defines the entity class
 * @ContentEntityType(
 *   id = "knowledge_content",
 *   label = @Translation("Knowledge content"),
 *   handlers = {
 *     "storage" = "Drupal\knowledge\KnowledgeContentStorage",
 *     "access" = "Drupal\knowledge\KnowledgeContentAccessHandler",
 *     "list_builder" = "Drupal\knowledge\KnowledgeContentListBuilder",
 *     "form" = {
 *       "default" = "Drupal\knowledge\Form\KnowledgeContentForm",
 *       "delete" = "Drupal\knowledge\Form\KnowledgeContentDeleteForm"
 *     }
 *   },
 *   base_table = "knowledge_content",
 *   data_table = "knowledge_content_field_data",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "label" = "title"
 *   },
 *   links = {
 *     "edit-form" = "/admin/knowledge/content/{knowledge_content}/edit",
 *     "delete-form" = "/admin/knowledge/content/{knowledge_content}/delete"
 *   }
 * )
 *
 */
class KnowledgeContent extends ContentEntityBase {
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
    if(isset($this->before_type_update)) {
      $before_type_id = $this->before_type_update;
      $before_query = $storage->getQuery();
      $before_query->condition('type_id', $before_type_id);
      $before_result = $before_query->execute();
      $before_type = entity_load('knowledge_type', $before_type_id);
      if(!empty($before_type)) {
         $before_type->set('problem_quantity', count($before_result));
         $before_type->save();
      }
    }
    $entity_query = $storage->getQuery();
    $entity_query->condition('type_id', $this->get('type_id')->target_id);
    $result = $entity_query->execute();
    $type = $this->get('type_id')->entity;
    $type->set('problem_quantity', count($result));
    $type->save();
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);
    foreach($entities as $entity) {
      $entity_query = $storage->getQuery();
      $entity_query->condition('type_id', $entity->get('type_id')->target_id);
      $result = $entity_query->execute();
      $type = $entity->get('type_id')->entity;
      $type->set('problem_quantity', count($result));
      $type->save();
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

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => 1
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['type_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Family'))
			->setTranslatable(TRUE)
      ->setSetting('target_type', 'knowledge_type');

    $fields['content'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Content'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'text_textarea',
        'weight' => 15
      ))
      ->setDisplayConfigurable('form', True);

    $fields['tags'] = BaseFieldDefinition::create('string')
      ->setLabel(t('tags'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => 20
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['browse_number'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Browse number'))
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
}
