<?php

namespace Drupal\kaoqin\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the upon entity class.
 *
 * @ContentEntityType(
 *   id = "upon",
 *   label = @Translation("上班管理"),
 *   base_table = "kaoqin_upon",
 *   handlers = {
 *     "list_builder" = "Drupal\kaoqin\KaoqinUponListBuilder",
 *     "access" = "Drupal\kaoqin\KaoqinAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\kaoqin\Form\KaoqinUponForm",
 *       "delete" = "Drupal\kaoqin\Form\KaoqinUponDeleteForm",
 *     }
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid"  = "uuid",
 *   },
 *   links = {
 *     "edit-form" = "/admin/kaoqin/upon/{upon}/edit",
 *     "delete-form" = "/admin/kaoqin/upon/{upon}/delete",
 *   }
 * )
 */
class Upon extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    $this->set('uid', \Drupal::currentUser()->id());
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
      ->setLabel(t('Upon ID'))
      ->setDescription(t('The Upon ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The Upon UUID for updated.'))
      ->setReadOnly(TRUE);

    $fields['type'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('班次'))
      ->setDefaultValue(1)
      ->setDescription(t('班次.'));

    $fields['icontype'] = BaseFieldDefinition::create('string')
      ->setLabel(t('事件类型图标'))
      ->setDescription(t('事件类型图标.'));

    $fields['allday'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('全天事件'))
      ->setDefaultValue(FALSE)
      ->setDescription(t('全天事件.'));

    $fields['iconcolor'] = BaseFieldDefinition::create('string')
      ->setLabel(t('事件颜色'))
      ->setDescription(t('事件颜色.'));

    // 使用部门术语列表.
    $fields['depart'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('部门'))
      ->setDefaultValue(0)
      ->setDescription(t('部门.'))
      ->setRequired(TRUE)
      ->setSettings([
        'target_type' => 'taxonomy_term',
        'handler_settings' => [
          'target_bundles' => ['enterprises'],
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['user'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('申请人'))
      ->setSetting('target_type', 'user');

    $fields['datetime'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('月份'))
      ->setDefaultValue(0)
      ->setDescription(t('月份.'));

    $fields['morningsign'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('上班'))
      ->setDefaultValue(0)
      ->setDescription(t('上班.'));

    $fields['afternoonsign'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('下班'))
      ->setDefaultValue(0)
      ->setDescription(t('下班.'));

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('描述'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textfield',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE);



    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('申请人'))
      ->setSetting('target_type', 'user');

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The upon was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The upon was last edited..'));

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code.'));

    return $fields;
  }

}

