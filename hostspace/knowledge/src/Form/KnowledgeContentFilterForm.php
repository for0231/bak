<?php

/**
 * @file
 * Contains \Drupal\knowledge\Form\KnowledgeContentFilterForm.
 */

namespace Drupal\knowledge\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\SafeMarkup;

class KnowledgeContentFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'knowledge_content_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['filters'] = array(
      '#type' => 'details',
      '#title' => $this->t('Filter server messages'),
      '#open' => !empty($_SESSION['knowledge_content_filter']),
    );
    $form['filters']['key'] = array(
      '#type' => 'textfield',
      '#title' => '关键词',
      '#size' => 20
    );
    $options = array('' => '－请选择－');
    $items = entity_load_multiple_by_properties('knowledge_type', array('parent_id' => 0));
    foreach($items as $item) {
      $options[$item->id()] = $item->label();
      $c_items = entity_load_multiple_by_properties('knowledge_type', array('parent_id' => $item->id()));
      foreach($c_items as $c_item) {
        $options[$c_item->id()] = SafeMarkup::format('&nbsp;&nbsp;' . $c_item->label(), array());
      }
    }
    $form['filters']['type_id'] = array(
      '#type' => 'select',
      '#title' => '所属分类',
      '#options' => $options,
    );

    $form['filters']['user'] = array(
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      '#title' => '创建者'
    );
    $fields = array('key', 'type_id', 'user');
    $allempty = true;
    foreach ($fields as $field) {
      if(isset($_SESSION['knowledge_content_filter'][$field]) && $_SESSION['knowledge_content_filter'][$field] != '') {
        if($field == 'user') {
          $form['filters'][$field]['#default_value'] = entity_load('user', $_SESSION['knowledge_content_filter'][$field]);
        } else {
          $form['filters'][$field]['#default_value'] = $_SESSION['knowledge_content_filter'][$field];
        }
        $allempty = false;
      }
    }
    if($allempty) {
      $_SESSION['knowledge_content_filter'] = array();
    }
    $form['filters']['actions'] = array(
      '#type' => 'actions',
      '#attributes' => array('class' => array('container-inline')),
    );
    $form['filters']['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Filter')
    );
    if (!empty($_SESSION['knowledge_content_filter'])) {
      $form['filters']['actions']['reset'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Reset'),
        '#limit_validation_errors' => array(),
        '#submit' => array('::resetForm'),
      );
    }
    $form['#prefix'] = '<div class="column">';
    $form['#suffix'] = '</div>';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['knowledge_content_filter']['key'] = $form_state->getValue('key');
    $_SESSION['knowledge_content_filter']['type_id'] = $form_state->getValue('type_id');
    $_SESSION['knowledge_content_filter']['user'] = $form_state->getValue('user');
  }

  /**
   * Resets the filter form.
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['knowledge_content_filter'] = array();
  }
}
