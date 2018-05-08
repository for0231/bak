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
use Drupal\hostlog\HostLogFactory;

/**
 * Defines the server entity class.
 *
 * @ContentEntityType(
 *   id = "server_type",
 *   label = @Translation("Server type"),
 *   handlers = {
 *     "access" = "Drupal\server\ServerAccessControlHandler",
 *     "list_builder" = "Drupal\server\ServerTypeListBuilder",
 *     "form" = {
 *       "default" = "Drupal\server\Form\ServerTypeForm",
 *       "delete" = "Drupal\server\Form\ServerTypeDeleteForm",
 *       "add_server" = "Drupal\server\Form\ServerTypeAddServerForm"
 *     }
 *   },
 *   base_table = "server_type",
 *   data_table = "server_type_field_data",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "tid",
 *     "label" = "name",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "edit-form" = "/admin/server/type/{server_type}/edit",
 *     "delete-form" = "/admin/server/type/{server_type}/delete",
 *     "add_server-form" = "/admin/server/type/{server_type}/server/add"
 *   }
 * )
 */
class ServerType extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    if($this->isNew()) {
      $this->set('uid', \Drupal::currentUser()->id());
    }
  }

  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    if(isset($this->new_server_number)) {
      $number = $this->new_server_number;
      $description = '';
      if(isset($this->new_server_description)){
        $description = $this->new_server_description;
      }
      $values = array();
      $values['type'] = $this->id();
      //把cpu标记成基础配件
      $cpu_vals = $this->get('cpu')->getValue();
      foreach($cpu_vals as &$cpu_val) {
        $cpu_val['value'] = 1;
      }
      $values['cpu'] = $cpu_vals;
      $values['mainboard'] = $this->get('mainboard')->target_id;
      //把memory标记成基础配件
      $memory_vals = $this->get('memory')->getValue();
      foreach($memory_vals as &$memory_val) {
        $memory_val['value'] = 1;
      }
      $values['memory'] =  $memory_vals;
      //把harddisk标记成基础配件
      $harddisk_vals = $this->get('harddisk')->getValue();
      foreach($harddisk_vals as &$harddisk_val) {
        $harddisk_val['value'] = 1;
      }
      $values['harddisk'] = $harddisk_vals;
      $values['chassis'] = $this->get('chassis')->target_id;
      $values['name'] = $description;
      $values['rid'] = $this->get('rid')->value;
      $server = entity_create('server', $values);
      for($i=0; $i<$number; $i++) {
        $duplicate = $server->createDuplicate();
        $duplicate->save();
        HostLogFactory::OperationLog('server')->log($duplicate, 'insert');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['tid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Server ID'))
      ->setDescription(t('The server type Id.'));

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID.'))
      ->setReadOnly(TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The server language code.'));

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Catalog name'))
      ->setRequired(true)
      ->setDisplayOptions('form', array(
          'type' => 'string',
          'weight' =>1,
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);

    $fields['cpu'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel('CPU')
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setSetting('target_type', 'part_cpu')
      ->setDisplayOptions('form', array(
        'type' => 'part_select_single',
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

    $fields['memory'] =  BaseFieldDefinition::create('entity_reference')
      ->setLabel('Memory')
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setSetting('target_type', 'part_memory')
      ->setDisplayOptions('form', array(
          'type' => 'part_select_single',
          'weight' => 15
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);

    $fields['harddisk'] =  BaseFieldDefinition::create('entity_reference')
      ->setLabel('Hard disk')
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setSetting('target_type', 'part_harddisc')
      ->setDisplayOptions('form', array(
          'type' => 'part_select_single',
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

    $fields['server_number'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Number'))
      ->setDescription(t('The server total number '))
      ->setDefaultValue(0);

     $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User ID'))
      ->setDescription(t('The user ID which create the server type.'))
      ->setSetting('target_type', 'user')
      ->setQueryable(TRUE)
      ->setTranslatable(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the server type was created.'))
      ->setTranslatable(TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the server type was last edited.'))
      ->setTranslatable(TRUE);
    // 用于传递服务器的机房信息。
    // 机房ID在本实体中无真实意义。
    $fields['rid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('所属机房'))
      ->setDefaultValue(0);
    return $fields;
  }
}

