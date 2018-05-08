<?php

namespace Drupal\paypro\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the tip entity class.
 *
 * @ContentEntityType(
 *   id = "paypro",
 *   label = @Translation("支付单票据"),
 *   base_table = "paypro",
 *   handlers = {
 *     "list_builder" = "Drupal\paypro\PayproListBuilder",
 *     "access" = "Drupal\paypro\PayproAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\paypro\Form\PayproForm",
 *       "detail" = "Drupal\paypro\Form\PayproDetailForm"
 *     }
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "no",
 *     "uuid"  = "uuid",
 *   },
 *   links = {
 *     "edit-form" = "/admin/paypro/{paypro}/edit",
 *     "detail-form" = "/admin/paypro/{paypro}/detail",
 *   }
 * )
 */
class Paypro extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    if ($this->isNew()) {
      $this->set('uid', \Drupal::currentUser()->id());
      $this->set('no', 'ZF' . \Drupal::service('paypro.payproservice')->getIkNumberCounterCode());
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
      ->setLabel(t('Paypro ID'))
      ->setDescription(t('The Paypro ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The Paypro UUID for updated.'))
      ->setReadOnly(TRUE);

    // 支付单标题.
    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('支付单名称'))
      ->setDescription(t('支付单名称.'));

    // 支付单编号.
    $fields['no'] = BaseFieldDefinition::create('string')
      ->setLabel(t('支付单No.'))
      ->setDescription(t('支付单No.'));

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('申请人'))
      ->setSetting('target_type', 'user');

    $fields['fnos'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('付款单'))
      ->setDescription(t('付款单No.'))
      ->setDefaultValue(0)
      ->setSetting('target_type', 'paypre')
      ->setCardinality(-1);
    // @todo fname, fbank, faccount, ftype, fbserial这几个字段待处理,后期定是否删除
    $fields['fname'] = BaseFieldDefinition::create('string')
      ->setLabel(t('付款账户名'))
      ->setDescription(t('付款账户名'));

    $fields['fbank'] = BaseFieldDefinition::create('string')
      ->setLabel(t('付款开户行'))
      ->setDescription(t('付款开户行'));

    $fields['faccount'] = BaseFieldDefinition::create('string')
      ->setLabel(t('付款账号'))
      ->setDescription(t('付款账号'));

    $fields['fbserial'] = BaseFieldDefinition::create('string')
      ->setLabel(t('银行流水号'))
      ->setDescription(t('银行流水号'));

    $fields['ftype'] = BaseFieldDefinition::create('string')
      ->setLabel(t('付款币种'))
      ->setDescription(t('付款币种'));

    /*
    $fields['pcnos'] = BaseFieldDefinition::create('entity_reference')
    ->setLabel(t('支付记录'))
    ->setDescription(t('支付记录'))
    ->setSetting('target_type', 'pcord')
    ->setCardinality(-1);
     */

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The paypro was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The paypro was last edited..'));

    $fields['status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('工单状态'))
      ->setDefaultValue(0)
      ->setDescription(t('工单状态'));

    $fields['audit'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('审批状态'))
      ->setDefaultValue(0)
      ->setDescription(t('审批状态'));

    $fields['amount'] = BaseFieldDefinition::create('float')
      ->setLabel(t('支付单金额'))
      ->setDefaultValue(0.00)
      ->setDescription(t('支付单金额'));

    // 1.正常
    // 0.已删除.
    $fields['deleted'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('删除'))
      ->setDefaultValue(1)
      ->setDescription(t('是否删除'));

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
      ->setSetting('file_directory', 'uploads/paypro/[date:custom:Y]-[date:custom:m]')
      ->setDisplayConfigurable('form', TRUE);

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
