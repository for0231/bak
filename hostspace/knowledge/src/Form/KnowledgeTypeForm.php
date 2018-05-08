<?php
/**
 * @file
 * Contains \Drupal\knowledge\Form\KnowledgeTypeForm.
 */

namespace Drupal\knowledge\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class KnowledgeTypeForm extends ContentEntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $entity = $this->entity;
    $options = array('0' => '根目录');
    $nodes = entity_load_multiple_by_properties('knowledge_type', array('parent_id' => 0));
    foreach($nodes as $node) {
      if(!$entity->isNew()) {
        if($node->id() == $entity->id()) {
           continue;
        }
      }
      $options[$node->id()] = $node->label();
    }
    $form['parent_id'] = array(
      '#type' => 'select',
      '#title' => '上级目录',
      '#options' => $options,
      '#weight' => 2
    );
    if(!$entity->isNew()) {
      $form['parent_id']['#default_value'] = $entity->get('parent_id')->value;
    }
    return $form;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $entity->save();
    drupal_set_message('保存成功');
    $form_state->setRedirectUrl(new Url('entity.knowledge_type'));
  }
}
