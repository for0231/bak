<?php

namespace Drupal\requirement\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 *
 */
class RequirementFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'requirement_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['filters'] = [
      '#type' => 'details',
      '#title' => '查询条件',
      '#open' => !empty($_SESSION['requirement_filter']),
    ];
    // @todo 待进一步优化搜索框
    $form['filters']['user'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      '#selection_handler' => 'username',
      '#maxlength' => 1024,
      '#title' => '申请人',
    ];
    $form['filters']['begin'] = [
      '#type' => 'textfield',
      '#title' => '起始日期',
      '#default_value' => empty($_SESSION['requirement_filter']['begin']) ? '' : $_SESSION['requirement_filter']['begin'],
    ];
    $form['filters']['end'] = [
      '#type' => 'textfield',
      '#title' => '截止日期',
      '#default_value' => empty($_SESSION['requirement_filter']['end']) ? '' : $_SESSION['requirement_filter']['end'],
    ];
    // Requirement status.
    // -999是为了排序，放到选项中的第一行。无意义。.
    $options_all = [-999 => '请选择状态'];
    $options_status = getRequirementStatus();
    $options_status = $options_all + $options_status;
    $filter = isset($_SESSION['requirement_filter']) ? $_SESSION['requirement_filter'] : '';
    $filter_status = isset($filter['status']) ? $filter['status'] : 0;
    $form['filters']['status'] = [
      '#type' => 'select',
      '#title' => '工单状态',
      '#options' => $options_status,
      '#default_value' => $filter_status,
    ];
    $form['filters']['submit'] = [
      '#type' => 'submit',
      '#value' => '查询',
    ];
    $form['filters']['reset'] = [
      '#type' => 'submit',
      '#value' => '清空',
      '#submit' => ['::resetForm'],
    ];
    $form['#attached'] = [
      'library' => ['requirement/drupal.requirement.default'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['requirement_filter']['user'] = $form_state->getValue('user');
    $_SESSION['requirement_filter']['begin'] = $form_state->getValue('begin');
    $_SESSION['requirement_filter']['end'] = $form_state->getValue('end');
    $_SESSION['requirement_filter']['status'] = $form_state->getValue('status');
  }

  /**
   * {@inheritdoc}
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['requirement_filter'] = [];
  }

}
