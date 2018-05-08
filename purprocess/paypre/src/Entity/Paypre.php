<?php

namespace Drupal\paypre\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the tip entity class.
 *
 * @ContentEntityType(
 *   id = "paypre",
 *   label = @Translation("付款单票据"),
 *   base_table = "paypre",
 *   handlers = {
 *     "list_builder" = "Drupal\paypre\PaypreListBuilder",
 *     "access" = "Drupal\paypre\PaypreAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\paypre\Form\PaypreForm",
 *       "detail" = "Drupal\paypre\Form\PaypreDetailForm",
 *     }
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "no",
 *     "uuid"  = "uuid",
 *   },
 *   links = {
 *     "edit-form" = "/admin/paypre/{paypre}/edit",
 *     "detail-form" = "/admin/paypre/{paypre}/detail",
 *   }
 * )
 */
class Paypre extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    if ($this->isNew()) {
      $this->set('uid', \Drupal::currentUser()->id());
      $this->set('no', 'FK' . \Drupal::service('paypre.paypreservice')->getIkNumberCounterCode());
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
      ->setLabel(t('Paypre ID'))
      ->setDescription(t('The Paypre ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The Paypre UUID for updated.'))
      ->setReadOnly(TRUE);

    // 付款单标题.
    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('付款单名称'))
      ->setDescription(t('付款单名称.'));

    // 付款单编号.
    $fields['no'] = BaseFieldDefinition::create('string')
      ->setLabel(t('付款单No.'))
      ->setDescription(t('付款单No.'));

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('申请人'))
      ->setSetting('target_type', 'user');

    // @todo cnos 需要做采购单ID关联
    $fields['cnos'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('采购单No.'))
      ->setDescription(t('采购单No.列表,一对多的关系'))
      ->setDefaultValue(0)
      ->setSetting('target_type', 'purchase')
      ->setCardinality(-1);

    // @todo fname, fbank, faccount, ftype, fbserial这几个字段待处理,后期定是否删除
    $fields['acceptname'] = BaseFieldDefinition::create('string')
      ->setLabel(t('收款账户名'))
      ->setDescription(t('收款账户名'));

    $fields['acceptbank'] = BaseFieldDefinition::create('string')
      ->setLabel(t('收款开户行'))
      ->setDescription(t('收款开户行'));

    $fields['acceptaccount'] = BaseFieldDefinition::create('string')
      ->setLabel(t('收款账号'))
      ->setDescription(t('收款账号'));

    $fields['ftype'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('采购币种'))
      ->setDescription(t('采购币种'))
      ->setSetting('target_type', 'currency');

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The paypre was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The paypre was last edited..'));

    $fields['status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('工单状态'))
      ->setDefaultValue(0)
      ->setDescription(t('工单状态'));

    $fields['audit'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('审批状态'))
      ->setDefaultValue(0)
      ->setDescription(t('审批状态'));

    $fields['aids'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('审批ID'))
      ->setSetting('target_type', 'audit')
      ->setCardinality(-1)
      ->setDescription(t('审批流程ID'));

    $fields['fid'] = BaseFieldDefinition::create('file')
      ->setLabel(t('附件'))
      ->setCardinality(-1)
      ->setDisplayOptions('form', [
        'type' => 'file_generic',
        'weight' => 15,
      ])
      ->setSetting('file_extensions', 'doc docx xls xlsx csv ppt pptx jpg jpep png pdf')
      ->setSetting('file_directory', 'uploads/paypre/[date:custom:Y]-[date:custom:m]')
      ->setDisplayConfigurable('form', TRUE);

    $fields['contact_no'] = BaseFieldDefinition::create('string')
      ->setLabel(t('合同'))
      ->setDescription(t('合同No.'));

    $fields['amount'] = BaseFieldDefinition::create('float')
      ->setLabel(t('付款单金额'))
      ->setDefaultValue(0.00)
      ->setDescription(t('付款单金额'));

    $fields['pre_amount'] = BaseFieldDefinition::create('float')
      ->setLabel(t('付款单应付金额'))
      ->setDefaultValue(0.00)
      ->setDescription(t('本次付款单应付金额'));

    // 1.正常
    // 0.已删除.
    $fields['deleted'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('删除'))
      ->setDefaultValue(1)
      ->setDescription(t('是否删除'));

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code.'));
    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('备注'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE);
    $fields['iscancel'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('是否取消'))
      ->setDefaultValue(0)
      ->setDescription(t('是否取消.'));
    return $fields;
  }

}
