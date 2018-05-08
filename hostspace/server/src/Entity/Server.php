<?php

/**
 * @file
 * Contains \Drupal\server\Entity\Server.
 */

namespace Drupal\server\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the server entity class.
 *
 * @ContentEntityType(
 *   id = "server",
 *   label = @Translation("Server"),
 *   handlers = {
 *     "access" = "Drupal\server\ServerAccessControlHandler",
 *     "list_builder" = "Drupal\server\ServerListBuilder",
 *     "form" = {
 *       "default" = "Drupal\server\Form\ServerForm",
 *       "delete" = "Drupal\server\Form\ServerDeleteForm"
 *     }
 *   },
 *   base_table = "server",
 *   data_table = "server_field_data",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "sid",
 *     "label" = "server_code",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "edit-form" = "/admin/server/overview/{server}/edit",
 *     "delete-form" = "/admin/server/overview/{server}/delete",
 *   }
 * )
 */
class Server extends ContentEntityBase {
  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    // 系统预设服务器使用状态、上架状态、服务器类
    if($this->isNew()) {
      $this->set('status_equipment', 'off');
      $this->set('part_change', false);
      $this->set('server_code', common_regenerate_code());
      $this->set('uid', \Drupal::currentUser()->id());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);
    foreach($entities as $entity) {
      $server_type = $entity->get('type')->entity;
      $number = $server_type->get('server_number')->value;
      $server_type->set('server_number', $number-1);
      $server_type->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields['sid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Server ID'))
      ->setDescription(t('The server ID.'));

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID.'))
      ->setReadOnly(TRUE); 

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The server language code.'));

    $fields['server_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Server Code'))
      ->setDescription(t('The server code.'));

    $fields['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Configure Type'))
      ->setDescription(t('The server\'s configure type, such as configure 1,2,3,4,5'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSetting('target_type', 'server_type')
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' => 1
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);


    // @todo 后期再完善服务器绑定多个配件及数量相关问题

    $fields['cpu'] = BaseFieldDefinition::create('multi_part')
      ->setLabel('CPU')
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setSetting('target_type', 'part_cpu')
      ->setDisplayOptions('form', array(
        'type' => 'part_select_status',
        'weight' => 5
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['mainboard'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Mainboard'))
      ->setSetting('target_type', 'part_mainboard')
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => 10
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['memory'] =  BaseFieldDefinition::create('multi_part')
      ->setLabel('Memory')
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setSetting('target_type', 'part_memory')
      ->setDisplayOptions('form', array(
          'type' => 'part_select_status',
          'weight' => 15
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);

    $fields['harddisk'] =  BaseFieldDefinition::create('multi_part')
      ->setLabel('Hard disk')
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setSetting('target_type', 'part_harddisc')
      ->setDisplayOptions('form', array(
          'type' => 'part_select_status',
          'weight' => 20
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);

    $fields['chassis'] =  BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Case'))
      ->setSetting('target_type', 'part_chassis')
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' => 25
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True); 

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('The server description')) //服务器描述－产品系列的产品名称
      ->setDescription(t('The server\'s description name.'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', array(
          'type' => 'string',
          'weight' =>30, 
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);

    $fields['status_equipment'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Server Used Equipment Status'))
      ->setDescription(t('The server equipment status'))
      ->setTranslatable(TRUE);

    $fields['part_change'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Part change'))
      ->setDescription(t('Parts are changed'))
      ->setTranslatable(TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User ID'))
      ->setDescription(t('The user ID which create the server.'))
      ->setSetting('target_type', 'user')
      ->setQueryable(TRUE)
      ->setTranslatable(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the server was created.'))
      ->setTranslatable(TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the server was last edited.'))
      ->setTranslatable(TRUE);

    $fields['rid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('所属机房'))
      ->setDefaultValue(0);
    return $fields;
  }
}
