<?php

/**
 * @file
 * Contains \Drupal\question\Entity\ServerQuestion.
 */

namespace Drupal\question\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the server question entity class.
 *
 * @ContentEntityType(
 *   id ="question",
 *   label = @Translation("Question of server"),
 *   handlers = {
 *     "access" = "Drupal\question\QuestionAccessControlHandler",
 *     "list_builder" = "Drupal\question\QuestionListBuilder",
 *     "form" = {
 *       "default" = "Drupal\question\user\DeclareQuestionForm",
 *       "detail" = "Drupal\question\Form\QuestionDetailForm",
 *       "client_detail" = "Drupal\question\user\ClientQuestionDetailForm",
 *     }
 *   },
 *   base_table = "server_question",
 *   data_table = "server_question_field_data",
 *   revision_table = "server_question_revision",
 *   revision_data_table = "server_question_field_revision",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "label" = "parent_question_class",
 *     "langcode" = "langcode",
 *     "uuid"  = "uuid",
 *   },
 *   links = {
 *     "edit-form" = "/admin/question/{question}/detail",
 *   }
 * )
 */
class ServerQuestion extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
 /* public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    $this->set('uid', \Drupal::currentUser()->id());
  }*/

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID'))
      ->setReadOnly(TRUE);

    $fields['vid'] = BaseFieldDefinition::create('integer')
      ->setDescription(t('The revision ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The cpu language code.'))
      ->setRevisionable(TRUE);

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('ID.'));

    $fields['parent_question_class'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Category'))
      ->setDescription(t('The category of question.'))
      ->setSetting('target_type', 'question_class')
      ->setQueryable(TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User that  declare the question'))
      ->setDescription(t('The user that deal the question.'))
      ->setSetting('target_type', 'user')
      ->setQueryable(FALSE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

     $fields['status'] = BaseFieldDefinition::create('integer')
       ->setLabel(t('Status'))
       ->setDescription(t('Status'))
       ->setRequired(TRUE)
       ->setTranslatable(TRUE)
       ->setRevisionable(TRUE);

    $fields['server_uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User that deal the question'))
      ->setDescription(t('The user that declare the question.'))
      ->setSetting('target_type', 'user')
      ->setQueryable(FALSE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);
    $fields['accept_stamp'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Reception hours'))
      ->setDescription(t('Time of accepted this question.'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE);

    $fields['pre_finish_stamp'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Expected completion time'))
      ->setDescription(t('Time of expected to complete.'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE);

    $fields['finish_stamp'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Finish time'))
      ->setDescription(t('Actual finish time.'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE);

    $fields['mipstring'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Management ip'))
      ->setDescription(t('Management ip'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE);

    $fields['ipstring'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Business ip'))
      ->setDescription(t('Business ip'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE);

    $fields['content'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Content of this question'))
      ->setDescription(t('Content of this question'))
			->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
          'type' => 'text_textarea',
          'weight' => 6
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the ip was created.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    return $fields;
  }
}
