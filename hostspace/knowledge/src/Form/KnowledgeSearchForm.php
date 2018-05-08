<?php

/**
 * @file
 * Contains \Drupal\knowledge\Form\KnowledgeSearchForm.
 */

namespace Drupal\knowledge\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * 增加策略表单类
 */
class KnowledgeSearchForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'knowledge_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $key = '') {
    $form['search_zone'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => 'search_zone'
      )
    );
    $form['search_zone']['key'] = array(
      '#type' => 'textfield',
      '#placeholder' => '请输入搜索关键词',
      '#required' => true,
      '#default_value' => $key
    );
    $form['search_zone']['search'] = array(
      '#type' => 'submit',
      '#value' => '搜索'
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $key = $form_state->getValue('key');
    $form_state->setRedirectUrl(new Url('knowledge.search', array('key' => $key)));
  }
}
