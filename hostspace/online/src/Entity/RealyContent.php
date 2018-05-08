<?php


namespace Drupal\online\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * defines the entity class
 * @ContentEntityType(
 *   id = "realy_content",
 *   label = @Translation("realy content"),
 *   handlers = {
 *     "form" = {
 *       "home" = "Drupal\online\Form\HomeRealyForm",
 *     }
 *   },
 *   base_table = "realy_content",
 *   data_table = "realy_content_field_data",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 * )
 *
 */

class RealyContent extends ContentEntityBase{

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
    
    //发送者
    $fields['sender'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE);
    
     //发送时间
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('Create time'))
      ->setTranslatable(TRUE);
      
    //发送内容
    $fields['content'] = BaseFieldDefinition::create('text_long')
    ->setLabel(t('Content'))
    ->setTranslatable(TRUE);
    
    //显示状态（已显示，未显示）
    $fields['status'] = BaseFieldDefinition::create('integer')
    ->setLabel('显示状态')
    ->setDefaultValue(0);    
    
    
    //后台回复者
    $fields['receiver'] = BaseFieldDefinition::create('string')
    ->setLabel(t('Name'))
    ->setRequired(TRUE)
    ->setTranslatable(TRUE);

    return $fields;
  }
}
