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
 *   id = "hostclient",
 *   label = @Translation("Host clients"),
 *   handlers = {
 *     "access" = "Drupal\order\HostclientAccessHandler",
 *     "list_builder" = "Drupal\order\HostclientListBuilder",
 *     "form" = {
 *       "stop" = "Drupal\order\Form\StopServerForm",
 *       "detail" = "Drupal\order\Form\HostclientDetailForm",
 *       "remove_ip" = "Drupal\order\Form\RemoveIpForm",
 *       "user_remove_ip" = "Drupal\order\Form\UserRemoveIpForm"
 *     }
 *   },
 *   base_table = "hostclients",
 *   data_table = "hostclients_field_data",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "hid",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "label" = "ipm_id"
 *   },
 *   links = {
 *     "stop-form" = "/admin/hostclient/{hostclient}/stop",
 *     "detail-form" = "/admin/hostclient/{hostclient}/detail",
 *     "remove_ip-form" = "/admin/hostclient/{hostclient}/remove_ip"
 *   }
 * )
 *
 */
class HostClients extends ContentEntityBase {

  /*
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    $this->set('last_uid', \Drupal::currentUser()->id());
  }

  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    //修改管理IP状态
    if(isset($this->brfore_save_ipm)) {
      $old_ipm = $this->brfore_save_ipm;
      $ipm_obj = $this->getObject('ipm_id');
      if($old_ipm) {
        if($ipm_obj->id() != $old_ipm) {
          $old_ipm_obj = entity_load('ipm', $old_ipm);
          $old_ipm_obj->set('status', 1);
          $old_ipm_obj->save();
          $ipm_obj->set('status', 5);
          $ipm_obj->save();
        }
      } else {
        $ipm_obj->set('status', 5);
        $ipm_obj->save();
      }
    }
    //修改业务ip的状态
    if(isset($this->save_ipb_change)) {
      $add_ipb = $this->save_ipb_change['add'];
      $rm_ipb = $this->save_ipb_change['rm'];
      foreach($add_ipb as $ipb_value) {
        $ipb_obj = entity_load('ipb', $ipb_value);
        $ipb_obj->set('status', 5);
        $ipb_obj->save();
      }
      foreach($rm_ipb as $ipb_value) {
        $ipb_obj = entity_load('ipb', $ipb_value);
        $ipb_obj->set('status', 1);
        $ipb_obj->save();
      }
      if(isset($this->save_business_change)) {
        $hostclient_service = \Drupal::service('hostclient.serverservice');
        $hostclient_service->delHostclientBusinessByIp($this->id(), $rm_ipb);
      }
    }
  }

  /*
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields['hid'] = BaseFieldDefinition::create('integer')
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

    $fields['product_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Product'))
      ->setSetting('target_type', 'product')
      ->setTranslatable(TRUE);

    $fields['server_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('server'))
      ->setSetting('target_type', 'server')
      ->setTranslatable(TRUE);

    $fields['cabinet_server_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Cabinet server')) //所在机位
      ->setSetting('target_type', 'cabinet_server')
      ->setTranslatable(TRUE);

    $fields['ipm_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Manage ip'))
      ->setSetting('target_type', 'ipm')
      ->setTranslatable(TRUE);

    $fields['ipb_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel('Business ip')
      ->setSetting('target_type', 'ipb')
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setTranslatable(TRUE);

    $fields['client_uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('client'))
      ->setSetting('target_type', 'user')
      ->setTranslatable(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('Create time'))
      ->setTranslatable(TRUE);

    $fields['equipment_date'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Equipment date')) //上架时间
      ->setDescription(t('Equipment date'))
      ->setDefaultValue(0)
      ->setSetting('unsigned', TRUE);

    $fields['service_expired_date'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Expired date')) //到期时间
      ->setDescription(t('Expired date'))
      ->setDefaultValue(0)
      ->setSetting('unsigned', TRUE);

    $fields['last_uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Last user'))
      ->setSetting('target_type', 'user')
      ->setTranslatable(TRUE);

    $fields['description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Description'))
      ->setTranslatable(TRUE)
      ->setDefaultValue('')
      ->setSetting('max_length', 1000);

    $fields['trial'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('trial')) //是否试用
      ->setDescription(t('Order product trial status'))
      ->setDefaultValue(0);

    $fields['init_pwd'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Init pwd'))
      ->setTranslatable(TRUE)
      ->setDefaultValue('');

    $fields['server_system'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('System'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setTranslatable(TRUE);

    $fields['server_mask'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Subnet mask'))
      ->setTranslatable(TRUE)
      ->setDefaultValue('');

    $fields['server_gateway'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Gateway'))
      ->setTranslatable(TRUE)
      ->setDefaultValue('');

    $fields['server_dns'] = BaseFieldDefinition::create('string')
      ->setLabel(t('DNS'))
      ->setTranslatable(TRUE)
      ->setDefaultValue('');

    $fields['server_manage_card'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Management Card'))
      ->setTranslatable(TRUE)
      ->setDefaultValue(0);

    $fields['unpaid_order'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Unpaid Order'))
      ->setDescription(t('Unpaid Order'))
      ->setDefaultValue(0)
      ->setSetting('unsigned', TRUE);

    $fields['status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Status'))
      ->setDescription(t('Status'))
      ->setDefaultValue(0);

    $fields['isbind'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('已绑定'))
      ->setTranslatable(TRUE)
      ->setDefaultValue(0);

    $fields['online_op'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('上机操作'))
      ->setTranslatable(TRUE)
      ->setDefaultValue(0);

    $fields['rid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('机房'))
      ->setSetting('target_type', 'room')
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
