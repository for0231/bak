<?php
/*
 * @file
 * \Drupal\order\Entity\Order
 */

namespace Drupal\order\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * defines the entity class
 *
 * @ContentEntityType(
 *   id = "order",
 *   label = @Translation("Order"),
 *   handlers = {
 *     "storage" = "Drupal\order\OrderStorage",
 *     "access" = "Drupal\order\OrderAccessHandler",
 *     "list_builder" = "Drupal\order\OrderListBuilder",
 *     "form" = {
 *       "create" = "Drupal\order\Form\BuildOrderForm",
 *       "payment" = "Drupal\order\Form\PaymentOrderForm",
 *       "cancel" = "Drupal\order\user\CancelOrder",
 *       "accept" = "Drupal\order\Form\AcceptOrderForm",
 *       "change" = "Drupal\order\Form\PriceChangeApplyForm",
 *       "trial" = "Drupal\order\Form\TrialApplyForm"
 *     }
 *   },
 *   base_table = "idc_order",
 *   data_table = "idc_order_field_data",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "oid",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "label" = "alias_order"
 *   },
 *   links = {
 *     "accept-form" = "/admin/order/{order}/accept",
 *     "detail-view" = "/admin/order/{order}/detail",
 *     "change_price-form" = "/admin/order/{order}/preferential",
 *     "trial-form" = "/admin/order/{order}/trial"
 *   }
 * )
 *
 */
class Order extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    if(!$update) {
      $product_list = $this->products;
      foreach($product_list as &$product) {
        $product->order_id = $this->id();
      }
      \Drupal::service('order.product')->add_multiple($product_list);
    }
  }

  /*
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields['oid'] = BaseFieldDefinition::create('integer')
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

    $fields['code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Order code'))
      ->setTranslatable(TRUE)
      ->setDefaultValue('');

    $fields['alias_order'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Order title'))
      ->setDescription(t('When you have multiple orders, orders the title will help you distinguish between memory or order'))
      ->setTranslatable(TRUE)
      ->setDefaultValue('');

    $fields['order_price'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Order price'))
      ->setTranslatable(TRUE)
      ->setDefaultValue(0);

    $fields['paid_price'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Paid price'))
      ->setTranslatable(TRUE)
      ->setDefaultValue(0);

    $fields['discount_price'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Discount price'))
      ->setTranslatable(TRUE)
      ->setDefaultValue(0);

    $fields['change_price_status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Change price status'))
      ->setDescription(t('Order change price status'))
      ->setDefaultValue(0);

    $fields['trial_status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('trial status'))
      ->setDescription(t('Order product trial status'))
      ->setDefaultValue(0);

    $fields['status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Order status'))
      ->setDescription(t('Order status'))
      ->setDefaultValue(0);

    $fields['payment_mode'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Mode of payment'))
      ->setDescription(t('Mode of payment'))
      ->setDefaultValue(0);

    $fields['payment_date'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Payment date'))
      ->setDescription(t('Payment date'))
      ->setDefaultValue(0)
      ->setSetting('unsigned', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Create user'))
      ->setDescription(t('The user ID of the author.'))
      ->setSetting('target_type', 'user')
      ->setTranslatable(TRUE);

    $fields['client_service'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Create user'))
      ->setDescription(t('The user ID of the author.'))
      ->setSetting('target_type', 'user')
      ->setTranslatable(TRUE);

    $fields['accept'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Accept'))
      ->setTranslatable(TRUE)
      ->setDefaultValue(0);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('Create time'))
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
