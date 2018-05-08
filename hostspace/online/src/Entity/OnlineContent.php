<?php


namespace Drupal\online\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * defines the entity class
 * @ContentEntityType(
 *   id = "online_content",
 *   label = @Translation("online content"),
 *   handlers = {
 *     "access" = "Drupal\online\OnlineContentAccessHandler",
 *     "list_builder" = "Drupal\online\OnlineContentListBuilder",
 *     "form" = {
 *       "default" = "Drupal\online\Form\OnlineContentForm",
 *       "delete" = "Drupal\online\Form\OnlineContentDeleteForm",
 *     }
 *   },
 *   base_table = "online_content",
 *   data_table = "online_content_field_data",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "label" = "ask_name"
 *   },
 *   links = {
 *     "show-form" = "/admin/online/content/{online_content}/show",
 *     "delete-form" = "/admin/online/content/{online_content}/delete",
 *   }
 * )
 *
 */
/**
 * 
 * @author Administrator
 *
 */
class OnlineContent extends ContentEntityBase{
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    //在线联系id
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
    
    //前台提问者
    $fields['ask_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => 1
      ))
      ->setDisplayConfigurable('form', TRUE);
      
    //邮箱
    $fields['email'] = BaseFieldDefinition::create('string')
      ->setLabel(t('email'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => 1
      ))
      ->setDisplayConfigurable('form', TRUE);
      
    //远程访问ip
    $fields['remote_iP'] = BaseFieldDefinition::create('string')
      ->setLabel(t('remote_iP'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE);

    //接收状态（已接受，待接受）
    $fields['status'] = BaseFieldDefinition::create('integer')
      ->setLabel('接收状态')
      ->setDefaultValue(0);
    //记录接受人的id
    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
    ->setLabel(t('Create user'))
    ->setSetting('target_type', 'user');
    
    //创建时间
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('Create time'));
    
    $fields['love_id'] = BaseFieldDefinition::create('integer')
    ->setLabel('点赞');
    
    return $fields;
  }
}
