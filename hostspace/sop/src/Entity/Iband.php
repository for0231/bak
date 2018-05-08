<?php

/**
 * @file
 * SOP之IPor带宽.
 * \Drupal\sop\Entity\Iband;.
 */

namespace Drupal\sop\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\sop\SOPEntityBase;
/**
 * Defines the sop entity class.
 *
 * @ContentEntityType(
 *   id = "sop_task_iband",
 *   label = @Translation("IPor带宽"),
 *   handlers = {
 *     "form" = {
 *       "default" = "Drupal\sop\Form\SopTaskIBandForm",
 *     },
 *   },
 *   base_table = "sop_task_iband",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class Iband extends SOPEntityBase {
  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    if ($this->isNew()) {
      $this->set('created', REQUEST_TIME);
      $this->set('sop_status', 0);
    }
    if (empty($this->get('aips'))) {
      // 如果当前SOP关联的服务器状态是新上架时，保存SOP时应该清空服务器通过自动分配的业务IP.
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    \Drupal::service('sop.soptaskservice')->save($this, $update);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['aips'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel('添加IP')
      ->setSetting('target_type', 'ipb')
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setTranslatable(TRUE);
    $fields['sips'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel('停用IP')
      ->setSetting('target_type', 'ipb')
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setTranslatable(TRUE);

    $fields['isbind'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('已绑定'))
      ->setTranslatable(TRUE);

    $fields['online_op'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('上机操作'))
      ->setTranslatable(TRUE);

    $fields['management_card'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('已添加管理卡'))
      ->setTranslatable(TRUE);
    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('需求'))
      ->setDisplayOptions('form', array(
        'type' => 'text_textarea',
        'weight' => 25,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['result_description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('处理过程、结果'));

    return $fields;
  }

}
