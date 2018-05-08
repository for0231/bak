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
 * defines the IDC Room entity class
 *
 * @ContentEntityType(
 *   id = "product_business",
 *   label = @Translation("Product business"),
 *   handlers = {
 *     "access" = "Drupal\product\ProductBusinessAccessHandler",
 *     "list_builder" = "Drupal\product\ProductBusinessListBuilder",
 *     "form" = {
 *       "default" = "Drupal\product\Form\BusinessForm",
 *       "delete" = "Drupal\product\Form\BusinessDeleteForm"
 *     }
 *   },
 *   base_table = "product_business",
 *   data_table = "product_business_field_data",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "bid",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "label" = "name"
 *   },
 *   links = {
 *     "edit-form" = "/admin/product/business/{product_business}/edit",
 *     "delete-form" = "/admin/product/business/{product_business}/delete",
 *     "content-form" = "/admin/product/business/{product_business}/content/add",
 *     "entity-content-form" = "/admin/product/business/{product_business}/entity/content/add"
 *   }
 * )
 *
 */
class ProductBusiness extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    if($this->isNew()) {
      $this->set('uid', \Drupal::currentUser()->id());
    }
    $lib = $this->getSimpleValue('resource_lib');
    if($lib == 'none' || $lib == 'create') {
      $this->set('entity_type', null);
    }
  }

  /*
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['bid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the IDC Room entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID.'))
      ->setReadOnly(TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The Room language code.'));

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Business name'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', array(
       'type' => 'string',
       'weight' => 1
     ))
     ->setDisplayConfigurable('form', TRUE);

    $fields['catalog'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Business catalog'))
      ->setDescription(t('Classification of business, facilitate the classification of display'))
      ->setRequired(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'options_list_callback' => NULL,
        'handler' => 'default',
        'handler_settings' => array(
          'target_bundles' =>array(
            'product_business_Catalog' => 'product_business_Catalog',
          )
        )
      ))
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' => 5
      ))
      ->setDisplayConfigurable('form', True);
    $fields['operate'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Business operate'))
      ->setDescription(t('Set custom business operation mode'))
      ->setTranslatable(TRUE);

    $fields['resource_lib'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Resource library'))
      ->setDescription(t('Resource library'))
      ->setTranslatable(TRUE);

    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity type'))
      ->setDescription(t('Business content related part type'))
      ->setTranslatable(TRUE);

    $fields['combine_mode'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Merger way'))
      ->setDescription(t('The default settings and business combination mode')) //设置与默认业务合并方式
      ->setTranslatable(TRUE);

    $fields['upgrade'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Upgrade'))
      ->setDescription(t('Is it possible to upgrade'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'boolean_checkbox',
        'settings' => array('display_label' => 'true'),
        'weight' => 50
      ))
      ->setDisplayConfigurable('form', True);

    $fields['locked'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Locked'))
      ->setDescription(t('After lock cannot be edited'))
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
