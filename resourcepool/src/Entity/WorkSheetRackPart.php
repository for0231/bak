<?php

/**
 * @file
 * IP类工单实体
 * \Drupal\resourcepool\Entity\WorkSheetRackPart;.
 */

namespace Drupal\resourcepool\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityStorageInterface;
/**
 * Defines the sop entity class.
 *
 * @ContentEntityType(
 *   id = "work_sheet_rackpart",
 *   label = "机柜配置",
 *   handlers = {
 *     "list_builder" = "Drupal\resourcepool\WorkSheetRackPartListBuilder",
 *     "form" = {
 *       "default" = "Drupal\resourcepool\Form\WorkSheetRackPartAddForm",
 *       "delete" = "Drupal\resourcepool\Form\WorkSheetRackPartDeleteForm"
 *     }
 *   },
 *   base_table = "work_sheet_rackpart",
 *   entity_keys = {
 *     "id" = "id",
 *   },
 *   links = {
 *     "edit-form" = "/admin/worksheetrackpart/{work_sheet_rackpart}/edit",
 *     "delete-form" = "/admin/worksheetrackpart/{work_sheet_rackpart}/delete"
 *   }
 * )
 */
class WorkSheetRackPart extends ContentEntityBase {
  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel('机柜编号')
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);
      
    $fields['manage_ip'] = BaseFieldDefinition::create('string')
      ->setLabel('管理IP')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 11
      ))
      ->setDisplayConfigurable('form', TRUE);
    
    $fields['room'] = BaseFieldDefinition::create('integer')
      ->setLabel('机房');

    $fields['rack'] = BaseFieldDefinition::create('string')
      ->setLabel('机柜')
      ->setDescription('服务器安装的机柜号；如：C104(LA表示方法)、1B9.66(HK表示方法)')
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 15,
      ))
      ->setDisplayConfigurable('form', TRUE);
      
    $fields['serve_u'] = BaseFieldDefinition::create('string')
      ->setLabel('服务器U位')
      ->setDescription('服务器具体安放的位置,如:36U、1-5U或1U-5U(针对节点服务器)')
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 14
      ))
      ->setDisplayConfigurable('form', True);
      
    $fields['port'] = BaseFieldDefinition::create('string')
      ->setLabel('管理端口')
      ->setDescription('服务器IPMI端口接到对应交换机的端口，如：Ethe0/0/1')
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 15,
      ))
      ->setDisplayConfigurable('form', TRUE);
    $fields['href_manage_network_card'] = BaseFieldDefinition::create('string')
      ->setLabel('管理网卡Cacti')
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 15,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['ma_ic_name'] = BaseFieldDefinition::create('string')
      ->setLabel('管理交换机名称')
      ->setDescription('例如：M411-S2300-23U  M:管理交换机简称 411:机柜号 S2300:交换机型号 23U:交换机对应U位')
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 15,
      ))
      ->setDisplayConfigurable('form', TRUE);
    $fields['node'] = BaseFieldDefinition::create('string')
      ->setLabel('Node')
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 15,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['ye_network_card'] = BaseFieldDefinition::create('string')
      ->setLabel('业务网卡')
      ->setDescription('服务器NIC1接到对应交换机的端口；如：Gi0/0/1')
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 15,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['href_network_card'] = BaseFieldDefinition::create('string')
      ->setLabel('业务网卡Cacti')
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 15,
      ))
      ->setDisplayConfigurable('form', TRUE);


    $fields['ye_vlan'] = BaseFieldDefinition::create('string')
      ->setLabel('业务vlan')
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 15,
      ))
      ->setDisplayConfigurable('form', TRUE);
      
    $fields['ye_icname'] = BaseFieldDefinition::create('string')
      ->setLabel('业务交换机名称')
      ->setDescription('例如:C411-S5700-22U C:业务交换机简称 411:机柜号 S5700:交换机型号 22U:交换机对应U位')
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 15,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['network_card'] = BaseFieldDefinition::create('string')
      ->setLabel('第二网卡')
      ->setDescription('服务器NIC2接到对应交换机的端口,如:Ethe0/0/1')
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 15,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['href_network_card_two'] = BaseFieldDefinition::create('string')
      ->setLabel('业务网卡Cacti-2')
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 15,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['networkcard_vlan'] = BaseFieldDefinition::create('string')
      ->setLabel('第二网卡vlan')
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 15,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['networkcard_icname'] = BaseFieldDefinition::create('string')
      ->setLabel('第二网卡交换机名称')
      ->setDescription('例如:C411-S5700-22U C:业务交换机简称 411:机柜号 S5700:交换机型号 22U:交换机对应U位')
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 15,
      ))
      ->setDisplayConfigurable('form', TRUE);
    $fields['notes'] = BaseFieldDefinition::create('string_long')
      ->setLabel('备注')
      ->setDefaultValue('')
      ->setDisplayOptions('form', array(
        'type' => 'string_textarea',
        'weight' => 17
      ))
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }
}
