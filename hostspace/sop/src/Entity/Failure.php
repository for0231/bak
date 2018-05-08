<?php

/**
 * @file
 * SOP之故障处理.
 * \Drupal\sop\Entity\Failure;.
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
 *   id = "sop_task_failure",
 *   label = @Translation("故障处理"),
 *   handlers = {
 *     "form" = {
 *       "default" = "Drupal\sop\Form\SopTaskFailureForm",
 *     },
 *   },
 *   base_table = "sop_task_failure",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class Failure extends SOPEntityBase {
  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    if ($this->isNew()) {
      $this->set('created', REQUEST_TIME);
      // $this->set('uid', \Drupal::currentUser()->id());
      $this->set('sop_op_type', 10);
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

    $fields['base_description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('故障原因'));

    $fields['result_description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('处理过程、结果'));

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

    $fields['level'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('问题等级'))
      ->setDescription(t('如,简单,困难,复杂等'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['qid'] = BaseFieldDefinition::create('entity_reference')
      ->setSetting('target_type', 'question')
      ->setQueryable(FALSE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    return $fields;
  }

}
