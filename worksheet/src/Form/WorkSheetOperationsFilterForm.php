<?php

/**
 * @file
 * Contains \Drupal\worksheet\Form\WorkSheetOperationsFilterForm
 */

namespace Drupal\worksheet\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * 增加IP封停表单类
 */
class WorkSheetOperationsFilterForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'worksheet_comment_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['filters'] = array(
      '#type' => 'details',
      '#title' => '查询条件',
      '#open' => !empty($_SESSION['worksheet_statistic']['is_open']),
    );
    $form['filters']['begin'] = array(
      '#type' => 'textfield',
      '#title' => '开始时间',
      '#default_value' => empty($_SESSION['worksheet_statistic']['begin']) ? date('Y-m-d'): $_SESSION['worksheet_statistic']['begin'],
    );
    $form['filters']['end'] = array(
      '#type' => 'textfield',
      '#title' => '结束时间',
      '#default_value' => empty($_SESSION['worksheet_statistic']['end']) ? date('Y-m-d'): $_SESSION['worksheet_statistic']['end'],
    );
    $form['filters']['subdivision'] = array(
      '#type' => 'select',
      '#title' => '子部门',
      '#options' => array(''=>'-All-','生产组' =>'生产组','技术组' => '技术组'),
      '#default_value' => empty($_SESSION['worksheet_statistic']['subdivision']) ? 'all': $_SESSION['worksheet_statistic']['subdivision'],
    );
    $form['filters']['submit'] = array(
      '#type' => 'submit',
      '#value' => '查询'
    );
    if(isset($_SESSION['worksheet_statistic']['is_open'])){
      $form['filters']['reset'] = array(
        '#type' => 'submit',
        '#value' => '清空',
        '#submit' => array('::resetForm'),
      );
    }
    $form['#attached'] = array(
      'library' => array('worksheet/drupal.work_sheet_statistic')
    );
    return $form;
  }

  public function resetForm(){
    unset($_SESSION['worksheet_statistic']);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {   
    $_SESSION['worksheet_statistic']['begin'] = $_POST['begin'];
    $_SESSION['worksheet_statistic']['end'] = $_POST['end'];
    $_SESSION['worksheet_statistic']['subdivision'] = $_POST['subdivision'];
    $_SESSION['worksheet_statistic']['is_open'] = true;
  }
}
