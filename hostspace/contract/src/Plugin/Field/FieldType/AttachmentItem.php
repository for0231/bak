<?php

/**
 * @file
 * Contains \Drupal\contract\Plugin\Field\FieldType\AttachmentItem.
 */

namespace Drupal\contract\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

use Drupal\file\Plugin\Field\FieldType\FileItem;
/**
 * Plugin implementation of the 'file' field type.
 *
 * @FieldType(
 *   id = "attachment_file",
 *   label = @Translation("Attachment File"),
 *   description = @Translation("This field stores the ID of a file as an integer value."),
 *   default_widget = "file_generic",
 *   default_formatter = "file_default",
 * )
 */

class AttachmentItem extends FileItem {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);
    $schema['columns']['file_type'] = array(
      'description' => '附件类型',
      'type' => 'int',
      'unsigned' => TRUE,
    );    
    return $schema;
  }

    /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);
    $properties['file_type'] = DataDefinition::create('integer')
      ->setLabel('附件类型');
    return $properties;
  }
  
}
