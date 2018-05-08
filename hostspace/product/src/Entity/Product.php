<?php
/*
 * @file
 * \Drupal\product\Entity\ProductBusiness
 */

namespace Drupal\product\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * defines the IDC Room entity class
 *
 * @ContentEntityType(
 *   id = "product",
 *   label = @Translation("Product"),
 *   handlers = {
 *     "access" = "Drupal\product\ProductAccessHandler",
 *     "list_builder" = "Drupal\product\ProductListBuilder",
 *     "form" = {
 *       "default" = "Drupal\product\Form\ProductForm",
 *       "clone" = "Drupal\product\Form\ProductCloneForm",
 *       "delete" = "Drupal\product\Form\ProductDeleteForm"
 *     }
 *   },
 *   base_table = "product",
 *   data_table = "product_field_data",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "pid",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "label" = "name"
 *   },
 *   links = {
 *     "edit-form" = "/admin/product/{product}/edit",
 *     "edit-clone-form" = "/admin/product/{product}/clone",
 *     "delete-form" = "/admin/product/{product}/delete",
 *     "price-view" = "/admin/product/{product}/price/list",
 *     "business-price-view" = "/admin/product/{product}/business/price/list"
 *   }
 * )
 *
 */
class Product extends ContentEntityBase {
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
    if(isset($this->default_business)) {
      $default_business = $this->default_business;
      $productId = $this->id();
      \Drupal::service('product.default.business')->reAdd($default_business, $productId);
    }
    if(isset($this->add_business_price)) {
      $business_prices = entity_load_multiple_by_properties('business_price', array());
      foreach($business_prices as $business_price) {
        entity_create('product_business_price', array(
          'productId' => $this->id(),
          'payment_mode' => $business_price->getSimpleValue('payment_mode'),
          'businessId' => $business_price->getObjectId('businessId'),
          'business_content' => $business_price->getSimpleValue('business_content'),
          'price' => $business_price->getSimpleValue('price')
        ))->save();
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    $price_storage = \Drupal::entityManager()->getStorage('product_price');
    $business_price_storage = \Drupal::entityManager()->getStorage('product_business_price');
    foreach($entities as $entity) {
      \Drupal::service('product.default.business')->deleteByProduct($entity->id());
      $product_price = $price_storage->loadByProperties(array('productId' => $entity->id()));
      $price_storage->delete($product_price);

      $business_price = $business_price_storage->loadByProperties(array('productId' => $entity->id()));
      $business_price_storage->delete($business_price);
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
      ->setDescription(t('The language code.'));

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Product name'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', array(
       'type' => 'string',
       'weight' => 1
     ))
     ->setDisplayConfigurable('form', TRUE);

    $fields['server_type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Server catalog'))
      ->setDescription(t('Select a category of server'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSetting('target_type', 'server_type')
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => 5
      ))
      ->setDisplayConfigurable('form', True);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'text_textarea',
        'weight' => 15
      ))
      ->setDisplayConfigurable('form', True);

    $fields['parameters'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Parameters'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'text_textarea',
        'weight' => 20
      ))
      ->setDisplayConfigurable('form', True);

    $fields['display_cpu'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Display cpu'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', array(
       'type' => 'string',
       'weight' => 6
     ))
     ->setDisplayConfigurable('form', TRUE);

    $fields['display_memory'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Display memory'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', array(
       'type' => 'string',
       'weight' => 7
     ))
     ->setDisplayConfigurable('form', TRUE);

     $fields['display_harddisk'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Display harddisk'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', array(
       'type' => 'string',
       'weight' => 8
     ))
     ->setDisplayConfigurable('form', TRUE);

    $fields['display_system'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Display system'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', array(
       'type' => 'string',
       'weight' => 9
     ))
     ->setDisplayConfigurable('form', TRUE);

    $fields['custom_business'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Optional configuration'))
      ->setTranslatable(TRUE)
       ->setDisplayOptions('form', array(
        'type' => 'boolean_checkbox',
        'settings' => array('display_label' => 'true'),
        'weight' => 10
      ))
      ->setDisplayConfigurable('form', True);

    $fields['front_Dispaly'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Display'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'boolean_checkbox',
        'settings' => array('display_label' => 'true'),
        'weight' => 11
      ))
      ->setDisplayConfigurable('form', True);

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

     $fields['rids'] = BaseFieldDefinition::create('string')
      ->setLabel(t('所属机房'))
      ->setDescription(t('可能属于多个机房,json后存入!'))
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
