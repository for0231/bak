<?php
namespace Drupal\server\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Session\AccountInterface;

/**
 * Plugin implementation of the 'multi path' field type.
 *
 * @FieldType(
 *   id = "multi_part",
 *   label = @Translation("Multi Part"),
 *   description = @Translation("Multi Part"),
 *   default_widget = "multi_part_default",
 *   default_formatter = "multi_part_default"
 * )
 */
class MultiPart extends EntityReferenceItem {
  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $scheam = parent::schema($field_definition);
    $scheam['columns']['value'] = array(
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => FALSE
    );
    return $scheam;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);
    $properties['value'] = DataDefinition::create('integer')
      ->setLabel(t('number'));
    return $properties;
  }
}
