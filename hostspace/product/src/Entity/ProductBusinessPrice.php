<?php
/*
 * @file
 * \Drupal\product\Entity\ProductBusinessPrice 
 */

namespace Drupal\product\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * defines the IDC Room entity class
 * 
 * @ContentEntityType(
 *   id = "product_business_price",
 *   label = @Translation("Product business price"),
 *   handlers = {
 *     "access" = "Drupal\product\ProductPriceAccessHandler",
 *     "list_builder" = "Drupal\product\ProductBusinessPriceListBuilder",
 *     "form" = {
 *       "default" = "Drupal\product\Form\ProductBusinessPriceForm",
 *       "delete" = "Drupal\product\Form\ProductBusinessPriceDeleteForm"
 *     }
 *   },
 *   base_table = "product_business_price",
 *   data_table = "product_business_price_field_data",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "pid",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "label" = "price"
 *   },
 *   links = {
 *     "delete-form" = "/admin/product/business/price/{product_business_price}/delete"
 *   }
 * )
 *
 */
class ProductBusinessPrice extends ContentEntityBase {

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
    $fields['pid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID.'))
      ->setReadOnly(TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The Room language code.'));

    $fields['productId'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Product'))
      ->setSetting('target_type', 'product')
      ->setTranslatable(TRUE);

    $fields['payment_mode'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Payment mode'))
      ->setTranslatable(TRUE);
    
    $fields['businessId'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Business'))
      ->setDescription(t('Belongs to the business'))
      ->setSetting('target_type', 'product_business')
      ->setTranslatable(TRUE); 

    $fields['business_content'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Business content'))
      ->setTranslatable(TRUE);
 
    $fields['price'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('price'))
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
