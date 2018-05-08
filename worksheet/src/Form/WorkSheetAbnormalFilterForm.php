<?php

/**
 * @file
 * Contains \Drupal\worksheet\Form\WorkSheetStatisticFilterForm.
 */

namespace Drupal\worksheet\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * 增加IP封停表单类
 */
class WorkSheetAbnormalFilterForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'abnormal_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['filters'] = array(
      '#type' => 'details',
      '#title' => '查询条件',
      '#open' => !empty($_SESSION['worksheet_abnormal']['is_open']),
    );
    $form['filters']['keyword'] = array(
      '#type' => 'textfield',
      '#title' => '关键字',
      '#default_value' => empty($_SESSION['worksheet_abnormal']['keyword']) ? '' : $_SESSION['worksheet_abnormal']['keyword'],
    );
    $form['filters']['type'] = array(
      '#type' => 'select',
      '#title' => '工单类型',
      '#options' => array('all' => '-All-') + getEntityType(),
      '#default_value' => empty($_SESSION['worksheet_abnormal']['type']) ? 'all' :  $_SESSION['worksheet_abnormal']['type'],
    );
    $users = entity_load_multiple('user');
    $creater = array();
    $hander = array();
    foreach($users as $user) {
      if($user->id() > 1) {
        if(in_array('worksheet_operation', $user->getRoles())) {
          $hander[$user->id()] = $user->label();
        } else {
          $creater[$user->id()] = $user->label();
        }
      }
    }
    $form['filters']['creater'] = array(
      '#type' => 'select',
      '#title' => '建单人',
      '#options' => array('all' => '-All-') + $creater + $hander,
      '#default_value' => empty($_SESSION['worksheet_abnormal']['creater']) ? 'all' : $_SESSION['worksheet_abnormal']['creater']
    );
    $form['filters']['hander'] = array(
      '#type' => 'select',
      '#title' => '处理人',
      '#options' => array('all' => '-All-') + $hander,
      '#default_value' => empty($_SESSION['worksheet_abnormal']['hander']) ? 'all' : $_SESSION['worksheet_abnormal']['hander']
    );
    $form['filters']['created_begin'] = array(
      '#type' => 'textfield',
      '#title' => '建单时间',
      '#default_value' => empty($_SESSION['worksheet_abnormal']['begin']) ? date('Y-m') . '-01' : $_SESSION['worksheet_abnormal']['begin'] ,
    );
    $form['filters']['created_end'] = array(
      '#type' => 'textfield',
      '#default_value' => empty($_SESSION['worksheet_abnormal']['end']) ? date('Y-m-d') : $_SESSION['worksheet_abnormal']['end']
    );
    $form['filters']['submit'] = array(
      '#type' => 'submit',
      '#value' => '查询'
    );
    if(isset($_SESSION['worksheet_abnormal']['is_open'])){
      $form['filters']['reset'] = array(
        '#type' => 'submit',
        '#value' => '清空',
        '#submit' => array('::resetForm'),
      );
      if($_SESSION['worksheet_abnormal']['type'] != 'all') {
        $form['filters']['export'] = array(
          '#type' => 'link',
          '#title' => '导出',
          '#url' => new Url('admin.worksheet.abnormal.export'),
          '#attributes' => array(
            'class' => array('button')
          )
        );
      }      
    }
    return $form;
  }

  public function resetForm(array &$form, FormStateInterface $form_state){
    unset($_SESSION['worksheet_abnormal']);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {   
    $_SESSION['worksheet_abnormal']['keyword'] = $form_state->getValue('keyword');
    $_SESSION['worksheet_abnormal']['type'] = $form_state->getValue('type');
    $_SESSION['worksheet_abnormal']['creater'] = $form_state->getValue('creater');
    $_SESSION['worksheet_abnormal']['hander'] = $form_state->getValue('hander');
    $_SESSION['worksheet_abnormal']['begin'] = $form_state->getValue('created_begin');
    $_SESSION['worksheet_abnormal']['end'] = $form_state->getValue('created_end');
    $_SESSION['worksheet_abnormal']['is_open'] = true;
  }
}
