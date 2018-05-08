<?php

/**
 * @file
 * Contains \Drupal\ip\Entity\IPS.
 */

namespace Drupal\ip\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\hostlog\HostLogFactory;

/**
 * Defines the IPS entity class.
 *
 * @ContentEntityType(
 *   id ="ips",
 *   label = @Translation("Switch IP"),
 *   handlers = {
 *      "access" = "Drupal\ip\IPAccessControlHandler",
 *      "list_builder" = "Drupal\ip\SwitchListBuilder",
 *      "form" = {
 *        "default" = "Drupal\ip\Form\AddSwitchForm",
 *        "multiple" = "Drupal\ip\Form\AddMultipleSwitchIpForm",
 *        "delete" = "Drupal\ip\Form\SwitchDeleteForm",
 *     }
 *   },
 *   base_table = "switch_ip",
 *   data_table = "switch_ip_field_data",
 *   revision_table = "switch_ip_revision",
 *   revision_data_table = "switch_ip_field_revision",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "label" = "ip",
 *     "langcode" = "langcode",
 *     "uuid"  = "uuid",
 *   },
 *   links = {
 *     "edit-form" = "/admin/ips/{ips}/edit",
 *     "delete-form" = "/admin/ips/{ips}/delete",
 *   }

 * )
 */
class IPS extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // 系统预设服务器使用状态、上架状态、服务器类
    if($this->isNew()) {
      $this->set('uid', \Drupal::currentUser()->id());
      $this->set('status_equipment', 'off');
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
      ->setLabel(t('IP'))
     // ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => -30
      ))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE);

    $fields['ip_number'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ip number'))
      ->setDescription(t('Numbers type corresponding to IP.'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('unsigned', TRUE)
      ->setSetting('size', 'big');

   $fields['port'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Port'))
      ->setDescription(t('交换机的端口数.'))
      ->setSetting('target_type', 'user')
      ->setQueryable(FALSE)
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', array(
          'type' => 'number',
          'weight' => 2
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);


   $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Creator'))
      ->setDescription(t('The user ID of the ip creator'))
      ->setSetting('target_type', 'user')
      ->setQueryable(FALSE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setDescription(t('Description of the ip'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
          'type' => 'text_textarea',
          'weight' => 3
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
