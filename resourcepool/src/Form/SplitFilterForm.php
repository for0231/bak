<?php

/**
 * @file
 * Contains \Drupal\resourcepool\Form\SplitFilterForm
 */

namespace Drupal\resourcepool\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * 增加IP封停表单类
 */
class SplitFilterForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'split_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state,$type='') {
    $form['filters'] = array(
      '#type' => 'details',
      '#title' => '查询条件',
      '#open' => !empty($_SESSION['split']['is_open']),
    );
    if($type=='contract'){
      $form['filters']['contract'] = array(
        '#type' => 'textfield',
        '#title' => '合同编号',
        '#default_value' => empty($_SESSION['split']['contract']) ? '' : $_SESSION['split']['contract']
      );
      $form['filters']['client_name'] = array(
        '#type' => 'textfield',
        '#title' => '客户名称',
        '#default_value' => empty($_SESSION['split']['client_name']) ? '' : $_SESSION['split']['client_name']
      );
    }else{
      $form['filters']['affiliation_pro'] = array(
        '#type' => 'textfield',
        '#title' => '归属产品',
        '#default_value' => empty($_SESSION['split']['affiliation_pro']) ? '' : $_SESSION['split']['affiliation_pro']
      );
    }
    $form['filters']['submit'] = array(
      '#type' => 'submit',
      '#value' => '查询'
    );
    if(isset($_SESSION['split']['is_open'])){
      $form['filters']['reset'] = array(
        '#type' => 'submit',
        '#value' => '清空',
        '#submit' => array('::resetForm'),
      );
    }
    return $form;
  }

  public function resetForm(){
    unset($_SESSION['split']);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {   
    $_SESSION['split']['affiliation_pro'] = empty($_POST['affiliation_pro'])?'':$_POST['affiliation_pro'];
    $_SESSION['split']['contract'] = empty($_POST['contract'])?'':$_POST['contract'];
    $_SESSION['split']['client_name'] = empty($_POST['client_name'])?'':$_POST['client_name'];
    $_SESSION['split']['is_open'] = true;
  }
}
