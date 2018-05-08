<?php

/**
 * @file
 * Contains \Drupal\question\Entity\ServerQuestionClass.
 */

namespace Drupal\question\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the server question entity class.
 * 
 * @ContentEntityType(
 *   id ="question_class",
 *   label = @Translation("Category of question"),
 *   handlers = {
 *      "form" = {
 *        "default" = "Drupal\question\Form\AddCategoryForm",
 *        "delete" = "Drupal\question\Form\ServerQuestionClassDeleteForm"
 *     }
 *   },
 *   base_table = "server_question_class",
 *   data_table = "server_question_class_field_data",
 *   revision_table = "server_question_class_revision",
 *   revision_data_table = "server_question_class_field_revision",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "label" = "class_name",
 *     "langcode" = "langcode",
 *     "uuid"  = "uuid",
 *   },
 * )
 */
class ServerQuestionClass extends ContentEntityBase {
  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID'))
      ->setReadOnly(TRUE);

    $fields['vid'] = BaseFieldDefinition::create('integer')
      ->setDescription(t('The revision ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The cpu language code.'))
      ->setRevisionable(TRUE);

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('ID.'));

    $fields['class_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name of category'))
      ->setDescription(t('Name of category'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
      	'type' => 'string',
        'weight' => 1
      ))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE);

    $fields['limited_stamp'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('limited stamp'))
      ->setDescription(t('Time of complete the question(Unit:min).'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
      	'type' => 'number',
        'weight' => 3
      ))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE);

    $fields['description'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Description'))
      ->setDescription(t('Description of the category.'))
			->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
          'type' => 'string_textarea',
          'weight' => 6
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);

    $fields['parent'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Parent'))
      ->setDescription(t('ID of parent.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'handler' => 'sub_term',
      	'handler_settings' => array(
      	  'target_bundles' => array(
        	  'question_category' => array('parent_id' => \Drupal::config('question.settings')->get('question.category')), 
      	  )
        )
      ))
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' => 5
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);

      return $fields;
  }
}
