<?php

/**
 * @file
 *  Contains \Drupal\contract\Entity\HostContract.
 */

namespace Drupal\contract\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the contract entity class.
 *
 * @ContentEntityType(
 *   id ="host_contract",
 *   label = @Translation("Contract"),
 *   handlers = {
 *     "access" = "Drupal\contract\ContractAccessControlHandler",
 *     "list_builder" = "Drupal\contract\HostContractListBuilder",
 *     "form" = {
 *       "default" = "Drupal\contract\Form\ContractForm",
 *       "execute" = "Drupal\contract\Form\ContractExcuteForm",
 *       "complete" = "Drupal\contract\Form\ContractCompleteForm",
 *       "stop" = "Drupal\contract\Form\ContractstopForm",
 *       "delete" = "Drupal\contract\Form\ContractDeleteForm"
 *     }
 *   },   
 *   base_table = "host_contract",
 *   data_table = "host_contract_field_data",
 *   revision_table = "host_contract_revision",
 *   revision_data_table = "host_contract_field_revision",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "label" = "code",
 *     "langcode" = "langcode",
 *     "uuid"  = "uuid",
 *   },
 *   links = {
 *     "edit-form" = "/admin/contract/{host_contract}",
 *     "execute-form" = "/admin/contract/{host_contract}/execute",
 *     "stop-form" = "/admin/contract/{host_contract}/stop",
 *     "complete-form" = "/admin/contract/{host_contract}/complete",
 *     "delete-form" = "/admin/contract/{host_contract}/delete"
 *   }
 * )
 */
class HostContract extends ContentEntityBase {

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
      ->setLabel('ID')
      ->setDescription(t('ID.'));

    $fields['code'] = BaseFieldDefinition::create('string')
      ->setLabel('合同编号')
			->setTranslatable(TRUE)
			->setRequired(TRUE)
      ->setRevisionable(TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel('合同名称')
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
      	'type' => 'string',
        'weight' => -30
      ))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the ip was created.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    $fields['effect_time'] = BaseFieldDefinition::create('integer')
      ->setLabel('生效时间')
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    $fields['invalid_time'] = BaseFieldDefinition::create('integer')
      ->setLabel('失效时间')
      ->setRevisionable(TRUE)
			->setRequired(TRUE)
      ->setTranslatable(TRUE);

    $fields['amount'] = BaseFieldDefinition::create('float')
      ->setLabel('合同金额')
      ->setRevisionable(TRUE)
			->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', array(
      	'type' => 'number',
        'weight' => -30
      ))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE);

   $fields['client'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel('客户')
      ->setSetting('target_type', 'contract_user')
      ->setQueryable(FALSE)
      ->setRevisionable(TRUE)
			->setRequired(TRUE)
      ->setTranslatable(TRUE);

   $fields['project'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel('项目名称')
      ->setQueryable(FALSE)
      ->setRevisionable(TRUE)
			->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSetting('target_type', 'host_project');
      /*->setSetting('handler', 'project_group')
			->setDisplayOptions('form', array(
          'type' => 'entity_reference_autocomplete',
          'weight' => 2
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);*/

    $fields['person'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel('负责人')
      ->setRevisionable(TRUE)
			->setRequired(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'employee_group')
			->setDisplayOptions('form', array(
          'type' => 'entity_reference_autocomplete',
          'weight' => 2
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);


    $fields['status'] = BaseFieldDefinition::create('integer')
      ->setLabel('合同状态')
      ->setRevisionable(TRUE)
			->setRequired(TRUE)
      ->setTranslatable(TRUE);

    $fields['remark'] = BaseFieldDefinition::create('string')
      ->setLabel('合同说明')
			->setTranslatable(TRUE)
      ->setRevisionable(TRUE);

    $fields['attachment'] = BaseFieldDefinition::create('attachment_file')
      ->setLabel('合同附件')
      ->setTranslatable(TRUE)
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setSetting('description_field', 1)
      ->setSetting('file_extensions', 'txt jpg png gif jpeg')
      ->setDisplayOptions('form', array(
        'type' => 'attachment_generic',
        'weight' => 5
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Creator'))
      ->setDescription(t('The user ID of the contract creator'))
      ->setSetting('target_type', 'user')
      ->setQueryable(FALSE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    $fields['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel('合同类型')
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'handler' => 'default',
        'handler_settings' => array(
          'target_bundles' => array(
            'suitable' => 'contract_type',
          ),
          'auto_create' => TRUE,
        ),
      ))
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' => 3
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);
    return $fields;
  }

  /**
   * 获取简单的实体字段的值
   */
  public function getproperty($fied_name) {
   return $this->get($fied_name)->value;    
  }

  /**
   * 获取对象字段的实体
   */
  public function getPropertyObject($fied_name) {
    return $this->get($fied_name)->entity;
  }


}
