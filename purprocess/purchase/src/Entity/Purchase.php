<?php

namespace Drupal\purchase\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the purchase entity class.
 *
 * @ContentEntityType(
 *   id = "purchase",
 *   label = @Translation("采购单票据"),
 *   base_table = "purchase",
 *   handlers = {
 *     "list_builder" = "Drupal\purchase\PurchaseListBuilder",
 *     "access" = "Drupal\purchase\PurchaseAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\purchase\Form\PurchaseForm",
 *       "detail" = "Drupal\purchase\Form\PurchaseDetailForm",
 *     }
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "no",
 *     "uuid"  = "uuid",
 *   },
 *   links = {
 *     "edit-form" = "/admin/purchase/{purchase}/edit",
 *     "detail-form" = "/admin/purchase/{purchase}/detail",
 *   }
 * )
 */
class Purchase extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    if ($this->isNew()) {
      $this->set('uid', \Drupal::currentUser()->id());
      $this->set('no', 'CG' . \Drupal::service('purchase.purchaseservice')->getIkNumberCounterCode());
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
      ->setLabel(t('Purchase ID'))
      ->setDescription(t('The Purchase ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The Purchase UUID for updated.'))
      ->setReadOnly(TRUE);

    // 采购单编号.
    $fields['no'] = BaseFieldDefinition::create('string')
      ->setLabel(t('采购单No.'))
      ->setDescription(t('采购单No.'));

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('申请人'))
      ->setSetting('target_type', 'user');

    // 采购单标题.
    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('采购单名称'))
      ->setDescription(t('采购单名称.'));

    $fields['pids'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('配件'))
      ->setDescription(t('配件Nos.'))
      ->setSetting('unsigned', TRUE)
      ->setSetting('target_type', 'part')
      ->setCardinality(-1);

    $fields['purpose'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Purpose'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE);

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
      ->setSetting('file_directory', 'uploads/purchase/[date:custom:Y]-[date:custom:m]')
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The purchase was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The purchase was last edited..'));

    // 1.正常
    // 0.已删除.
    $fields['deleted'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('删除'))
      ->setDefaultValue(1)
      ->setDescription(t('是否删除'));

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code.'));

    $fields['iscancel'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('是否取消'))
      ->setDefaultValue(0)
      ->setDescription(t('是否取消.'));

    return $fields;
  }

}
