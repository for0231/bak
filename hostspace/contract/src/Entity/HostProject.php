<?php

/**
 * @file
 *  Contains \Drupal\contract\Entity\HostProject.
 */

namespace Drupal\contract\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the project entity class.
 *
 * @ContentEntityType(
 *   id ="host_project",
 *   label = @Translation("projct"),
 *   handlers = {
 *     "access" = "Drupal\contract\ContractAccessControlHandler",
 *     "list_builder" = "Drupal\contract\HostProjectListBuilder",
 *     "form" = {
 *       "default" = "Drupal\contract\Form\ProjectForm",
 *        "delete" = "Drupal\contract\Form\ProjectDeleteForm",
 *     }
 *   }, 
 *   base_table = "host_project",
 *   data_table = "host_project_field_data",
 *   revision_table = "host_project_revision",
 *   revision_data_table = "host_project_field_revision",
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
 *     "edit-form" = "/admin/project/{host_project}",
 *     "delete-form" = "/admin/project/{host_project}/delete",
 *   } 
 * )
 */
class HostProject extends ContentEntityBase {

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
      ->setLabel('项目代码')
			->setTranslatable(TRUE)
			->setRequired(TRUE)
      ->setRevisionable(TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel('项目名称')
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
      	'type' => 'string',
        'weight' => -30
      ))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE);

    /*$fields['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel('项目类型')
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'handler' => 'default',
        'handler_settings' => array(
          'target_bundles' => array(
            'suitable' => 'server_rent_type',
          ),
          'auto_create' => TRUE,
        ),
      ))
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' => 3
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);*/

    $fields['type'] = BaseFieldDefinition::create('integer')
      ->setLabel('项目类型')
      ->setRevisionable(TRUE)
			->setRequired(TRUE)
      ->setTranslatable(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the ip was created.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    $fields['begin_time'] = BaseFieldDefinition::create('integer')
      ->setLabel('项目开始时间')
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    $fields['end_time'] = BaseFieldDefinition::create('integer')
      ->setLabel('项目结束时间')
      ->setRevisionable(TRUE)
			->setRequired(TRUE)
      ->setTranslatable(TRUE);

    $fields['status'] = BaseFieldDefinition::create('integer')
      ->setLabel('项目状态')
      ->setRevisionable(TRUE)
			->setRequired(TRUE)
      ->setTranslatable(TRUE);

   $fields['client'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel('客户')
      ->setSetting('target_type', 'contract_user')
      ->setQueryable(FALSE)
      ->setRevisionable(TRUE)
			->setRequired(TRUE)
      ->setTranslatable(TRUE);

    $fields['attachment'] = BaseFieldDefinition::create('attachment_file')
      ->setLabel('附件')
      ->setTranslatable(TRUE)
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setSetting('description_field', 1)
      ->setSetting('file_directory', 'Project_attachment')
      ->setSetting('file_extensions', 'txt jpg png gif jpeg')
      ->setDisplayOptions('form', array(
        'type' => 'attachment_generic',
        'weight' => 5
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Creator'))
      ->setDescription(t('The user ID of the project creator'))
      ->setSetting('target_type', 'user')
      ->setQueryable(FALSE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    $fields['person'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel('项目经理')
     ->setSetting('target_type', 'user')
      ->setQueryable(FALSE)
      ->setRevisionable(TRUE)
			->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSetting('handler', 'employee_group')
			->setDisplayOptions('form', array(
          'type' => 'entity_reference_autocomplete',
          'weight' => 2
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);

    $fields['remark'] = BaseFieldDefinition::create('string')
      ->setLabel('项目说明')
			->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
          'type' => 'text_textarea',
          'weight' => 4 
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);

    return $fields;
  }

  /**
   * 获取简单的实体字段的值
   */
  public function getProjectproperty($fied_name) {
   return $this->get($fied_name)->value;    
  }

  /**
   * 获取对象字段的实体
   */
  public function getPropertyObject($fied_name) {
    return $this->get($fied_name)->entity;
  }


}
