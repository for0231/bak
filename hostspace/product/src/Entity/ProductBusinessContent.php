<?php
/*
 * @file
 * \Drupal\product\Entity\ProductBusiness 
 */

namespace Drupal\product\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * defines the Business content entity class
 * 
 * @ContentEntityType(
 *   id = "product_business_content",
 *   label = @Translation("Product business content"),
 *   handlers = {
 *     "access" = "Drupal\product\ProductBusinessAccessHandler",
 *     "form" = {
 *       "default" = "Drupal\product\Form\BusinessContentForm",
 *       "delete" = "Drupal\product\Form\BusinessContentDeleteForm"
 *     }
 *   },
 *   base_table = "product_business_content",
 *   data_table = "product_business_content_field_data",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "cid",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "label" = "name"
 *   },
 *   links = {
 *     "edit-form" = "/admin/product/business/content/{product_business_content}/edit",
 *     "delete-form" = "/admin/product/business/content/{product_business_content}/delete"
 *   }
 * )
 */
class ProductBusinessContent extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    if($this->isNew()) {
      $this->set('uid', \Drupal::currentUser()->id());
      $this->set('use_number', '0');
    }
  }
  
  /*
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['cid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID.'))
      ->setReadOnly(TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code.'));

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Content name'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => 1
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['businessId'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Business'))
      ->setDescription(t('Belongs to the business'))
      ->setSetting('target_type', 'product_business')
      ->setTranslatable(TRUE);
   
    $fields['use_number'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Use'))
      ->setTranslatable(TRUE);
    
    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Create user'))
      ->setDescription(t('The user ID of the author.'))
      ->setSetting('target_type', 'user')
      ->setTranslatable(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('Create time'))
      ->setTranslatable(TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('Change time'))
      ->setTranslatable(TRUE);

    return $fields;
  }
  
  /**
   * 获取简单的实体字段的值
   */
  public function getSimpleValue($name) {
    return $this->get($name)->value;
  }

  /**
   * 获取对象字段的实体
   */
  public function getObject($name) {
    return $this->get($name)->entity;
  }

  /**
   * 获取对象字段的实体Id
   */
  public function getObjectId($name) {
    return $this->get($name)->target_id;
  }
  
}
