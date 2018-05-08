<?php

namespace Drupal\kaoqin\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the kaoqin entity class.
 *
 * @ContentEntityType(
 *   id = "kaoqin",
 *   label = @Translation("考勤管理"),
 *   base_table = "kaoqin",
 *   handlers = {
 *     "list_builder" = "Drupal\kaoqin\KaoqinListBuilder",
 *     "access" = "Drupal\kaoqin\KaoqinAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\kaoqin\Form\KaoqinForm"
 *     }
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid"  = "uuid",
 *   },
 *   links = {
 *     "edit-form" = "/admin/kaoqin/{kaoqin}/edit",
 *   }
 * )
 */
class Kaoqin extends ContentEntityBase {

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
      ->setLabel(t('Kaoqin ID'))
      ->setDescription(t('The Kaoqin ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The Kaoqin UUID for updated.'))
      ->setReadOnly(TRUE);


    $fields['code'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('人员编号'))
      ->setDefaultValue(0)
      ->setDescription(t('人员编号.'));

    $fields['emname'] = BaseFieldDefinition::create('string')
      ->setLabel(t('姓名'))
      ->setDescription(t('姓名.'));

    $fields['logdate'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('考勤日期'))
      ->setDefaultValue(0)
      ->setDescription(t('考勤日期.'));

    $fields['weekday'] = BaseFieldDefinition::create('string')
      ->setLabel(t('星期'))
      ->setDescription(t('星期.'));

    $fields['banci'] = BaseFieldDefinition::create('string')
      ->setLabel(t('班次'))
      ->setDescription(t('班次.'));

    $fields['morningsign'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('上班'))
      ->setDefaultValue(0)
      ->setDescription(t('上班.'));

    $fields['afternoonsign'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('下班'))
      ->setDefaultValue(0)
      ->setDescription(t('下班.'));


    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('申请人'))
      ->setSetting('target_type', 'user');

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The kaoqin was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The kaoqin was last edited..'));

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code.'));

    return $fields;
  }

}
