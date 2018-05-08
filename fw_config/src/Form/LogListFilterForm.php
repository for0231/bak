<?php

/**
 * @file
 * Contains \Drupal\fw_config\Form\LogListFilterForm.
 */

namespace Drupal\fw_config\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * 手动增加牵引表单类
 */
class LogListFilterForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jd_log_list_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['filter'] = array(
      '#type' => 'details',
      '#title' => '过滤条件',
      '#open' => !empty($_SESSION['fw_log_filter'])
    );
    $form['filter']['person'] = array(
      '#type' => 'entity_autocomplete',
      '#title' => '操作员',
      '#target_type' => 'user',
      '#selection_handler' => 'username',
      '#maxlength' => 1024
    );
    $form['filter']['keyword'] = array(
      '#type' => 'textfield',
      '#title' => '关键字',
      '#default_value' => empty($_SESSION['fw_log_filter']['keyword']) ? '' : $_SESSION['fw_log_filter']['keyword']
    );
    if(!empty($_SESSION['fw_log_filter']['uid'])) {
      $form['filter']['person']['#default_value'] = entity_load('user', $_SESSION['fw_log_filter']['uid']);
    } 
    $form['filter']['query'] = array(
      '#type' => 'submit',
      '#value' => '查询',
    );
    return $form;
  }
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $uid = $form_state->getValue('person');
    $keyword = $form_state->getValue('keyword');
    if(empty($uid) && empty($keyword)) {
      $_SESSION['fw_log_filter'] = array();
    } else {
      $_SESSION['fw_log_filter'] = array(
         'uid' => $uid,
         'keyword' => $keyword
      );
    }
  }
}
