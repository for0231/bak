<?php

namespace Drupal\tip\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

use Drupal\tip\TipEntityBase;

/**
 * Defines the msg entity class.
 *
 * @ContentEntityType(
 *   id = "tip_msg",
 *   label = @Translation("Msg"),
 *   base_table = "smart_tip_msg",
 *   handlers = {
 *     "form" = {
 *       "default" = "Drupal\tip\Form\MsgForm"
 *     }
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "edit-form" = "/admin/smart/tip/msg/{tip_msg}/edit",
 *   }
 * )
 */
class Msg extends TipEntityBase {

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    $this->set('cid', \Drupal::currentUser()->id());
    // $this->set('uid', \Drupal::currentUser()->id()); 这个属于接收者用户的ID.
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    \Drupal::service('tip.tipservice')->save($this, $update);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDisplayOptions('form', [
        'type' => 'string',
        'weight' => 9,
    // 设置在表单里显示的控件.
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['content'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Content'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

}
