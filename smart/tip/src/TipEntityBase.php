<?php

namespace Drupal\tip;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 *
 */
class TipEntityBase extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Tip ID'))
      ->setDescription(t('The tip ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The child tip UUID for updated.'))
      ->setReadOnly(TRUE);

    $fields['isreaded'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Readed'))
      ->setDescription(t('Tips is readed in logic.'))
      ->setDefaultValue(FALSE);

    $fields['isdeleted'] = BaseFieldDefinition::create('boolean')
    // 是否逻辑删除.
      ->setLabel(t('Deleted'))
      ->setDescription(t('Tips is deleted in logic.'))
      ->setDefaultValue(FALSE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Received user'))
      ->setSetting('target_type', 'user');

    $fields['cid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Created user'))
      ->setSetting('target_type', 'user');

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The cpu language code.'))
      ->setRevisionable(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the cpu was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the cpu was last edited..'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    return $fields;
  }

}
