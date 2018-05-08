<?php

/**
 * @file
 * Contains file
 * \Drupal\part\PartEntityBase.
 */

namespace Drupal\part;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\part\PartEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\UserInterface;

class PartEntityBase extends ContentEntityBase implements PartEntityInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID.'))
      ->setReadOnly(TRUE);

    $fields['vid'] = BaseFieldDefinition::create('integer')
      ->setDescription(t('The revision ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The cpu language code.'))
      ->setRevisionable(TRUE);

    $fields['brand'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Brand'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
      	'type' => 'string_textfield',
        'weight' => -30
      ))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE);

    $fields['model'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Model'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
      	'type' => 'string_textfield',
        'weight' => -20
      ))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE);

    $fields['standard'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Standard'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', array(
      	'type' => 'string_textfield',
        'weight' => -10
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['description'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Description'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
      	'type' => 'string_textarea',
        'weight' => 96
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['stock'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Storage Number'))
      ->setTranslatable(TRUE)
      ->setDescription(t('Storage Number'))
      ->setRevisionable(TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('The user ID create the cpu'))
      ->setDescription(t('The user ID of the author.'))
      ->setSetting('target_type', 'user')
      ->setQueryable(FALSE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the cpu was created.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the cpu was last edited..'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getBrand() {
    return $this->get('brand')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setBrand($brand) {
    $this->set('brand', $brand);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getModel() {
    return $this->get('model')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setModel($model) {
    $this->set('model', $model);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStandard() {
    return $this->get('standard')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStandard($standard) {
    $this->set('standard', $standard);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStock() {
    return $this->get('stock')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getUsedStock() {
    return $this->get('stock_used')->value;
  }


  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    if (isset($this->get('created')->value)) {
      return $this->get('created')->value;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($created) {
    $this->set('created', $created);
    return $this;
  }
  /**
   * {@inheritdoc}
   */
  public function setChangedTime($changed) {
    return $this->set('changed', $changed);
  }
  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTimeAcrossTranslations() {
  }
}
