<?php

/**
 * @file
 *  Contains \Drupal\contract\Entity\CotractUser.
 */

namespace Drupal\contract\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the user entity for contract.
 *
 * @ContentEntityType(
 *   id ="contract_user",
 *   label = @Translation("projct"),
 *   handlers = {
 *     "access" = "Drupal\contract\ContractAccessControlHandler",
 *     "list_builder" = "Drupal\contract\ContractUserListBuilder",
 *     "form" = {
 *       "default" = "Drupal\contract\Form\ContractUserForm",
 *       "delete" = "Drupal\contract\Form\ContractUserDeleteForm",
 *     }
 *   },   
 *   base_table = "contract_user",
 *   data_table = "contract_user_field_data",
 *   revision_table = "contract_user_revision",
 *   revision_data_table = "contract_user_field_revision",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "label" = "name",
 *     "langcode" = "langcode",
 *     "uuid"  = "uuid",
 *   },
 *   links = {
 *     "edit-form" = "/admin/contract/client/{contract_user}",
 *     "delete-form" = "/admin/contract/client/{contract_user}/delete",
 *   } 
 * )
 */
class CotractUser extends ContentEntityBase {

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

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel('公司/用户名称')
			->setTranslatable(TRUE)
			->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
      	'type' => 'string',
        'weight' => 1
      ))
      ->setSetting('max_length', 50)
      ->setDisplayConfigurable('form', TRUE);


    $fields['type'] = BaseFieldDefinition::create('integer')
      ->setLabel('用户类型')
      ->setRevisionable(TRUE)
			->setRequired(TRUE)
      ->setTranslatable(TRUE);

    $fields['contry'] = BaseFieldDefinition::create('string')
      ->setLabel('用户国家')
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
      	'type' => 'string',
        'weight' => 5
      ))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE);

    $fields['address'] = BaseFieldDefinition::create('string')
      ->setLabel('用户地址')
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
      	'type' => 'string',
        'weight' => 5
      ))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE);

    $fields['bank'] = BaseFieldDefinition::create('string')
      ->setLabel('用户开户行')
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
      	'type' => 'string',
        'weight' => 5
      ))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE);

    $fields['bank_account'] = BaseFieldDefinition::create('string')
      ->setLabel('银行帐号')
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
      	'type' => 'string',
        'weight' => 5
      ))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE);

    $fields['registered_capital'] = BaseFieldDefinition::create('float')
      ->setLabel('注册资金')
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
      	'type' => 'number',
        'weight' => 5
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['phone'] = BaseFieldDefinition::create('string')
      ->setLabel('用户电话')
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
      	'type' => 'string',
        'weight' => 5
      ))
      ->setSetting('max_length', 20)
      ->setDisplayConfigurable('form', TRUE);

     $fields['leader'] = BaseFieldDefinition::create('string')
      ->setLabel('法定代表人')
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
      	'type' => 'string',
        'weight' => 5
      ))
      ->setSetting('max_length', 50)
      ->setDisplayConfigurable('form', TRUE);


     $fields['contact'] = BaseFieldDefinition::create('string')
      ->setLabel('主要联系人')
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE);

    $fields['mobile'] = BaseFieldDefinition::create('string')
      ->setLabel('联系电话')
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE);

    $fields['email'] = BaseFieldDefinition::create('email')
      ->setLabel('联系邮件')
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE);


    $fields['remark'] = BaseFieldDefinition::create('string')
      ->setLabel('客户说明')
			->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
          'type' => 'text_textarea',
          'weight' => 10 
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel('创建人')
      ->setSetting('target_type', 'user')
      ->setQueryable(FALSE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);  
 
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
