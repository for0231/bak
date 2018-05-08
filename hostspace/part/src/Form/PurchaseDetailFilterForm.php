<?php

/**
 * @file
 * Contains \Drupal\part\Form\PurchaseDetailFilterForm.
 */

namespace Drupal\part\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class PurchaseDetailFilterForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'purchase_detail_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['filters'] = array(
      '#type' => 'details',
      '#title' => $this->t('Filter Purchase details'),
      '#open' => !empty($_SESSION['purchase_detail_filter']),
    );
    $form['filters']['keyword'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Keyword')
    );
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
    $fields = array('keyword', 'start', 'expire');
    $allempty = true;
    foreach($fields as $field) {
      if(!empty($_SESSION['purchase_detail_filter'][$field])) {
        $form['filters'][$field]['#default_value'] = $_SESSION['purchase_detail_filter'][$field];
        $allempty = false;
      }
    }     
    if($allempty) {
      $_SESSION['purchase_detail_filter'] = array();
    }

    $form['filters']['actions'] = array(
      '#type' => 'actions',
    );
    $form['filters']['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Filter')
    );
    if (!empty($_SESSION['purchase_detail_filter'])) {
      $form['filters']['actions']['reset'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Reset'),
        '#limit_validation_errors' => array(),
        '#submit' => array('::resetForm'),
      );
    }
    //---给表单绑定时间控件的js文件
    $form['#attached']['library'] = array('core/jquery.ui.datepicker', 'common/hostspace.select_datetime');
    $form['#prefix'] = '<div class="column">';
    $form['#suffix'] = '</div>';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['purchase_detail_filter']['keyword'] = $form_state->getValue('keyword');
	  $_SESSION['purchase_detail_filter']['start'] = $form_state->getValue('start');
		$_SESSION['purchase_detail_filter']['expire'] = $form_state->getValue('expire');
  }

  /**
   * Resets the filter form.
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['purchase_detail_filter'] = array();
  }
}
