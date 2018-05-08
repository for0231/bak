<?php

/**
 * @file
 * Contains \Drupal\order\Form\UserOrderFilterForm.
 */

namespace Drupal\order\user;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class UserOrderFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'user_order_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['filters'] = array(
      '#type' => 'details',
      '#title' => $this->t('Filter order messages'),
      '#open' => true,
    );

    $form['filters']['oid'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Order ID'),
      '#size' => 15
    );
     $form['filters']['title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#size' => 15
    );
	 	//加载订单状态到筛选条件里
		$form['filters']['status']= array(
      '#type' => 'select',
      '#title' => $this->t('Status'),
			'#options' => array('-1' => t('All')) + orderStatus(),
    );

    // 时间筛选条件
    $form['filters']['start'] = array(
    	'#title' => t('Start date'),
    	'#type' => 'textfield',
      '#size' => 15,
    );
    $form['filters']['expire'] = array(
    	'#title' => t('End date'),
    	'#type' => 'textfield',
      '#size' => 15,
    );
    //---给表单绑定时间控件的js文件
    $form['#attached']['library'] = array('core/jquery.ui.datepicker', 'common/hostspace.select_datetime');

   $fields = array('oid', 'title','date_time','start', 'expire', 'status');
    $allempty = true;
    foreach ($fields as $field) {
      if(!empty($_SESSION['my_order_filter'][$field])) {
        $form['filters'][$field]['#default_value'] = $_SESSION['my_order_filter'][$field];
        $allempty = false;
      }
    }
    /*if(!empty($_SESSION['my_order_filter']) && $_SESSION['my_order_filter']['status'] != -1) {
      $form['filters']['status']['#default_value'] = $_SESSION['my_order_filter']['status'];
      $allempty = false;
    }*/

    if($allempty) {
      $_SESSION['my_order_filter'] = array();
    }
    $form['filters']['actions'] = array(
      '#type' => 'actions',
     // '#attributes' => array('class' => array('container-inline')),
    );
    $form['filters']['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Filter')
    );
    if (!empty($_SESSION['my_order_filter'])) {
      $form['filters']['actions']['reset'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Reset'),
        '#limit_validation_errors' => array(),
        '#submit' => array('::resetForm'),
      );
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['my_order_filter']['oid'] = $form_state->getValue('oid');
	  $_SESSION['my_order_filter']['title'] = $form_state->getValue('title');
		$_SESSION['my_order_filter']['start'] = $form_state->getValue('start');
    $_SESSION['my_order_filter']['expire'] = $form_state->getValue('expire');
    if($form_state->getValue('status') == -1) {
	    $_SESSION['my_order_filter']['status'] = '';
    } else {
	    $_SESSION['my_order_filter']['status'] = $form_state->getValue('status');
    }
  }

  /**
   * Resets the filter form.
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['my_order_filter'] = array();
  }
}
