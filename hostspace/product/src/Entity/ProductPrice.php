<?php
/*
 * @file
 * \Drupal\product\Entity\ProductPrice
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
 *   id = "product_price",
 *   label = @Translation("Product price"),
 *   handlers = {
 *     "access" = "Drupal\product\ProductPriceAccessHandler",
 *     "list_builder" = "Drupal\product\ProductPriceListBuilder",
 *     "form" = {
 *       "default" = "Drupal\product\Form\ProductPriceForm",
 *       "delete" = "Drupal\product\Form\ProductPriceDeleteForm"
 *     }
 *   },
 *   base_table = "product_price",
 *   data_table = "product_price_field_data",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "pid",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "label" = "price"
 *   },
 *   links = {
 *     "edit-form" = "/admin/product/price/{product_price}/edit",
 *     "delete-form" = "/admin/product/price/{product_price}/delete",
 *   }
 * )
 *
 */
class ProductPrice extends ContentEntityBase {

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

    $fields['user_level'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Agent level'))
      ->setSetting('target_type', 'user_role')
      ->setTranslatable(TRUE)
      ->setRequired(TRUE)
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' => 5
      ))
      ->setDisplayConfigurable('form', True);

    $fields['payment_mode'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Payment mode'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE);

    $fields['original_price'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Original price'))
      ->setTranslatable(TRUE)
      ->setSetting('min', 0)
      ->setDisplayOptions('form', array(
        'type' => 'number',
        'weight' => 15
      ))
      ->setDisplayConfigurable('form', True);

    $fields['price'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('price'))
      ->setTranslatable(TRUE)
      ->setRequired(TRUE)
      ->setSetting('min', 0)
      ->setDisplayOptions('form', array(
        'type' => 'number',
        'weight' => 15
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
