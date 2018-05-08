<?php

namespace Drupal\paypro\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 *
 */
class PayproAggretotalFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'paypro_aggretotal_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['filters'] = [
      '#type' => 'details',
      '#title' => '查询条件',
      '#open' => !empty($_SESSION['paypro_aggretotal_filter']),
    ];
    $form['filters']['level'] = [
      '#type' => 'select',
      '#options' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
      '#title' => '查询层级',
      '#description' => '根据需要查询层级，默认为2级',
    ];
    $form['filters']['begin'] = [
      '#type' => 'textfield',
      '#title' => '起始日期',
      '#default_value' => empty($_SESSION['paypro_aggretotal_filter']['begin']) ? '' : $_SESSION['paypro_aggretotal_filter']['begin'],
    ];
    $form['filters']['end'] = [
      '#type' => 'textfield',
      '#title' => '截止日期',
      '#default_value' => empty($_SESSION['paypro_aggretotal_filter']['end']) ? '' : $_SESSION['paypro_aggretotal_filter']['end'],
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
    $_SESSION['paypro_aggretotal_filter']['level'] = $form_state->getValue('level');
    $_SESSION['paypro_aggretotal_filter']['begin'] = $form_state->getValue('begin');
    $_SESSION['paypro_aggretotal_filter']['end'] = $form_state->getValue('end');
  }

  /**
   * {@inheritdoc}
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['paypro_aggretotal_filter'] = [];
  }

}
