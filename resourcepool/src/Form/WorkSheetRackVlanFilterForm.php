<?php

/**
 * @file
 * Contains \Drupal\resourcepool\Form\RackVlanFilterForm.php.
 */

namespace Drupal\resourcepool\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * 增加IP封停表单类
 */
class WorkSheetRackVlanFilterForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'WorkSheet_rackVlan_filter_form';
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
    $form['filters']['room'] = array(
      '#type' => 'select',
      '#title' => '机房',
      '#options' => array(''=>'-All-','49' =>'LA','50' => 'HK','51' => 'DC'),
      '#default_value' => empty($_SESSION['worksheet_statistic']['room']) ? 'all': $_SESSION['worksheet_statistic']['room'],
    );
    $room ='';
    $room = empty($_SESSION['worksheet_statistic']['room']) ? 'all': $_SESSION['worksheet_statistic']['room'];
    $title ='';
    if( !empty($room)&& $room==49){
      $title ='LA 内网VLAN取值范围：1950~2065';
    }elseif(!empty($room)&& $room==50){
      $title ='HK 内网VLAN取值范围：1800~1899';
    }elseif(!empty($room)&& $room==51){
      $title ='DC 内网VLAN取值范围:';
    }
    $form['filters']['title'] = array(
      '#type' => 'label',
      '#title' => $title,
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
    return $form;
  }

  public function resetForm(){
    unset($_SESSION['worksheet_statistic']);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {   
    $_SESSION['worksheet_statistic']['room'] = $_POST['room'];
    $_SESSION['worksheet_statistic']['is_open'] = true;
  }
}
