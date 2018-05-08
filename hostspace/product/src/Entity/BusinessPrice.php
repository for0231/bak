<?php
/*
 * @file
 * \Drupal\product\Entity\BusinessPrice 
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
 *   id = "business_price",
 *   label = @Translation("Business price"),
 *   handlers = {
 *     "access" = "Drupal\product\ProductPriceAccessHandler",
 *     "list_builder" = "Drupal\product\BusinessPriceListBuilder",
 *     "form" = {
 *       "default" = "Drupal\product\Form\BusinessPriceForm",
 *       "delete" = "Drupal\product\Form\BusinessPriceDeleteForm"
 *     }
 *   },
 *   base_table = "business_price",
 *   data_table = "business_price_field_data",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "pid",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "label" = "price"
 *   },
 *   links = {
 *     "edit-form" = "/admin/product/business_price/{business_price}/edit",
 *     "delete-form" = "/admin/product/business_price/{business_price}/delete",
 *   }
 * )
 *
 */
class BusinessPrice extends ContentEntityBase {

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
    $filter['businessId'] = $this->getObjectId('businessId');
    $content = $this->getSimpleValue('business_content');
    if(!empty($content)) {
      $filter['business_content'] = $content;
    }
    $price = $this->getSimpleValue('price');
    $entitys = entity_load_multiple_by_properties('product_business_price', $filter);
    foreach($entitys as $entity) {
      $entity->set('price', $price);
      $entity->save();
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
      ->setTranslatable(TRUE)
      ->setSetting('min', 0)
      ->setDefaultValue(0)
      ->setDisplayOptions('form', array(
        'type' => 'number',
        'weight' => 15
      ))
      ->setDisplayConfigurable('form', True);

    $fields['payment_mode'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Payment mode'))
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
