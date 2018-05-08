<?php

/**
 * @file
 * Contains \Drupal\order\Form\OrderFilterForm.
 */

namespace Drupal\order\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\Core\Datetime\DrupalDateTime;

class OrderFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'order_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['filters'] = array(
      '#type' => 'details',
      '#title' => $this->t('Filter order messages'),
      '#open' => !empty($_SESSION['order_filter']),
    );
    
    $form['filters']['oid'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Order code')
    );
    $form['filters']['title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Title')
    );

	//加载订单状态到筛选条件里
	$form['filters']['status']= array(
      '#type' => 'select',
      '#title' => $this->t('Status'),
	  '#options' => orderStatus(),
    );

    //下过订单的客户
    $client = \Drupal::service('order.product')->getClient();
    $client_ops = array();
    foreach($client as $key=>$value) {
      $client_ops[$value->uid] = $value->client_name ? $value->client_name : entity_load('user',$value->uid)->getUsername();
    }
    $form['filters']['uid'] = array(
      '#type' => 'select',
      '#title' => t('Client'),
      '#options' => array('' => 'All') + $client_ops
    );
    // 负责专员
    $employee = \Drupal::service('order.product')->getClientService();
    $service_ops = array();
    foreach($employee as $key=>$value) {
      $service_ops[$value->client_service] = $value->employee_name ;
    }
    $form['filters']['client_service'] = array(
      '#type' => 'select',
      '#title' => t('Commissioner'),
      '#options' => array('' => 'All') + $service_ops
    );
    // 时间筛选条件
    $form['filters']['start'] = array(
    	'#title' => t('Start date'),
    	'#type' => 'textfield',
      '#size' => 12,
    );
    $form['filters']['expire'] = array(
    	'#title' => t('End Date'),
    	'#type' => 'textfield',
      '#size' => 12,
    );
    //---给表单绑定时间控件的js文件
    $form['#attached']['library'] = array('core/jquery.ui.datepicker', 'common/hostspace.select_datetime');

    $fields = array('oid', 'title', 'status', 'uid', 'client_service', 'start', 'expire');
    $allempty = true;
    foreach ($fields as $field) {
      if($field == 'status') {
        if(!isset($_SESSION['order_filter'][$field])) {
          $_SESSION['order_filter'][$field] =-1;
        }
        if($_SESSION['order_filter'][$field] != -1) {
          $form['filters'][$field]['#default_value'] = $_SESSION['order_filter'][$field];
          $allempty = false;
        }
      } else{
        if(!empty($_SESSION['order_filter'][$field])) {
          $form['filters'][$field]['#default_value'] = $_SESSION['order_filter'][$field];
          $allempty = false;
        }
      }
    }
    if($allempty) {
      $_SESSION['order_filter'] = array();
    }
    $form['filters']['actions'] = array(
      '#type' => 'actions',
      '#attributes' => array('class' => array('container-inline')),
    );
    $form['filters']['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Filter')
    );
    if (!empty($_SESSION['order_filter'])) {
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
    $_SESSION['order_filter']['oid'] = $form_state->getValue('oid');
	  $_SESSION['order_filter']['title'] = $form_state->getValue('title');
		$_SESSION['order_filter']['start'] = $form_state->getValue('start');
    $_SESSION['order_filter']['expire'] = $form_state->getValue('expire');
	  $_SESSION['order_filter']['status'] = $form_state->getValue('status');
    $_SESSION['order_filter']['uid'] = $form_state->getValue('uid');
	  $_SESSION['order_filter']['client_service'] = $form_state->getValue('client_service');
  }

  /**
   * Resets the filter form.
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['order_filter'] = array();
  }
}
