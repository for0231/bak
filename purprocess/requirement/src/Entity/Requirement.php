<?php

namespace Drupal\requirement\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the requirement entity class.
 *
 * @ContentEntityType(
 *   id = "requirement",
 *   label = @Translation("需求单票据"),
 *   base_table = "requirement",
 *   handlers = {
 *     "list_builder" = "Drupal\requirement\RequirementListBuilder",
 *     "access" = "Drupal\requirement\RequirementAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\requirement\Form\RequirementForm",
 *       "detail" = "Drupal\requirement\Form\RequirementDetailForm",
 *     }
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "no",
 *     "uuid"  = "uuid",
 *   },
 *   links = {
 *     "edit-form" = "/admin/requirement/{requirement}/edit",
 *     "detail-form" = "/admin/requirement/{requirement}/detail",
 *   }
 * )
 */
class Requirement extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    if ($this->isNew()) {
      $this->set('uid', \Drupal::currentUser()->id());
      $this->set('no', 'XQ' . \Drupal::service('requirement.requirementservice')->getIkNumberCounterCode());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('需求ID'))
      ->setDescription(t('需求ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The Requirement UUID for updated.'))
      ->setReadOnly(TRUE);

    // 需求单编号.
    $fields['no'] = BaseFieldDefinition::create('string')
      ->setLabel(t('需求单'))
      ->setDescription(t('需求单No.'));

    // 需求单标题.
    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('需求名称'))
      ->setDescription(t('需求名称.'));

    $fields['num'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('需求数量'))
      ->setDefaultValue(0)
      ->setDescription(t('需求数量.'));

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('申请人'))
      ->setSetting('target_type', 'user');

    // @todo pids 需要做需求配件实体ID关联,一对多关联.
    $fields['pids'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('配件'))
      ->setDescription(t('配件Nos.'))
      ->setSetting('target_type', 'part')
      ->setCardinality(-1);

    $fields['unitprice'] = BaseFieldDefinition::create('float')
      ->setLabel(t('成本单价'))
      ->setDefaultValue(0.00)
      ->setDescription(t('成本单价.'));

    $fields['purpose'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('用途'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('备注'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('工单状态'))
      ->setDefaultValue(0)
      ->setDescription(t('工单状态'));

    $fields['pay_status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('支付状态'))
      ->setDefaultValue(0)
      ->setDescription(t('支付状态'));

    $fields['ship_status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('配送状态'))
      ->setDefaultValue(0)
      ->setDescription(t('配送状态'));

    // 1.正常
    // 0.已删除.
    $fields['deleted'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('删除'))
      ->setDefaultValue(1)
      ->setDescription(t('是否删除'));

    $fields['audit'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('审批状态'))
      ->setDefaultValue(0)
      ->setDescription(t('审批状态'));

    $fields['aids'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('审批ID'))
      ->setSetting('target_type', 'audit')
      ->setCardinality(-1)
      ->setDescription(t('审批流程ID'));

    // 这个会和file实体关联.
    $fields['fid'] = BaseFieldDefinition::create('file')
      ->setLabel(t('附件'))
      ->setCardinality(-1)
      ->setDisplayOptions('form', [
        'type' => 'file_generic',
        'weight' => 15,
      ])
      ->setSetting('file_extensions', 'doc docx xls xlsx csv ppt pptx jpg jpep png pdf')
      ->setSetting('file_directory', 'uploads/require/[date:custom:Y]-[date:custom:m]')
      ->setDisplayConfigurable('form', TRUE);

    $fields['requiredate'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('需求交付时间'))
      ->setDescription(t('需求交付时间.'))
      ->setRequired(TRUE);

    $fields['requiretype'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('需求单类型'))
      ->setDescription(t('需求单类型.'))
      ->setRequired(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The requirement was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The requirement was last edited..'));

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code.'));

    return $fields;
  }

}
