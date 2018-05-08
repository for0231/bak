<?php
/**
 * @file
 * Contains \Drupal\knowledge\Form\KnowledgeContentForm.
 */

namespace Drupal\knowledge\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Url;

class KnowledgeContentForm extends ContentEntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $entity = $this->entity;
    $options = array();
    $items = entity_load_multiple_by_properties('knowledge_type', array('parent_id' => 0));
    foreach($items as $item) {
      $options[$item->id()] = $item->label();
      $c_items = entity_load_multiple_by_properties('knowledge_type', array('parent_id' => $item->id()));
      foreach($c_items as $c_item) {
        $options[$c_item->id()] = SafeMarkup::format('&nbsp;&nbsp;' . $c_item->label(), array());
      }
    }
    $form['type_id'] = array(
      '#type' => 'select',
      '#title' => '所属分类',
      '#options' => $options,
      '#weight' => 11,
      '#required' => true
    );
    $form['before_type_update'] = array(
      '#type' => 'value',
      '#value' => 0
    );
    if(!$entity->isNew()) {
      $form['type_id']['#default_value'] = $entity->get('type_id')->target_id;
      $form['before_type_update']['#value'] = $entity->get('type_id')->target_id;
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $type_id = $form_state->getValue('type_id');
    $items = entity_load_multiple_by_properties('knowledge_type', array('parent_id' => $type_id));
    if(count($items)) {
      $form_state->setErrorByName('type_id', '请选择未级分类');
    }
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $type_id = $form_state->getValue('type_id');
    $before_update = $form_state->getValue('before_type_update');
    if($before_update && $type_id != $before_update) {
      $entity->before_type_update = $before_update;
    }
    $entity->save();
    drupal_set_message('保存成功');
    $form_state->setRedirectUrl(new Url('entity.knowledge_content'));
  }
}
