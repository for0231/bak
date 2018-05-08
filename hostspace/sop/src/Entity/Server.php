<?php

/**
 * @file
 * SOP之IPor带宽.
 * \Drupal\sop\Entity\Server;.
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
 *   id = "sop_task_server",
 *   label = @Translation("服务器上下架"),
 *   handlers = {
 *     "form" = {
 *       "default" = "Drupal\sop\Form\SopTaskServerForm",
 *       "delete" = "Drupal\sop\Form\SopTaskServerDeleteForm",
 *     },
 *   },
 *   base_table = "sop_task_server",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "edit-form" = "/admin/sop/server_up_down/{sop_task_server}/edit",
 *     "delete-form" = "/admin/sop/server_up_down/{sop_task_server}/delete",
 *   }
 * )
 */
class Server extends SOPEntityBase {
  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    if ($this->isNew()) {
      $this->set('uid', \Drupal::currentUser()->id());
      // $this->set('sop_op_type', 4);.
      $this->set('sop_status', 0);
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
    $fields['pid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel('产品')
      ->setSetting('target_type', 'product')
      ->setQueryable(FALSE)
      ->setTranslatable(TRUE)
      ->setRequired(TRUE);

    $fields['bips'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel('业务IP')
      ->setSetting('target_type', 'ipb')
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setTranslatable(TRUE);

    $fields['bandwidth'] = BaseFieldDefinition::create('string')
      ->setLabel(t('带宽'))
      ->setTranslatable(TRUE);

    $fields['bip_type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('IP类型'))
      ->setRequired(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'handler' => 'default',
        'handler_settings' => array(
          // Reference a single vocabulary.
          'target_bundles' => array(
            'suitable' => 'business_ip_segment_type',
          ),
        ),
      ));

    $fields['os'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('操作系统'))
      ->setRequired(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'handler' => 'default',
        'handler_settings' => array(
          // Reference a single vocabulary.
          'target_bundles' => array(
            'suitable' => 'server_system',
          ),
        ),
      ))
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => 3,
    // 设置在表单里显示的控件.
      ))
      ->setDisplayConfigurable('form', TRUE);

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

    $fields['base_description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('基本操作要求'));

    $fields['result_description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('处理过程、结果'));
    return $fields;
  }

}
