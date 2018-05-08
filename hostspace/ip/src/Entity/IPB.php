<?php

/**
 * @file
 * Contains \Drupal\ip\Entity\IPB.
 */

namespace Drupal\ip\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the IPB entity class.
 *
 * @ContentEntityType(
 *   id ="ipb",
 *   label = @Translation("Business IP"),
 *   handlers = {
 *      "access" = "Drupal\ip\IPAccessControlHandler",
 *      "list_builder" = "Drupal\ip\BusinessIpListBuilder",
 *      "form" = {
 *        "default" = "Drupal\ip\Form\AddBusinessIpForm",
 *        "multiple" = "Drupal\ip\Form\AddMultipleBusinessIpForm",
 *        "delete" = "Drupal\ip\Form\BusinesspDeleteForm",
 *        "type_ops" = "Drupal\ip\Form\BusinessIpTypeForm",
 *     }
 *   },
 *   base_table = "business_ip",
 *   data_table = "business_ip_field_data",
 *   revision_table = "business_ip_revision",
 *   revision_data_table = "business_ip_field_revision",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "label" = "ip",
 *     "langcode" = "langcode",
 *     "uuid"  = "uuid",
 *   },
 *    links = {
 *     "edit-form" = "/admin/ipb/{ipb}/edit",
 *     "delete-form" = "/admin/ipb/{ipb}/delete",
 *   }
 * )
 */
class IPB extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    // 系统预设服务器使用状态、上架状态、服务器类
    if($this->isNew()) {
      $this->set('status_equipment', 'off');
      $this->set('uid', \Drupal::currentUser()->id());
      $op = 'insert';
    }
    $ip = $this->label();
    $ip_number = sprintf('%u', ip2long($ip));
    $this->set('ip_number', $ip_number);

  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID'))
      ->setReadOnly(TRUE);

    $fields['vid'] = BaseFieldDefinition::create('integer')
      ->setDescription(t('The revision ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The cpu language code.'))
      ->setRevisionable(TRUE);

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('IP ID'))
      ->setDescription(t('ID.'));

    $fields['ip'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Ip'))
      ->setDescription(t('IP'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE);

    $fields['ip_number'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ip number'))
      ->setDescription(t('Numbers type corresponding to IP.'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('unsigned', TRUE)
      ->setSetting('size', 'big');

    $fields['puid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setDescription(t('The user of the ip belongs'))
      ->setSetting('target_type', 'user')
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', array(
          'type' => 'entity_reference_autocomplete',
          'weight' => 2
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);

    $fields['rid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('机房ID'))
      ->setDescription(t('业务IP所属机房'))
      ->setDefaultValue(0);

    $fields['group_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('IP Group'))
      ->setRequired(TRUE)
      ->setDefaultValue(0)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Creator'))
      ->setDescription(t('The user ID of the ip creator'))
      ->setSetting('target_type', 'user')
      ->setQueryable(FALSE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    $fields['status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Ip state'))
      ->setDescription(t('IP state'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE);

    $fields['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Ip type'))
      ->setDescription(t('Ip type'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'handler' => 'default',
        'handler_settings' => array(
          'target_bundles' => array(
            'suitable' => 'business_ip_type',
          ),
          'auto_create' => TRUE,
        ),
      ))
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' => 3
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);

    $fields['classify'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Ip Classify'))
      ->setDescription(t('Ip Classify'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'handler' => 'default',
        'handler_settings' => array(
          // Reference a single vocabulary.
          'target_bundles' => array(
            'suitable' => 'business_ip_segment_type',
          ),
          // Enable auto-create.
          'auto_create' => TRUE,
        ),
      ))
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' => 3
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);

    $fields['ip_segment'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Ip segment'))
      ->setDescription(t('Ip segment'))
      ->setTranslatable(TRUE)
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
          'type' => 'string',
          'weight' => 3
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setDescription(t('Description of the ip'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
          'type' => 'text_textarea',
          'weight' => 13
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the ip was created.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the ip was last edited'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    $fields['status_equipment'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Server Used Equipment Status'))
      ->setDescription(t('The server equipment status'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);
    return $fields;
  }
}
