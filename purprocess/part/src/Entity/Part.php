<?php

namespace Drupal\part\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the part entity class.
 *
 * @ContentEntityType(
 *   id = "part",
 *   label = @Translation("配件"),
 *   base_table = "part",
 *   handlers = {
 *     "list_builder" = "Drupal\part\PartListBuilder",
 *     "access" = "Drupal\part\PartAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\part\Form\PartForm"
 *     }
 *   },
 *   revision_table = "part_revision",
 *   revision_data_table = "part_field_revision",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid"  = "uuid",
 *     "revision" = "vid",
 *   },
 *   links = {
 *     "edit-form" = "/admin/part/{part}/edit",
 *   }
 * )
 */
class Part extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    if ($this->isNew()) {
      // @todo 这个uid应该以需求单创建的UID为基准, 不应该动态的设置这个UID
      $this->set('uid', \Drupal::currentUser()->id());
      $this->set('created', REQUEST_TIME);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    // @todo 需要移植到requirement里面
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('自增ID'))
      ->setDescription(t('自增ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The PartRequirement UUID for updated.'))
      ->setReadOnly(TRUE);

    $fields['vid'] = BaseFieldDefinition::create('integer')
      ->setDescription(t('The revision ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('申请人'))
      ->setSetting('target_type', 'user')
      ->setRevisionable(TRUE);

    // 关联getPartSaveStatus()
    $fields['save_status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('保存状态'))
      ->setDefaultValue(0)
      ->setDescription(t('配件类型保存状态.'))
      ->setRevisionable(TRUE);

    // 关联getPartModifiedStatus()
    $fields['part_is_modified'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('配件修改状态'))
      ->setDefaultValue(0)
      ->setDescription(t('配件类型保存状态.'))
      ->setRevisionable(TRUE);
    $vocabulary_entity = taxonomy_vocabulary_load('parts');
    // 指向术语tid.
    $fields['nid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel($vocabulary_entity->label())
      ->setDefaultValue(0)
      ->setDescription(t('配件ID'))
      ->setRequired(TRUE)
      ->setSettings([
        'target_type' => 'taxonomy_term',
        'handler_settings' => [
          'target_bundles' => ['parts'],
        ],
      ])
      ->setDisplayOptions('form', [
        'type' => 'cshs',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setRevisionable(TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('配件名称'))
      ->setDescription(t('配件名称.'))
      ->setRevisionable(TRUE);

    $fields['parttype'] = BaseFieldDefinition::create('string')
      ->setLabel(t('配件类型'))
      ->setDescription(t('配件类型.'))
      ->setRevisionable(TRUE);

    $fields['purposetype'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('使用类型'))
      ->setDefaultValue(0)
      ->setDescription(t('物品分类使用类型.'))
      ->setRevisionable(TRUE);

    // 使用地点术语列表.
    $fields['locate_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('使用地点'))
      ->setDefaultValue(0)
      ->setDescription(t('使用地点.'))
      ->setRequired(TRUE)
      ->setSettings([
        'target_type' => 'taxonomy_term',
        'handler_settings' => [
          'target_bundles' => ['located'],
        ],
      ])
      ->setDisplayOptions('form', [
        'type' => 'cshs',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setRevisionable(TRUE);

    $fields['num'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('数量'))
      ->setDefaultValue(0)
      ->setDescription(t('数量.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE);

    // @todo 关联物流商tid术语列表
    $fields['ship_supply_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('物流公司'))
      ->setDefaultValue(0)
      ->setDescription(t('物流公司ID.'))
      ->setSettings([
        'target_type' => 'taxonomy_term',
        'handler_settings' => [
          'target_bundles' => ['ships'],
        ],
      ])
      ->setRevisionable(TRUE);

    $fields['ship_supply_no'] = BaseFieldDefinition::create('string')
      ->setLabel(t('物流单号'))
      ->setDescription(t('物流单号.'))
      ->setRevisionable(TRUE);

    // 物流费.
    $fields['wuliufee'] = BaseFieldDefinition::create('float')
      ->setLabel(t('物流费'))
      ->setDescription(t('物流费.'))
      ->setDefaultValue(0)
      ->setRevisionable(TRUE);

    $fields['supply_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('供应商'))
      ->setDefaultValue(0)
      ->setDescription(t('供应商ID.'))
      ->setSettings([
        'target_type' => 'taxonomy_term',
        'handler_settings' => [
          'target_bundles' => ['supply'],
        ],
      ])
      ->setRevisionable(TRUE);

    $fields['ftype'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('币种'))
      ->setDescription(t('币种'))
      ->setSetting('target_type', 'currency')
      ->setRevisionable(TRUE);

    $fields['unitprice'] = BaseFieldDefinition::create('float')
      ->setLabel(t('成本单价'))
      ->setDefaultValue(0.00)
      ->setDescription(t('成本单价.'))
      ->setRevisionable(TRUE);

    $fields['unit'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('单位'))
      ->setDescription(t('单位.'))
      ->setSettings([
        'target_type' => 'taxonomy_term',
        'handler_settings' => [
          'target_bundles' => ['unit'],
        ],
      ])
      ->setDisplayOptions('form', [
        'type' => 'cshs',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setRequired(TRUE)
      ->setRevisionable(TRUE);

    $fields['purpose'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('用途'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('备注'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE);

    // 需求单ID.
    $fields['rno'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('需求单No.'))
      ->setDescription(t('需求单No.'))
      ->setDefaultValue(0)
      ->setRevisionable(TRUE);

    // 采购单ID.
    $fields['cno'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('采购单No.'))
      ->setDescription(t('采购单No.'))
      ->setDefaultValue(0)
      ->setRevisionable(TRUE);

    // 付款单ID.
    $fields['fno'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('付款单No.'))
      ->setDescription(t('付款单No.'))
      ->setDefaultValue(0)
      ->setRevisionable(TRUE);

    // 支付单ID.
    $fields['pno'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('支付单No.'))
      ->setDescription(t('支付单No.'))
      ->setDefaultValue(0)
      ->setRevisionable(TRUE);

    $fields['re_status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('需求单工单状态'))
      ->setDefaultValue(0)
      ->setDescription(t('需求单工单状态'))
      ->setRevisionable(TRUE);

    $fields['re_ship_status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('需求单配送状态'))
      ->setDefaultValue(0)
      ->setDescription(t('需求单配送状态'))
      ->setRevisionable(TRUE);

    $fields['re_audit'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('需求单审批状态'))
      ->setDefaultValue(0)
      ->setDescription(t('需求单审批状态'))
      ->setRevisionable(TRUE);

    $fields['ch_status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('采购单工单状态'))
      ->setDefaultValue(0)
      ->setDescription(t('采购单工单状态'))
      ->setRevisionable(TRUE);

    $fields['ch_audit'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('采购单审批状态'))
      ->setDefaultValue(0)
      ->setDescription(t('采购单审批状态'))
      ->setRevisionable(TRUE);

    $fields['requiredate'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('需求交付时间'))
      ->setDefaultValue(0)
      ->setDescription(t('需求交付时间.'))
      ->setRevisionable(TRUE);

    $fields['plandate'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('预计交付时间'))
      ->setDefaultValue(0)
      ->setDescription(t('预计交付时间.'))
      ->setRevisionable(TRUE);

    $fields['requiretype'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('需求单类型'))
      ->setDefaultValue(0)
      ->setDescription(t('需求单类型.'))
      ->setRevisionable(TRUE);

    // 1.正常
    // 0.已删除.
    $fields['deleted'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('删除'))
      ->setDefaultValue(1)
      ->setDescription(t('是否删除'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDefaultValue(0)
      ->setDescription(t('The part was created.'))
      ->setRevisionable(TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDefaultValue(0)
      ->setDescription(t('The part was last edited..'))
      ->setRevisionable(TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDefaultValue(0)
      ->setDescription(t('The language code.'))
      ->setRevisionable(TRUE);

    $fields['caiwunos'] = BaseFieldDefinition::create('string')
      ->setLabel(t('财务组编号'))
      ->setDescription(t('财务组编号.'))
      ->setRevisionable(TRUE);

    return $fields;
  }

}
