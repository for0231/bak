<?php

/**
 * @file
 * Contains \Drupal\ip\Entity\IPM.
 */

namespace Drupal\ip\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
/**
 * Defines the IPM entity class.
 *
 * @ContentEntityType(
 *   id ="ipm",
 *   label = @Translation("Management IP"),
 *    handlers = {
 *      "access" = "Drupal\ip\IPAccessControlHandler",
 *      "list_builder" = "Drupal\ip\ManagementIpListBuilder",
 *      "form" = {
 *        "default" = "Drupal\ip\Form\AddManagementIpForm",
 *        "multiple" = "Drupal\ip\Form\AddMultipleManagementIpForm",
 *        "delete" = "Drupal\ip\Form\ManagementIpDeleteForm",
 *     }
 *   },
 *   base_table = "management_ip",
 *   data_table = "management_ip_field_data",
 *   revision_table = "management_ip_revision",
 *   revision_data_table = "management_ip_field_revision",
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
 *     "edit-form" = "/admin/ipm/{ipm}/edit",
 *     "delete-form" = "/admin/ipm/{ipm}/delete",
 *   }
 * )
 */
class IPM extends ContentEntityBase {

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
      ->setLabel(t('Ip'))
      ->setDescription(t('Management IP.'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE);

   $fields['ip_number'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ip number'))
      ->setDescription(t('Numbers type corresponding to IP.'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('unsigned', TRUE)
      ->setSetting('size', 'big');

   $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Creator'))
      ->setDescription(t('The user ID of the ip creator'))
      ->setSetting('target_type', 'user')
      ->setQueryable(FALSE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    $fields['port'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Port'))
      ->setDescription(t('The port of the management ip.'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE);

    $fields['status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Ip state'))
      ->setDescription(t('IP state'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE);

   $fields['server_type'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Server type'))
      ->setDescription(t('Server type'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setDescription(t('Description of the ip'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
          'type' => 'text_textarea',
          'weight' => 15
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

    $fields['rid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('IP Group'))
      ->setRequired(TRUE)
      ->setDefaultValue(0)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE);

    $fields['group_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('IP Group'))
      ->setRequired(TRUE)
      ->setDefaultValue(0)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE);

    return $fields;
  }
}
